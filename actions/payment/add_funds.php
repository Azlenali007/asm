<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: Add Funds / Deposit Process
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Validator;
use Core\Logger;

if (!Auth::check()) {
    redirect('/login.php');
}

if (!Csrf::validate()) {
    flash('error', 'Session expired. Please try again.');
    redirect('/user/add-funds.php');
}

$user = Auth::user();
$method = trim($_POST['method'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);

$v = Validator::make($_POST, [
    'method' => 'required',
    'amount' => 'required|numeric',
]);

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect('/user/add-funds.php');
}

$minDeposit = 5.00;
$maxDeposit = 5000.00;

if ($amount < $minDeposit) {
    flash('error', "Minimum deposit amount is " . money($minDeposit));
    redirect('/user/add-funds.php');
}

if ($amount > $maxDeposit) {
    flash('error', "Maximum deposit amount is " . money($maxDeposit));
    redirect('/user/add-funds.php');
}

// Generate transaction ID
$txnId = 'PAY_' . strtoupper(bin2hex(random_bytes(6)));

Database::beginTransaction();
try {
    $newBalance = (float)$user['balance'] + $amount;

    Database::insert('payments', [
        'user_id'        => $user['id'],
        'method'         => $method,
        'transaction_id' => $txnId,
        'amount'         => $amount,
        'fee'            => 0.00,
        'net_amount'     => $amount,
        'status'         => PAYMENT_COMPLETED,
        'raw_data'       => json_encode(['method' => $method, 'timestamp' => time()]),
    ]);

    Database::update('users', ['balance' => $newBalance], 'id = :id', [':id' => $user['id']]);

    Database::insert('transactions', [
        'user_id'       => $user['id'],
        'amount'        => $amount,
        'type'          => TXN_CREDIT,
        'description'   => "Deposit via {$method} ({$txnId})",
        'balance_after' => $newBalance,
    ]);

    Database::commit();

    Logger::audit("Deposit completed", ['user_id' => $user['id'], 'amount' => $amount, 'method' => $method, 'txn' => $txnId]);

    flash('success', "Deposit of " . money($amount) . " credited successfully to your account! Txn ID: {$txnId}");
    redirect('/user/transactions.php');
} catch (\Exception $e) {
    Database::rollBack();
    Logger::error("Deposit failed: " . $e->getMessage());
    flash('error', 'Deposit processing failed. Please try again.');
    redirect('/user/add-funds.php');
}
