<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Authentication: Login Page
 */

require_once __DIR__ . '/bootstrap/app.php';

use Core\Auth;

if (Auth::check()) {
    redirect(Auth::isAdmin() ? '/admin/dashboard.php' : '/user/dashboard.php');
}

$error = flash('error');
$success = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - ApexSMM Enterprise</title>
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
                <h2>Welcome Back</h2>
                <p>Sign in to your account to manage orders and funds</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form action="/actions/user/login.php" method="POST" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username or email" required autofocus>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label for="password" style="margin: 0;">Password</label>
                        <a href="/forgot-password.php" style="font-size: 12px; color: var(--accent); text-decoration: none;">Forgot?</a>
                    </div>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    Sign In to Account
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account yet? <a href="/register.php">Sign up here</a>
            </div>

            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-muted); text-align: center;">
                Demo Credentials: <strong>demouser</strong> / <strong>Demo@123456</strong> (or admin)
            </div>
        </div>
    </div>
</body>
</html>
