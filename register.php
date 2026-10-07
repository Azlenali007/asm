<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Authentication: Register Page
 */

require_once __DIR__ . '/bootstrap/app.php';

use Core\Auth;

if (Auth::check()) {
    redirect('/user/dashboard.php');
}

$error = flash('error');
$success = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Free Account - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-dark auth-page">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <a href="/" class="brand-logo" style="justify-content: center; margin-bottom: 16px;">
                    <div class="logo-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                    <span>Apex<span>SMM</span></span>
                </a>
                <h2>Create Account</h2>
                <p>Instant access to wholesale SMM rates and API integration</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form action="/actions/user/register.php" method="POST" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Choose a unique username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    Register Free Account
                </button>
            </form>

            <div class="auth-footer">
                Already registered? <a href="/login.php">Sign in here</a>
            </div>
        </div>
    </div>
</body>
</html>
