<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Authentication: Password Reset Request
 */

require_once __DIR__ . '/bootstrap/app.php';

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if ($email) {
        flash('success', 'If an account exists with that email, a password reset link has been dispatched.');
    } else {
        flash('error', 'Please enter a valid email address.');
    }
    redirect('/forgot-password.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="theme-light auth-page">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Reset Password</h2>
                <p>Enter your account email to receive reset instructions</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>
            <div class="auth-footer">
                Remember your password? <a href="/login.php">Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
