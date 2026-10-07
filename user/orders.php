<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * User: Orders History
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();
$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));
$search = trim($_GET['search'] ?? '');

$sql = "SELECT o.*, s.name as service_name, s.refill as can_refill, s.cancel as can_cancel 
        FROM orders o 
        JOIN services s ON s.id = o.service_id 
        WHERE o.user_id = :uid";
$params = [':uid' => $user['id']];

if ($statusFilter !== 'all') {
    $sql .= " AND o.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (o.id = :search_id OR o.link LIKE :search_term)";
    $params[':search_id'] = is_numeric($search) ? (int)$search : 0;
    $params[':search_term'] = '%' . $search . '%';
}

$sql .= " ORDER BY o.id DESC LIMIT 100";
$orders = Database::fetchAll($sql, $params);

// Count breakdown for tabs
$counts = [
    'all'        => (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid", [':uid' => $user['id']]),
    'pending'    => (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status = 'pending'", [':uid' => $user['id']]),
    'inprogress' => (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status IN ('processing', 'inprogress')", [':uid' => $user['id']]),
    'completed'  => (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status = 'completed'", [':uid' => $user['id']]),
    'partial'    => (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status = 'partial'", [':uid' => $user['id']]),
    'canceled'   => (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status = 'canceled'", [':uid' => $user['id']]),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-dark panel-layout" x-data="{ sidebarOpen: false }">

    <aside class="sidebar" :class="{ 'open': sidebarOpen }">
        <div class="sidebar-header">
            <a href="/user/dashboard.php" class="brand-logo">
                <div class="logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                <span>Apex<span>SMM</span></span>
            </a>
            <button class="btn-close-sidebar" @click="sidebarOpen = false">&times;</button>
        </div>

        <div class="sidebar-user-card">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 2)) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= e($user['username']) ?></div>
                <div class="user-balance"><?= money($user['balance']) ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="/user/dashboard.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="/user/new-order.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                <span>New Order</span>
            </a>
            <a href="/user/orders.php" class="nav-item active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                <span>Orders History</span>
            </a>
            <a href="/user/services.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Services List</span>
            </a>
            <a href="/user/add-funds.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 10h20"/></svg>
                <span>Add Funds</span>
            </a>
            <a href="/user/transactions.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                <span>Transactions</span>
            </a>
            <a href="/user/tickets.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <span>Support Tickets</span>
            </a>
            <a href="/user/api.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                <span>API Integration</span>
            </a>
        </nav>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <button class="btn-hamburger" @click="sidebarOpen = true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <div class="topbar-title">Orders Management</div>
            <div class="topbar-actions">
                <a href="/user/new-order.php" class="btn btn-primary btn-sm">+ New Order</a>
            </div>
        </header>

        <div class="panel-body">
            <!-- Filter Tabs -->
            <div class="order-tabs-bar">
                <div class="tabs-group">
                    <a href="?status=all" class="tab-link <?= $statusFilter === 'all' ? 'active' : '' ?>">All (<?= $counts['all'] ?>)</a>
                    <a href="?status=pending" class="tab-link <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending (<?= $counts['pending'] ?>)</a>
                    <a href="?status=inprogress" class="tab-link <?= $statusFilter === 'inprogress' ? 'active' : '' ?>">In Progress (<?= $counts['inprogress'] ?>)</a>
                    <a href="?status=completed" class="tab-link <?= $statusFilter === 'completed' ? 'active' : '' ?>">Completed (<?= $counts['completed'] ?>)</a>
                    <a href="?status=partial" class="tab-link <?= $statusFilter === 'partial' ? 'active' : '' ?>">Partial (<?= $counts['partial'] ?>)</a>
                    <a href="?status=canceled" class="tab-link <?= $statusFilter === 'canceled' ? 'active' : '' ?>">Canceled (<?= $counts['canceled'] ?>)</a>
                </div>

                <form method="GET" class="search-form">
                    <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search Order ID or link..." class="input-search">
                </form>
            </div>

            <!-- Orders Table -->
            <div class="card" style="margin-top: 16px;">
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Target Link</th>
                                    <th>Charge</th>
                                    <th>Start / Remains</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                            No orders found for this status. <a href="/user/new-order.php" style="color: var(--accent);">Place an order now &rarr;</a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $ord): ?>
                                        <tr>
                                            <td class="cell-id">#<?= $ord['id'] ?></td>
                                            <td style="max-width: 240px; font-weight: 500;">
                                                <div style="font-size: 13px;"><?= htmlspecialchars($ord['service_name']) ?></div>
                                            </td>
                                            <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <a href="<?= e($ord['link']) ?>" target="_blank" style="color: var(--accent); font-size: 13px; text-decoration: underline;"><?= e($ord['link']) ?></a>
                                            </td>
                                            <td style="font-weight: 600; color: #10b981;"><?= money($ord['charge']) ?></td>
                                            <td style="font-size: 13px; color: var(--text-muted);">
                                                <?= number_format($ord['start_count']) ?> / <?= number_format($ord['remains']) ?>
                                            </td>
                                            <td><strong><?= number_format($ord['quantity']) ?></strong></td>
                                            <td><?= status_badge($ord['status']) ?></td>
                                            <td style="font-size: 12px; color: var(--text-muted);"><?= format_date($ord['created_at']) ?></td>
                                            <td style="text-align: right;">
                                                <?php if ($ord['status'] === 'completed' && $ord['can_refill']): ?>
                                                    <button class="btn btn-xs btn-secondary" onclick="alert('Refill requested for Order #<?= $ord['id'] ?>')">Refill</button>
                                                <?php endif; ?>
                                                <?php if ($ord['status'] === 'pending' && $ord['can_cancel']): ?>
                                                    <a href="/api/cancel.php?key=<?= e($user['api_key']) ?>&order=<?= $ord['id'] ?>" class="btn btn-xs btn-danger">Cancel</a>
                                                <?php endif; ?>
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
