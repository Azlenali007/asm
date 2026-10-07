<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: User Registration
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Validator;
use Core\Security;
use Core\RateLimiter;

if (Auth::check()) {
    redirect('/user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/register.php');
}

if (!RateLimiter::check('user_register', 10, 3600)) {
    flash('error', 'Too many accounts registered from your network. Try again later.');
    redirect('/register.php');
}

if (!Csrf::validate()) {
    flash('error', 'Session expired. Please refresh and try again.');
    redirect('/register.php');
}

$v = Validator::make($_POST, [
    'username'         => 'required|min:3|max:30|unique:users,username',
    'email'            => 'required|email|unique:users,email',
    'password'         => 'required|min:6',
    'confirm_password' => 'required|matches:password',
]);

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect('/register.php');
}

$username = Security::clean($_POST['username']);
$email = strtolower(trim($_POST['email']));
$password = Auth::hashPassword($_POST['password']);
$apiKey = Security::generateApiKey();
$refCode = strtoupper(bin2hex(random_bytes(4)));

try {
    $userId = Database::insert('users', [
        'username'      => $username,
        'email'         => $email,
        'password'      => $password,
        'role'          => ROLE_USER,
        'balance'       => 0.0000,
        'spent'         => 0.0000,
        'status'        => USER_ACTIVE,
        'api_key'       => $apiKey,
        'referral_code' => $refCode,
    ]);

    Auth::attempt($username, $_POST['password']);
    flash('success', 'Your account has been registered successfully! Welcome to ApexSMM.');
    redirect('/user/dashboard.php');
} catch (\Exception $e) {
    flash('error', 'Registration failed. Please try again.');
    redirect('/register.php');
}
