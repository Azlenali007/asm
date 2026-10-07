<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: Create New Order
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Validator;
use Core\Logger;
use Providers\Adapters\StandardProvider;

if (!Auth::check()) {
    redirect('/login.php');
}

if (!Csrf::validate()) {
    flash('error', 'Security session expired. Please refresh and try again.');
    redirect('/user/new-order.php');
}

$user = Auth::user();
$serviceId = (int)($_POST['service_id'] ?? 0);
$link = trim($_POST['link'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 0);
$comments = trim($_POST['comments'] ?? '');

$v = Validator::make($_POST, [
    'service_id' => 'required|numeric',
    'link'       => 'required',
]);

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect('/user/new-order.php');
}

$service = Database::fetch("SELECT * FROM services WHERE id = :id AND status = 1", [':id' => $serviceId]);
if (!$service) {
    flash('error', 'The requested service is not currently available.');
    redirect('/user/new-order.php');
}

// Custom comments handling
if ($service['type'] === SERVICE_TYPE_CUSTOM_COMMENTS) {
    $commentLines = array_filter(array_map('trim', explode("\n", $comments)));
    $quantity = count($commentLines);
    if ($quantity === 0) {
        flash('error', 'Please enter at least one custom comment line.');
        redirect('/user/new-order.php');
    }
}

if ($quantity < $service['min_quantity']) {
    flash('error', "Quantity cannot be less than minimum allowed: {$service['min_quantity']}");
    redirect('/user/new-order.php');
}

if ($quantity > $service['max_quantity']) {
    flash('error', "Quantity cannot exceed maximum allowed: {$service['max_quantity']}");
    redirect('/user/new-order.php');
}

// User-specific custom rate check
$rate = (float)$service['rate'];
$customRates = !empty($user['custom_rates']) ? json_decode($user['custom_rates'], true) : [];
if (isset($customRates[$serviceId])) {
    $rate = (float)$customRates[$serviceId];
}

$charge = round(($quantity / 1000) * $rate, 4);

if ((float)$user['balance'] < $charge) {
    flash('error', 'Insufficient balance. Please add funds to place this order.');
    redirect('/user/add-funds.php');
}

// Begin transaction
Database::beginTransaction();
try {
    $newBalance = (float)$user['balance'] - $charge;
    $newSpent = (float)$user['spent'] + $charge;

    Database::update('users', [
        'balance' => $newBalance,
        'spent'   => $newSpent,
    ], 'id = :id', [':id' => $user['id']]);

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

    Database::insert('transactions', [
        'user_id'       => $user['id'],
        'amount'        => -$charge,
        'type'          => TXN_DEBIT,
        'description'   => "Order #{$orderId} ({$service['name']})",
        'balance_after' => $newBalance,
    ]);

    // If connected to external provider, dispatch automatically
    if (!empty($service['provider_id']) && !empty($service['provider_service_id'])) {
        $prov = Database::fetch("SELECT * FROM providers WHERE id = :id AND status = 1", [':id' => $service['provider_id']]);
        if ($prov) {
            $adapter = new StandardProvider($prov['api_url'], $prov['api_key']);
            $params = [
                'service'  => $service['provider_service_id'],
                'link'     => $link,
                'quantity' => $quantity,
            ];
            if ($comments) {
                $params['comments'] = $comments;
            }

            $resp = $adapter->add($params);
            if (!empty($resp['order'])) {
                Database::update('orders', [
                    'provider_order_id' => (string)$resp['order'],
                    'status'            => ORDER_PROCESSING,
                ], 'id = :id', [':id' => $orderId]);
            }
        }
    }

    Database::commit();

    flash('success', "Order #{$orderId} placed successfully! Amount charged: " . money($charge));
    redirect('/user/orders.php');
} catch (\Exception $e) {
    Database::rollBack();
    Logger::error("Order creation failed: " . $e->getMessage());
    flash('error', 'Failed to place order due to server error. Please try again.');
    redirect('/user/new-order.php');
}
