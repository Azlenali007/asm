<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Admin: Users Management
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    flash('error', 'Please authenticate to access the admin panel.');
    redirect('/admin/login.php');
}

if (!Auth::isAdmin()) {
    flash('error', 'Access denied: Administrator privileges required.');
    redirect('/user/dashboard.php');
}

// Handle Balance / Status Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $balance = (float)($_POST['balance'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $role = $_POST['role'] ?? 'user';

    if ($userId) {
        Database::update('users', [
            'balance' => $balance,
            'status'  => in_array($status, ['active', 'suspended', 'banned']) ? $status : 'active',
            'role'    => in_array($role, ['user', 'staff', 'admin']) ? $role : 'user',
        ], 'id = :id', [':id' => $userId]);

        flash('success', "User #{$userId} updated successfully.");
    }
    redirect('/admin/users/index.php');
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (username LIKE :search OR email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY id DESC LIMIT 100";
$users = Database::fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Administration - Admin ApexSMM</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-light panel-layout" x-data="{ sidebarOpen: false, modalOpen: false, editUser: {} }">

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
            <a href="<?= url('admin/users/index.php') ?>" class="nav-item active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>Users</span>
            </a>
            <a href="<?= url('admin/providers/index.php') ?>" class="nav-item">
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
            <div class="topbar-title">Users & Clients Administration</div>
            <div class="topbar-actions">
                <form method="GET" class="search-form">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search user or email..." class="input-search">
                </form>
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
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Balance</th>
                                    <th>Spent</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="cell-id">#<?= $u['id'] ?></td>
                                        <td><strong><?= e($u['username']) ?></strong></td>
                                        <td style="color: var(--text-muted); font-size: 13px;"><?= e($u['email']) ?></td>
                                        <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-default' ?>"><?= strtoupper($u['role']) ?></span></td>
                                        <td style="font-weight: 600; color: #10b981;"><?= money($u['balance']) ?></td>
                                        <td style="color: var(--text-muted);"><?= money($u['spent']) ?></td>
                                        <td><?= status_badge($u['status']) ?></td>
                                        <td style="font-size: 12px; color: var(--text-muted);"><?= format_date($u['created_at'], 'M d, Y') ?></td>
                                        <td style="text-align: right;">
                                            <button class="btn btn-xs btn-secondary" @click="editUser = <?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>; modalOpen = true;">Edit / Balance</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- User Edit Modal -->
    <div class="modal-backdrop" x-show="modalOpen" style="display: none;" @keydown.escape.window="modalOpen = false">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Edit User #<span x-text="editUser.id"></span> (<span x-text="editUser.username"></span>)</h3>
                <button type="button" class="btn-close-modal" @click="modalOpen = false">&times;</button>
            </div>
            <form method="POST" action="<?= url('admin/users/index.php') ?>">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" :value="editUser.id">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Account Balance (USD)</label>
                        <input type="number" step="0.0001" name="balance" class="form-control" x-model="editUser.balance" required>
                    </div>

                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="status" class="form-control" x-model="editUser.status">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control" x-model="editUser.role">
                            <option value="user">User / Client</option>
                            <option value="staff">Staff Member</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" @click="modalOpen = false">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
