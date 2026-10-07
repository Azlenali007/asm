<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: Dedicated Administrator Login Handler
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\RateLimiter;
use Core\Logger;
use Core\Session;

if (Auth::check() && Auth::isAdmin()) {
    redirect('/admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/login.php');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

Session::set('_old_inputs', ['username' => $username]);

// 1. Rate Limiting Check
if (!RateLimiter::check('admin_login', 5, 900)) {
    flash('error', 'Too many failed administrative login attempts. Please wait 15 minutes.');
    redirect('/admin/login.php');
}

// 2. CSRF Token Validation
if (!Csrf::validate()) {
    flash('error', 'Security token expired. Please refresh the page and try again.');
    redirect('/admin/login.php');
}

// 3. Validation
if (empty($username) || empty($password)) {
    flash('error', 'Please enter your administrator username and password.');
    redirect('/admin/login.php');
}

// 4. Authenticate Admin
try {
    if (Auth::attempt($username, $password)) {
        if (!Auth::isAdmin()) {
            Auth::logout();
            Logger::warning("Non-admin user attempted admin login", ['username' => $username]);
            flash('error', 'Access denied: Your account does not have administrator privileges.');
            redirect('/admin/login.php');
        }

        RateLimiter::clear('admin_login');
        Session::remove('_old_inputs');

        $user = Auth::user();
        Logger::audit("Admin logged in to control panel", ['user_id' => $user['id'], 'username' => $user['username']]);

        flash('success', 'Welcome to Master Admin Control, ' . htmlspecialchars($user['username']) . '!');
        redirect('/admin/dashboard.php');
    } else {
        flash('error', 'Invalid administrator credentials. Please check your username and password.');
        redirect('/admin/login.php');
    }
} catch (\Exception $e) {
    Logger::error("Admin login error: " . $e->getMessage());
    flash('error', 'Database connection issue. Please check server settings or run /install/');
    redirect('/admin/login.php');
}
