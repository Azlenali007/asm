<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Admin: Upstream Providers Integration & API Sync
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;
use Providers\Adapters\StandardProvider;

if (!Auth::check()) {
    flash('error', 'Please authenticate to access the admin panel.');
    redirect('/admin/login.php');
}

if (!Auth::isAdmin()) {
    flash('error', 'Access denied: Administrator privileges required.');
    redirect('/user/dashboard.php');
}

// Add/Edit Provider
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_provider') {
    $providerId = (int)($_POST['provider_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $apiUrl = trim($_POST['api_url'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name && $apiUrl && $apiKey) {
        $adapter = new StandardProvider($apiUrl, $apiKey);
        $balData = $adapter->balance();
        $balance = isset($balData['balance']) ? (float)$balData['balance'] : 0.0000;
        $currency = $balData['currency'] ?? 'USD';

        $data = [
            'name'     => $name,
            'api_url'  => $apiUrl,
            'api_key'  => $apiKey,
            'balance'  => $balance,
            'currency' => $currency,
            'status'   => $status,
        ];

        if ($providerId) {
            Database::update('providers', $data, 'id = :id', [':id' => $providerId]);
            flash('success', "Provider #{$providerId} updated and balance verified.");
        } else {
            $newId = Database::insert('providers', $data);
            flash('success', "New Provider #{$newId} connected! Current balance: \${$balance} {$currency}");
        }
    }
    redirect('/admin/providers/index.php');
}

$providers = Database::fetchAll("SELECT * FROM providers ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMM Providers - Admin ApexSMM</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-light panel-layout" x-data="{ sidebarOpen: false, modalOpen: false, editProv: {} }">

    <aside class="sidebar" :class="{ 'open': sidebarOpen }">
        <div class="sidebar-header">
            <a href="<?= url('admin/dashboard.php') ?>" class="brand-logo">
                <div class="logo-icon" style="background: linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <span>Apex<span>ADMIN</span></span>
            </a>
            <button class="btn-close-sidebar" @click="sidebarOpen = false">&times;</button>
        </div>

        <nav class="sidebar-nav">
            <a href="<?= url('admin/dashboard.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="<?= url('admin/orders/index.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                <span>All Orders</span>
            </a>
            <a href="<?= url('admin/services/index.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Services</span>
            </a>
            <a href="<?= url('admin/users/index.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>Users</span>
            </a>
            <a href="<?= url('admin/providers/index.php') ?>" class="nav-item active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                <span>Providers</span>
            </a>
            <a href="<?= url('admin/settings/index.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                <span>System Settings</span>
            </a>
            <div class="sidebar-divider"></div>
            <a href="<?= url('user/dashboard.php') ?>" class="nav-item">
                <span>&larr; Client Portal</span>
            </a>
        </nav>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <button class="btn-hamburger" @click="sidebarOpen = true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <div class="topbar-title">Upstream SMM Provider APIs</div>
            <div class="topbar-actions">
                <button class="btn btn-primary btn-sm" @click="editProv = { id: 0, status: 1 }; modalOpen = true;">+ Connect Provider</button>
            </div>
        </header>

        <div class="panel-body">
            <?= render_flashes() ?>

            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Provider Name</th>
                                    <th>API URL</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($providers)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                            No SMM providers connected yet. Connect any standard API v2 provider.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($providers as $p): ?>
                                        <tr>
                                            <td class="cell-id">#<?= $p['id'] ?></td>
                                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                            <td><code><?= htmlspecialchars($p['api_url']) ?></code></td>
                                            <td style="font-weight: 700; color: #10b981;"><?= money($p['balance']) ?> <?= $p['currency'] ?></td>
                                            <td><?= $p['status'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-fail">Inactive</span>' ?></td>
                                            <td style="text-align: right;">
                                                <button class="btn btn-xs btn-secondary" @click="editProv = <?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>; modalOpen = true;">Edit</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Provider Modal -->
    <div class="modal-backdrop" x-show="modalOpen" style="display: none;" @keydown.escape.window="modalOpen = false">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 x-text="editProv.id ? 'Edit Provider #' + editProv.id : 'Connect New SMM Provider'"></h3>
                <button type="button" class="btn-close-modal" @click="modalOpen = false">&times;</button>
            </div>
            <form method="POST" action="<?= url('admin/providers/index.php') ?>">
                <input type="hidden" name="action" value="save_provider">
                <input type="hidden" name="provider_id" :value="editProv.id">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Provider Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. JustAnotherPanel or Peakerr" x-model="editProv.name" required>
                    </div>

                    <div class="form-group">
                        <label>API URL</label>
                        <input type="url" name="api_url" class="form-control" placeholder="https://provider.com/api/v2" x-model="editProv.api_url" required>
                    </div>

                    <div class="form-group">
                        <label>API Key</label>
                        <input type="text" name="api_key" class="form-control" placeholder="API secret key" x-model="editProv.api_key" required>
                    </div>

                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; margin-top: 10px;">
                        <input type="checkbox" name="status" value="1" :checked="editProv.status == 1"> Provider Enabled
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" @click="modalOpen = false">Cancel</button>
                    <button type="submit" class="btn btn-primary">Test & Save Provider</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
