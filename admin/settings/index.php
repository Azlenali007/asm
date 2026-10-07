<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Admin: Global System Settings
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check() || !Auth::isAdmin()) {
    redirect('/login.php');
}

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsToUpdate = [
        'site_name'        => trim($_POST['site_name'] ?? 'ApexSMM Enterprise'),
        'site_currency'    => trim($_POST['site_currency'] ?? '$'),
        'min_deposit'      => (float)($_POST['min_deposit'] ?? 5.00),
        'max_deposit'      => (float)($_POST['max_deposit'] ?? 5000.00),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
    ];

    foreach ($settingsToUpdate as $key => $val) {
        $exists = Database::fetchColumn("SELECT COUNT(*) FROM settings WHERE setting_key = :k", [':k' => $key]);
        if ($exists) {
            Database::update('settings', ['setting_value' => (string)$val], 'setting_key = :k', [':k' => $key]);
        } else {
            Database::insert('settings', ['setting_key' => $key, 'setting_value' => (string)$val]);
        }
    }

    flash('success', 'System settings updated successfully.');
    redirect('/admin/settings/index.php');
}

$rawSettings = Database::fetchAll("SELECT * FROM settings");
$settings = [];
foreach ($rawSettings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin ApexSMM</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-dark panel-layout" x-data="{ sidebarOpen: false }">

    <aside class="sidebar" :class="{ 'open': sidebarOpen }">
        <div class="sidebar-header">
            <a href="/admin/dashboard.php" class="brand-logo">
                <div class="logo-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <span>Apex<span>ADMIN</span></span>
            </a>
            <button class="btn-close-sidebar" @click="sidebarOpen = false">&times;</button>
        </div>

        <nav class="sidebar-nav">
            <a href="/admin/dashboard.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="/admin/orders/index.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                <span>All Orders</span>
            </a>
            <a href="/admin/services/index.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Services</span>
            </a>
            <a href="/admin/users/index.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>Users</span>
            </a>
            <a href="/admin/providers/index.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                <span>Providers</span>
            </a>
            <a href="/admin/settings/index.php" class="nav-item active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                <span>System Settings</span>
            </a>
        </nav>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <button class="btn-hamburger" @click="sidebarOpen = true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <div class="topbar-title">Global Panel Settings</div>
        </header>

        <div class="panel-body">
            <?php if ($flash = flash('success')): ?>
                <div class="alert alert-success"><?= e($flash) ?></div>
            <?php endif; ?>

            <div class="card" style="max-width: 680px;">
                <div class="card-header">
                    <h3>Panel Configuration</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Website Name</label>
                            <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name'] ?? 'ApexSMM Enterprise') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Display Currency Symbol</label>
                            <input type="text" name="site_currency" class="form-control" value="<?= e($settings['site_currency'] ?? '$') ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div class="form-group">
                                <label>Minimum Deposit ($)</label>
                                <input type="number" step="0.01" name="min_deposit" class="form-control" value="<?= e($settings['min_deposit'] ?? '5.00') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Maximum Deposit ($)</label>
                                <input type="number" step="0.01" name="max_deposit" class="form-control" value="<?= e($settings['max_deposit'] ?? '5000.00') ?>" required>
                            </div>
                        </div>

                        <div style="margin: 20px 0;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;">
                                <input type="checkbox" name="maintenance_mode" value="1" <?= (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] === '1') ? 'checked' : '' ?>>
                                <strong>Enable Maintenance Mode</strong> (Lock non-admin access)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Save Configuration Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
