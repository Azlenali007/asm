<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Dedicated Cancel Order API Route
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

header('Content-Type: application/json; charset=utf-8');

$key = $_REQUEST['key'] ?? '';
$user = Auth::authenticateApiKey($key);
if (!$user) {
    json_response(['error' => 'Invalid or inactive API key.'], 403);
}

$orderId = (int)($_REQUEST['order'] ?? 0);
if (!$orderId) {
    json_response(['error' => 'Order ID is required.'], 400);
}

$order = Database::fetch("SELECT o.*, s.cancel as can_cancel FROM orders o JOIN services s ON s.id = o.service_id WHERE o.id = :id AND o.user_id = :uid", [
    ':id'  => $orderId,
    ':uid' => $user['id']
]);

if (!$order) {
    json_response(['error' => 'Order not found.'], 404);
}

if (!$order['can_cancel']) {
    json_response(['error' => 'Cancellation is not supported for this service.'], 400);
}

if ($order['status'] !== ORDER_PENDING) {
    json_response(['error' => 'Only pending orders can be canceled.'], 400);
}

// Cancel and refund user
Database::beginTransaction();
try {
    Database::update('orders', ['status' => ORDER_CANCELED], 'id = :id', [':id' => $orderId]);
    $refundAmount = (float)$order['charge'];
    $newBalance = (float)$user['balance'] + $refundAmount;

    Database::update('users', [
        'balance' => $newBalance,
        'spent'   => (float)$user['spent'] - $refundAmount
    ], 'id = :id', [':id' => $user['id']]);

    Database::insert('transactions', [
        'user_id'       => $user['id'],
        'amount'        => $refundAmount,
        'type'          => TXN_REFUND,
        'description'   => "Order #{$orderId} Canceled Refund",
        'balance_after' => $newBalance
    ]);

    Database::commit();
    json_response(['cancel' => 'ok', 'refunded' => $refundAmount]);
} catch (\Exception $e) {
    Database::rollBack();
    json_response(['error' => 'Could not cancel order at this moment.'], 500);
}
