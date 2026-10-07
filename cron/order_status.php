<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Automated Cron: Order Status Synchronizer
 * Run via crontab: * * * * * php /path/to/cron/order_status.php >> /path/to/storage/logs/cron.log 2>&1
 */

if (php_sapi_name() !== 'cli' && empty($_GET['cron_key'])) {
    http_response_code(403);
    die("Access denied.");
}

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Database;
use Core\Logger;
use Providers\Adapters\StandardProvider;

Logger::info("Starting automated order status sync cron...");

// Fetch orders pending/processing/inprogress that have a provider order ID
$sql = "SELECT o.*, p.api_url, p.api_key 
        FROM orders o 
        JOIN providers p ON p.id = o.provider_id 
        WHERE o.status IN ('pending', 'processing', 'inprogress') 
          AND o.provider_order_id IS NOT NULL 
        LIMIT 50";

$orders = Database::fetchAll($sql);

if (empty($orders)) {
    echo "No pending provider orders found.\n";
    exit;
}

$groupedByProvider = [];
foreach ($orders as $order) {
    $groupedByProvider[$order['provider_id']][] = $order;
}

foreach ($groupedByProvider as $providerId => $providerOrders) {
    $first = $providerOrders[0];
    $adapter = new StandardProvider($first['api_url'], $first['api_key']);

    $orderIdsMap = [];
    foreach ($providerOrders as $o) {
        $orderIdsMap[$o['provider_order_id']] = $o;
    }

    $providerOrderIds = array_keys($orderIdsMap);
    $results = $adapter->multiStatus($providerOrderIds);

    if (empty($results) || isset($results['error'])) {
        Logger::warning("Could not sync with provider #{$providerId}: " . ($results['error'] ?? 'empty response'));
        continue;
    }

    foreach ($results as $pOrderId => $statusData) {
        if (!isset($orderIdsMap[$pOrderId])) continue;
        $order = $orderIdsMap[$pOrderId];

        if (empty($statusData) || isset($statusData['error'])) continue;

        $pStatus = strtolower($statusData['status'] ?? '');
        $startCount = (int)($statusData['start_count'] ?? $order['start_count']);
        $remains = (int)($statusData['remains'] ?? $order['remains']);

        $newStatus = match ($pStatus) {
            'completed'   => ORDER_COMPLETED,
            'processing'  => ORDER_PROCESSING,
            'inprogress', 'in progress' => ORDER_IN_PROGRESS,
            'partial'     => ORDER_PARTIAL,
            'canceled', 'cancelled' => ORDER_CANCELED,
            default       => $order['status']
        };

        // If partial, calculate partial refund for remaining units
        if ($newStatus === ORDER_PARTIAL && $order['status'] !== ORDER_PARTIAL) {
            $delivered = max(0, $order['quantity'] - $remains);
            $service = Database::fetch("SELECT rate FROM services WHERE id = :id", [':id' => $order['service_id']]);
            $rate = (float)($service['rate'] ?? 0);
            $actualCost = round(($delivered / 1000) * $rate, 4);
            $refund = max(0, (float)$order['charge'] - $actualCost);

            if ($refund > 0) {
                Database::beginTransaction();
                try {
                    $user = Database::fetch("SELECT balance, spent FROM users WHERE id = :id", [':id' => $order['user_id']]);
                    $newBalance = (float)$user['balance'] + $refund;

                    Database::update('users', [
                        'balance' => $newBalance,
                        'spent'   => (float)$user['spent'] - $refund,
                    ], 'id = :id', [':id' => $order['user_id']]);

                    Database::insert('transactions', [
                        'user_id'       => $order['user_id'],
                        'amount'        => $refund,
                        'type'          => TXN_REFUND,
                        'description'   => "Partial Refund for Order #{$order['id']} ({$remains} remains)",
                        'balance_after' => $newBalance,
                    ]);

                    Database::update('orders', [
                        'status'      => ORDER_PARTIAL,
                        'start_count' => $startCount,
                        'remains'     => $remains,
                        'charge'      => $actualCost,
                    ], 'id = :id', [':id' => $order['id']]);

                    Database::commit();
                    Logger::info("Processed partial refund for order #{$order['id']}: \${$refund}");
                    continue;
                } catch (\Exception $e) {
                    Database::rollBack();
                    Logger::error("Failed partial refund transaction: " . $e->getMessage());
                }
            }
        }

        // Standard status update
        Database::update('orders', [
            'status'      => $newStatus,
            'start_count' => $startCount,
            'remains'     => $remains,
        ], 'id = :id', [':id' => $order['id']]);

        echo "Updated Order #{$order['id']} => {$newStatus}\n";
    }
}

Logger::info("Cron order status sync complete.");
