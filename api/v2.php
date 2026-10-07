<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Standard Reseller API v2 Endpoint
 * Compatible with all standard SMM API clients
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;
use Core\RateLimiter;
use Core\Logger;

// Ensure JSON header
header('Content-Type: application/json; charset=utf-8');

// Rate limiting
if (!RateLimiter::check('api_v2', 120, 60)) {
    json_response(['error' => 'Too many requests. Rate limit is 120 req/min.'], 429);
}

// Check API Key
$key = $_REQUEST['key'] ?? '';
if (empty($key)) {
    json_response(['error' => 'API key is required.'], 401);
}

$user = Auth::authenticateApiKey($key);
if (!$user) {
    json_response(['error' => 'Invalid or inactive API key.'], 403);
}

$action = strtolower(trim($_REQUEST['action'] ?? ''));

switch ($action) {
    // ----------------------------------------------------
    // Action: services
    // ----------------------------------------------------
    case 'services':
        $sql = "SELECT s.id as service, s.name, c.name as category, s.rate, s.min_quantity as min, s.max_quantity as max, s.type, s.refill, s.cancel
                FROM services s
                JOIN categories c ON c.id = s.category_id
                WHERE s.status = 1 AND c.status = 1
                ORDER BY c.sort_order ASC, s.sort_order ASC";
        $services = Database::fetchAll($sql);

        // Apply custom rates if user has them
        $customRates = !empty($user['custom_rates']) ? json_decode($user['custom_rates'], true) : [];

        $formatted = array_map(function ($s) use ($customRates) {
            $rate = (float)$s['rate'];
            if (isset($customRates[$s['service']])) {
                $rate = (float)$customRates[$s['service']];
            }
            return [
                'service'  => (int)$s['service'],
                'name'     => $s['name'],
                'type'     => $s['type'],
                'category' => $s['category'],
                'rate'     => number_format($rate, 4, '.', ''),
                'min'      => (int)$s['min'],
                'max'      => (int)$s['max'],
                'refill'   => (bool)$s['refill'],
                'cancel'   => (bool)$s['cancel'],
            ];
        }, $services);

        json_response($formatted);
        break;

    // ----------------------------------------------------
    // Action: add
    // ----------------------------------------------------
    case 'add':
        $serviceId = (int)($_REQUEST['service'] ?? 0);
        $link      = trim($_REQUEST['link'] ?? '');
        $quantity  = (int)($_REQUEST['quantity'] ?? 0);
        $comments  = trim($_REQUEST['comments'] ?? '');

        if (!$serviceId || empty($link)) {
            json_response(['error' => 'Invalid service or link.'], 400);
        }

        $service = Database::fetch("SELECT * FROM services WHERE id = :id AND status = 1", [':id' => $serviceId]);
        if (!$service) {
            json_response(['error' => 'Service not found or disabled.'], 404);
        }

        // Custom comments check
        if ($service['type'] === SERVICE_TYPE_CUSTOM_COMMENTS) {
            $lines = array_filter(array_map('trim', explode("\n", $comments)));
            $quantity = count($lines);
            if ($quantity === 0) {
                json_response(['error' => 'Please provide at least one comment.'], 400);
            }
        }

        if ($quantity < $service['min_quantity']) {
            json_response(['error' => "Quantity is lower than minimum allowed: {$service['min_quantity']}"], 400);
        }
        if ($quantity > $service['max_quantity']) {
            json_response(['error' => "Quantity exceeds maximum allowed: {$service['max_quantity']}"], 400);
        }

        // Calculate rate
        $rate = (float)$service['rate'];
        $customRates = !empty($user['custom_rates']) ? json_decode($user['custom_rates'], true) : [];
        if (isset($customRates[$serviceId])) {
            $rate = (float)$customRates[$serviceId];
        }

        $charge = round(($quantity / 1000) * $rate, 4);

        // Check user balance
        if ((float)$user['balance'] < $charge) {
            json_response(['error' => 'Insufficient funds on your account.'], 400);
        }

        // Database transaction for deduction and order creation
        Database::beginTransaction();
        try {
            // Deduct balance
            $newBalance = (float)$user['balance'] - $charge;
            $newSpent = (float)$user['spent'] + $charge;

            Database::update('users', [
                'balance' => $newBalance,
                'spent'   => $newSpent,
            ], 'id = :id', [':id' => $user['id']]);

            // Create order
            $orderId = Database::insert('orders', [
                'user_id'         => $user['id'],
                'service_id'      => $serviceId,
                'provider_id'     => $service['provider_id'],
                'link'            => $link,
                'quantity'        => $quantity,
                'remains'         => $quantity,
                'charge'          => $charge,
                'status'          => ORDER_PENDING,
                'order_type'      => $service['type'],
                'custom_comments' => $comments ?: null,
            ]);

            // Record transaction
            Database::insert('transactions', [
                'user_id'       => $user['id'],
                'amount'        => -$charge,
                'type'          => TXN_DEBIT,
                'description'   => "API Order #{$orderId} - Service #{$serviceId}",
                'balance_after' => $newBalance,
            ]);

            Database::commit();

            Logger::audit("API Order placed", ['user_id' => $user['id'], 'order_id' => $orderId, 'charge' => $charge]);

            json_response(['order' => (int)$orderId]);
        } catch (\Exception $e) {
            Database::rollBack();
            Logger::error("API Order placement failed: " . $e->getMessage());
            json_response(['error' => 'Failed to process order. Please try again.'], 500);
        }
        break;

    // ----------------------------------------------------
    // Action: status / multi-status
    // ----------------------------------------------------
    case 'status':
        if (!empty($_REQUEST['orders'])) {
            $orderIds = array_filter(array_map('intval', explode(',', $_REQUEST['orders'])));
            if (empty($orderIds)) {
                json_response(['error' => 'Invalid orders list.'], 400);
            }

            $inClause = implode(',', $orderIds);
            $rows = Database::fetchAll("SELECT id, charge, start_count, status, remains, currency FROM orders WHERE id IN ({$inClause}) AND user_id = :uid", [':uid' => $user['id']]);
            $res = [];
            foreach ($rows as $r) {
                $res[$r['id']] = [
                    'charge'      => number_format((float)$r['charge'], 4, '.', ''),
                    'start_count' => (string)$r['start_count'],
                    'status'      => strtoupper($r['status']),
                    'remains'     => (string)$r['remains'],
                    'currency'    => 'USD',
                ];
            }
            json_response($res);
        } elseif (!empty($_REQUEST['order'])) {
            $orderId = (int)$_REQUEST['order'];
            $order = Database::fetch("SELECT * FROM orders WHERE id = :id AND user_id = :uid", [
                ':id'  => $orderId,
                ':uid' => $user['id']
            ]);

            if (!$order) {
                json_response(['error' => 'Order not found.'], 404);
            }

            json_response([
                'charge'      => number_format((float)$order['charge'], 4, '.', ''),
                'start_count' => (string)$order['start_count'],
                'status'      => strtoupper($order['status']),
                'remains'     => (string)$order['remains'],
                'currency'    => 'USD',
            ]);
        } else {
            json_response(['error' => 'Order ID is required.'], 400);
        }
        break;

    // ----------------------------------------------------
    // Action: balance
    // ----------------------------------------------------
    case 'balance':
        json_response([
            'balance'  => number_format((float)$user['balance'], 4, '.', ''),
            'currency' => 'USD',
        ]);
        break;

    // ----------------------------------------------------
    // Action: refill
    // ----------------------------------------------------
    case 'refill':
        $orderId = (int)($_REQUEST['order'] ?? 0);
        $order = Database::fetch("SELECT * FROM orders WHERE id = :id AND user_id = :uid", [
            ':id'  => $orderId,
            ':uid' => $user['id']
        ]);

        if (!$order) {
            json_response(['error' => 'Order not found.'], 404);
        }

        // Return standard refill reference
        json_response(['refill' => 'REF_' . $orderId . '_' . time()]);
        break;

    default:
        json_response(['error' => 'Invalid or unknown action requested.'], 400);
        break;
}
