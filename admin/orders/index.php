<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Admin: Orders Management & Status Control
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

// Handle Admin Quick Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $validStatuses = ['pending', 'processing', 'inprogress', 'completed', 'partial', 'canceled'];

    if ($orderId && in_array($newStatus, $validStatuses)) {
        Database::update('orders', ['status' => $newStatus], 'id = :id', [':id' => $orderId]);
        flash('success', "Order #{$orderId} status updated to {$newStatus}.");
    }
    redirect('/admin/orders/index.php');
}

$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));
$search = trim($_GET['search'] ?? '');

$sql = "SELECT o.*, u.username, s.name as service_name, p.name as provider_name 
        FROM orders o 
        JOIN users u ON u.id = o.user_id 
        JOIN services s ON s.id = o.service_id 
        LEFT JOIN providers p ON p.id = o.provider_id 
        WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND o.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (o.id = :search_id OR u.username LIKE :search_term OR o.link LIKE :search_term)";
    $params[':search_id'] = is_numeric($search) ? (int)$search : 0;
    $params[':search_term'] = '%' . $search . '%';
}

$sql .= " ORDER BY o.id DESC LIMIT 100";
$orders = Database::fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Orders - Admin ApexSMM</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-light panel-layout" x-data="{ sidebarOpen: false }">

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
            <a href="<?= url('admin/orders/index.php') ?>" class="nav-item active">
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
            <div class="topbar-title">Orders Administration</div>
        </header>

        <div class="panel-body">
            <?= render_flashes() ?>

            <div class="order-tabs-bar">
                <div class="tabs-group">
                    <a href="?status=all" class="tab-link <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
                    <a href="?status=pending" class="tab-link <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
                    <a href="?status=processing" class="tab-link <?= $statusFilter === 'processing' ? 'active' : '' ?>">Processing</a>
                    <a href="?status=inprogress" class="tab-link <?= $statusFilter === 'inprogress' ? 'active' : '' ?>">In Progress</a>
                    <a href="?status=completed" class="tab-link <?= $statusFilter === 'completed' ? 'active' : '' ?>">Completed</a>
                    <a href="?status=canceled" class="tab-link <?= $statusFilter === 'canceled' ? 'active' : '' ?>">Canceled</a>
                </div>

                <form method="GET" class="search-form">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search ID, user, link..." class="input-search">
                </form>
            </div>

            <div class="card" style="margin-top: 16px;">
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Service</th>
                                    <th>Target Link</th>
                                    <th>Qty</th>
                                    <th>Charge</th>
                                    <th>Provider</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Manual Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">No orders matching criteria.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $ord): ?>
                                        <tr>
                                            <td class="cell-id">#<?= $ord['id'] ?></td>
                                            <td><strong><?= e($ord['username']) ?></strong></td>
                                            <td style="max-width: 200px;"><?= htmlspecialchars($ord['service_name']) ?></td>
                                            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <a href="<?= e($ord['link']) ?>" target="_blank" style="color: var(--accent);"><?= e($ord['link']) ?></a>
                                            </td>
                                            <td><?= number_format($ord['quantity']) ?></td>
                                            <td style="font-weight: 600; color: #10b981;"><?= money($ord['charge']) ?></td>
                                            <td style="font-size: 12px;">
                                                <?= $ord['provider_name'] ? htmlspecialchars($ord['provider_name']) : 'Manual' ?>
                                                <div style="color: var(--text-muted);"><?= $ord['provider_order_id'] ? '#' . $ord['provider_order_id'] : '&mdash;' ?></div>
                                            </td>
                                            <td><?= status_badge($ord['status']) ?></td>
                                            <td style="text-align: right;">
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                    <select name="status" class="form-control" style="padding: 4px 8px; font-size: 12px; display: inline-block; width: auto;" onchange="this.form.submit()">
                                                        <option value="">Change...</option>
                                                        <option value="pending">Pending</option>
                                                        <option value="processing">Processing</option>
                                                        <option value="inprogress">In Progress</option>
                                                        <option value="completed">Completed</option>
                                                        <option value="partial">Partial</option>
                                                        <option value="canceled">Canceled</option>
                                                    </select>
                                                </form>
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
</body>
</html>
