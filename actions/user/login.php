<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: User Login
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\Validator;
use Core\RateLimiter;

if (Auth::check()) {
    redirect(Auth::isAdmin() ? '/admin/dashboard.php' : '/user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

if (!RateLimiter::check('user_login', 5, 900)) {
    flash('error', 'Too many failed login attempts. Please try again in 15 minutes.');
    redirect('/login.php');
}

if (!Csrf::validate()) {
    flash('error', 'Invalid form submission token. Please refresh.');
    redirect('/login.php');
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

$v = Validator::make($_POST, [
    'username' => 'required',
    'password' => 'required',
]);

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect('/login.php');
}

if (Auth::attempt($username, $password)) {
    RateLimiter::clear('user_login');
    flash('success', 'Welcome back, ' . htmlspecialchars(Auth::user()['username']) . '!');
    redirect(Auth::isAdmin() ? '/admin/dashboard.php' : '/user/dashboard.php');
} else {
    flash('error', 'Invalid username or password.');
    redirect('/login.php');
}
