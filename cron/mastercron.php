<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Master Cron Job Task Runner
 *
 * Usage via Linux crontab:
 * * * * * * php /path/to/cron/mastercron.php >> /path/to/storage/logs/cron.log 2>&1
 * Or via web request (secured by secret key):
 * https://yourdomain.com/cron/mastercron.php?key=YOUR_CRON_SECRET
 */

$isCli = (php_sapi_name() === 'cli');
$cronKey = $_GET['key'] ?? '';
$configuredKey = getenv('CRON_KEY') ?: 'smm_master_cron_secret_7788';

if (!$isCli && $cronKey !== $configuredKey) {
    http_response_code(403);
    die("Access denied. CLI execution or valid security key required.\n");
}

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Database;
use Core\Logger;
use Providers\Adapters\StandardProvider;

$timestamp = date('Y-m-d H:i:s');
$cronLog = "[{$timestamp}] Starting Master Cron execution...\n";

// -----------------------------------------------------------------------------
// 1. Synchronize Pending / Processing / In-Progress Orders with Providers
// -----------------------------------------------------------------------------
try {
    $ordersSql = "SELECT o.*, p.api_url, p.api_key 
                  FROM orders o 
                  JOIN providers p ON p.id = o.provider_id 
                  WHERE o.status IN ('pending', 'processing', 'inprogress') 
                    AND o.provider_order_id IS NOT NULL 
                  LIMIT 50";
    $orders = Database::fetchAll($ordersSql);

    if (!empty($orders)) {
        $grouped = [];
        foreach ($orders as $ord) {
            $grouped[$ord['provider_id']][] = $ord;
        }

        foreach ($grouped as $providerId => $providerOrders) {
            $first = $providerOrders[0];
            $adapter = new StandardProvider($first['api_url'], $first['api_key']);

            $map = [];
            foreach ($providerOrders as $o) {
                $map[$o['provider_order_id']] = $o;
            }

            $orderIds = array_keys($map);
            $statuses = $adapter->multiStatus($orderIds);

            if (empty($statuses) || isset($statuses['error'])) {
                $cronLog .= "[WARN] Provider #{$providerId} multi-status query failed.\n";
                continue;
            }

            foreach ($statuses as $remoteId => $data) {
                if (!isset($map[$remoteId]) || empty($data) || isset($data['error'])) continue;
                $currentOrder = $map[$remoteId];

                $remoteStatus = strtolower(trim($data['status'] ?? ''));
                $startCount = (int)($data['start_count'] ?? $currentOrder['start_count']);
                $remains = (int)($data['remains'] ?? $currentOrder['remains']);

                $newStatus = match ($remoteStatus) {
                    'completed'                  => ORDER_COMPLETED,
                    'processing'                 => ORDER_PROCESSING,
                    'inprogress', 'in progress'  => ORDER_IN_PROGRESS,
                    'partial'                    => ORDER_PARTIAL,
                    'canceled', 'cancelled'      => ORDER_CANCELED,
                    default                      => $currentOrder['status']
                };

                // Partial Order Refund calculation
                if ($newStatus === ORDER_PARTIAL && $currentOrder['status'] !== ORDER_PARTIAL) {
                    $delivered = max(0, $currentOrder['quantity'] - $remains);
                    $srv = Database::fetch("SELECT rate FROM services WHERE id = :id", [':id' => $currentOrder['service_id']]);
                    $rate = (float)($srv['rate'] ?? 0);
                    $actualCost = round(($delivered / 1000) * $rate, 4);
                    $refund = max(0, (float)$currentOrder['charge'] - $actualCost);

                    if ($refund > 0) {
                        Database::beginTransaction();
                        try {
                            $usr = Database::fetch("SELECT balance, spent FROM users WHERE id = :id", [':id' => $currentOrder['user_id']]);
                            $newBalance = (float)$usr['balance'] + $refund;

                            Database::update('users', [
                                'balance' => $newBalance,
                                'spent'   => max(0, (float)$usr['spent'] - $refund),
                            ], 'id = :id', [':id' => $currentOrder['user_id']]);

                            Database::insert('transactions', [
                                'user_id'       => $currentOrder['user_id'],
                                'amount'        => $refund,
                                'type'          => TXN_REFUND,
                                'description'   => "Partial Refund: Order #{$currentOrder['id']} ({$remains} remains)",
                                'balance_after' => $newBalance,
                            ]);

                            Database::update('orders', [
                                'status'      => ORDER_PARTIAL,
                                'start_count' => $startCount,
                                'remains'     => $remains,
                                'charge'      => $actualCost,
                            ], 'id = :id', [':id' => $currentOrder['id']]);

                            Database::commit();
                            $cronLog .= "[INFO] Processed partial refund for Order #{$currentOrder['id']}: \${$refund}\n";
                            continue;
                        } catch (\Exception $e) {
                            Database::rollBack();
                            $cronLog .= "[ERROR] Partial refund failed for Order #{$currentOrder['id']}: " . $e->getMessage() . "\n";
                        }
                    }
                }

                // Standard Status update
                Database::update('orders', [
                    'status'      => $newStatus,
                    'start_count' => $startCount,
                    'remains'     => $remains,
                ], 'id = :id', [':id' => $currentOrder['id']]);

                $cronLog .= "[UPDATE] Order #{$currentOrder['id']} updated to {$newStatus} (remains: {$remains})\n";
            }
        }
    } else {
        $cronLog .= "[INFO] No pending upstream orders to sync.\n";
    }
} catch (\Exception $e) {
    $cronLog .= "[ERROR] Order sync exception: " . $e->getMessage() . "\n";
}

// -----------------------------------------------------------------------------
// 2. Synchronize Provider Balances
// -----------------------------------------------------------------------------
try {
    $providers = Database::fetchAll("SELECT * FROM providers WHERE status = 1");
    foreach ($providers as $prov) {
        $adapter = new StandardProvider($prov['api_url'], $prov['api_key']);
        $bal = $adapter->balance();
        if (isset($bal['balance'])) {
            Database::update('providers', [
                'balance'  => (float)$bal['balance'],
                'currency' => $bal['currency'] ?? 'USD',
            ], 'id = :id', [':id' => $prov['id']]);
            $cronLog .= "[INFO] Provider #{$prov['id']} ({$prov['name']}) balance synced: {$bal['balance']} {$bal['currency']}\n";
        }
    }
} catch (\Exception $e) {
    $cronLog .= "[ERROR] Provider sync exception: " . $e->getMessage() . "\n";
}

$cronLog .= "[{$timestamp}] Master Cron execution completed successfully.\n\n";

// Write to storage logs
$logDir = PATH_STORAGE . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
@file_put_contents($logDir . '/cron_' . date('Y-m-d') . '.log', $cronLog, FILE_APPEND | LOCK_EX);

if ($isCli) {
    echo $cronLog;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $cronLog;
}
