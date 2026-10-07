<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Authentication: Register Page
 */

require_once __DIR__ . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Security;
use Core\RateLimiter;
use Core\Logger;
use Core\Session;

if (Auth::check()) {
    redirect(Auth::isAdmin() ? '/admin/dashboard.php' : '/user/dashboard.php');
}

// Handle Form Submission directly in register.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    // Preserve inputs for repopulation
    Session::set('_old_inputs', [
        'username' => $username,
        'email'    => $email,
    ]);

    // 1. Rate limiting check (10 attempts per IP per hour)
    if (!RateLimiter::check('user_register', 15, 3600)) {
        flash('error', 'Too many registration attempts from your IP. Please try again later.');
    }
    // 2. CSRF verification
    elseif (!Csrf::validate()) {
        flash('error', 'Security session expired. Please refresh the page and try again.');
    }
    // 3. Field validation
    elseif (empty($username)) {
        flash('error', 'Username is required.');
    }
    elseif (strlen($username) < 3 || strlen($username) > 30) {
        flash('error', 'Username must be between 3 and 30 characters.');
    }
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        flash('error', 'Username can only contain letters, numbers, and underscores.');
    }
    elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
    }
    elseif (strlen($password) < 6) {
        flash('error', 'Password does not meet the requirements. Minimum 6 characters required.');
    }
    elseif ($password !== $confirmPassword) {
        flash('error', 'Passwords do not match. Please re-enter.');
    }
    else {
        // 4. Duplicate checks via PDO prepared statement
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
            } else {
                // 5. Secure password hashing
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $apiKey = 'smm_' . bin2hex(random_bytes(28));
                $refCode = strtoupper(bin2hex(random_bytes(4)));

                // 6. Safe database insert using prepared statement
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

                // Audit log
                Logger::audit("New user registered", ['user_id' => $userId, 'username' => $username]);

                // Set flash message for login page
                flash('success', 'Registration successful! You can now log in to your account.');

                // Redirect to login page
                redirect('/login.php');
            }
        } catch (\Exception $e) {
            Logger::error("Registration failed with database error: " . $e->getMessage());
            flash('error', 'Registration failed. Please try again or contact support.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Free Account - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-light auth-page">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <a href="<?= url('/') ?>" class="brand-logo" style="justify-content: center; margin-bottom: 16px;">
                    <div class="logo-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </div>
                    <span>Apex<span>SMM</span></span>
                </a>
                <h2>Create Account</h2>
                <p>Instant access to wholesale SMM rates and API integration</p>
            </div>

            <!-- Flash Notifications (Success & Validation Errors) -->
            <?= render_flashes() ?>

            <form action="<?= url('register.php') ?>" method="POST" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username (3-30 characters)" value="<?= e(old('username')) ?>" required autofocus>
                    <span class="field-help">Letters, numbers, and underscores only</span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" value="<?= e(old('email')) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                    Register Free Account
                </button>
            </form>

            <div class="auth-footer">
                Already registered? <a href="<?= url('login.php') ?>">Sign in here</a>
            </div>
        </div>
    </div>
</body>
</html>
