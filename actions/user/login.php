<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: User Login Handler
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\RateLimiter;
use Core\Logger;
use Core\Session;

if (Auth::check()) {
    redirect(Auth::isAdmin() ? '/admin/dashboard.php' : '/user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Save username for old input
Session::set('_old_inputs', ['username' => $username]);

// 1. Rate Limiting Check
if (!RateLimiter::check('user_login', 7, 900)) {
    flash('error', 'Too many failed login attempts. Please wait 15 minutes before trying again.');
    redirect('/login.php');
}

// 2. CSRF Token Validation
if (!Csrf::validate()) {
    flash('error', 'Security token expired. Please refresh the page and try again.');
    redirect('/login.php');
}

// 3. Validation
if (empty($username)) {
    flash('error', 'Please enter your username or email address.');
    redirect('/login.php');
}

if (empty($password)) {
    flash('error', 'Please enter your password.');
    redirect('/login.php');
}

// 4. Attempt Authentication with PDO
try {
    if (Auth::attempt($username, $password)) {
        RateLimiter::clear('user_login');
        Session::remove('_old_inputs');

        $user = Auth::user();
        flash('success', 'Welcome back, ' . htmlspecialchars($user['username']) . '!');

        // Role-based redirection
        if (Auth::isAdmin()) {
            redirect('/admin/dashboard.php');
        } else {
            redirect('/user/dashboard.php');
        }
    } else {
        flash('error', 'Invalid username or password. Please verify your credentials.');
        redirect('/login.php');
    }
} catch (\Exception $e) {
    Logger::error("Login exception: " . $e->getMessage());
    flash('error', 'Database connection issue. Please verify database settings or run /install/');
    redirect('/login.php');
}
