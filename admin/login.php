<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Administrator Login Portal
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;

if (Auth::check() && Auth::isAdmin()) {
    redirect('/admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Sign In - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-light auth-page">
    <div class="auth-wrapper">
        <div class="auth-card" style="border-top: 4px solid var(--accent);">
            <div class="auth-header">
                <a href="<?= url('/') ?>" class="brand-logo" style="justify-content: center; margin-bottom: 16px;">
                    <div class="logo-icon" style="background: linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <span>Apex<span>ADMIN</span></span>
                </a>
                <h2>Admin Control Center</h2>
                <p>Restricted access for system administrators only</p>
            </div>

            <!-- Flash Notifications -->
            <?= render_flashes() ?>

            <form action="<?= url('actions/admin/login.php') ?>" method="POST" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">Administrator Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter admin username" value="<?= e(old('username')) ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Security Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter admin password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                    Authenticate as Administrator
                </button>
            </form>

            <div class="auth-footer">
                <a href="<?= url('login.php') ?>">&larr; Return to Client Login</a>
            </div>

            <div class="credentials-box">
                <div style="font-weight: 600; color: #6d28d9; margin-bottom: 2px;">Default Admin Account</div>
                <div>Username: <strong>admin</strong></div>
                <div>Password: <strong>Admin@123456</strong></div>
            </div>
        </div>
    </div>
</body>
</html>
