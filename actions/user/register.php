<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: User Registration Handler
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Security;
use Core\RateLimiter;
use Core\Logger;
use Core\Session;

if (Auth::check()) {
    redirect('/user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/register.php');
}

// Store old inputs for repopulation
$username = trim((string)($_POST['username'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

Session::set('_old_inputs', [
    'username' => $username,
    'email'    => $email,
]);

// 1. Rate limiting
if (!RateLimiter::check('user_register', 15, 3600)) {
    flash('error', 'Too many registration attempts from your IP. Please try again later.');
    redirect('/register.php');
}

// 2. CSRF verification
if (!Csrf::validate()) {
    flash('error', 'Security session expired. Please refresh the page and try again.');
    redirect('/register.php');
}

// 3. Field validation
if (empty($username)) {
    flash('error', 'Username is required.');
    redirect('/register.php');
}

if (strlen($username) < 3 || strlen($username) > 30) {
    flash('error', 'Username must be between 3 and 30 characters.');
    redirect('/register.php');
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    flash('error', 'Username can only contain letters, numbers, and underscores.');
    redirect('/register.php');
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Please enter a valid email address.');
    redirect('/register.php');
}

if (strlen($password) < 6) {
    flash('error', 'Password does not meet the requirements. Minimum 6 characters required.');
    redirect('/register.php');
}

if ($password !== $confirmPassword) {
    flash('error', 'Passwords do not match. Please re-enter.');
    redirect('/register.php');
}

// 4. Check duplicate username & email via PDO
try {
    $existingUser = Database::fetch(
        "SELECT id, username, email FROM users WHERE LOWER(username) = LOWER(:u) OR LOWER(email) = LOWER(:e) LIMIT 1",
        [':u' => $username, ':e' => $email]
    );

    if ($existingUser) {
        if (strtolower($existingUser['username']) === strtolower($username)) {
            flash('error', 'Username already exists. Please choose a different username.');
        } else {
            flash('error', 'Email already exists. Please sign in or use another email.');
        }
        redirect('/register.php');
    }

    // 5. Hash password securely
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $apiKey = 'smm_' . bin2hex(random_bytes(28));
    $refCode = strtoupper(bin2hex(random_bytes(4)));

    // 6. Insert new user safely using PDO prepared statement
    $userId = Database::insert('users', [
        'username'      => $username,
        'email'         => $email,
        'password'      => $hashedPassword,
        'role'          => ROLE_USER,
        'balance'       => 0.0000,
        'spent'         => 0.0000,
        'status'        => USER_ACTIVE,
        'api_key'       => $apiKey,
        'referral_code' => $refCode,
    ]);

    Logger::audit("New user registered", ['user_id' => $userId, 'username' => $username]);

    // 7. Flash success message and redirect
    flash('success', 'Registration successful! You can now log in to your account.');
    redirect('/login.php');

} catch (\Exception $e) {
    Logger::error("Registration failed with database error: " . $e->getMessage());
    flash('error', 'Registration failed. Please try again or contact support.');
    redirect('/register.php');
}
