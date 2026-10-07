<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * User: Services Catalog
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();
$categories = Database::fetchAll("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC");
$services = Database::fetchAll("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON c.id = s.category_id WHERE s.status = 1 ORDER BY c.sort_order ASC, s.sort_order ASC");

$customRates = !empty($user['custom_rates']) ? json_decode($user['custom_rates'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Rates - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-dark panel-layout" x-data="{ sidebarOpen: false, selectedCategory: 'all', search: '', detailsModal: null }">

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
            <a href="/user/orders.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                <span>Orders History</span>
            </a>
            <a href="/user/services.php" class="nav-item active">
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
            <div class="topbar-title">Services & Wholesale Rates</div>
            <div class="topbar-actions">
                <input type="text" x-model="search" placeholder="Quick search service..." class="input-search">
            </div>
        </header>

        <div class="panel-body">
            <!-- Category Tabs -->
            <div class="category-tabs" style="margin-bottom: 20px;">
                <button class="tab-btn" :class="{ 'active': selectedCategory === 'all' }" @click="selectedCategory = 'all'">All Services</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="tab-btn" :class="{ 'active': selectedCategory === '<?= $cat['id'] ?>' }" @click="selectedCategory = '<?= $cat['id'] ?>'">
                        <?= htmlspecialchars($cat['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th>Service Name</th>
                                    <th>Your Rate / 1K</th>
                                    <th>Min / Max</th>
                                    <th>Refill</th>
                                    <th>Cancel</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $srv): 
                                    $rate = (float)$srv['rate'];
                                    if (isset($customRates[$srv['id']])) {
                                        $rate = (float)$customRates[$srv['id']];
                                    }
                                ?>
                                    <tr x-show="(selectedCategory === 'all' || selectedCategory === '<?= $srv['category_id'] ?>') && ('<?= strtolower(addslashes($srv['name'])) ?>'.includes(search.toLowerCase()) || '<?= $srv['id'] ?>'.includes(search))">
                                        <td class="cell-id">#<?= $srv['id'] ?></td>
                                        <td>
                                            <div style="font-weight: 500; font-size: 14px;"><?= htmlspecialchars($srv['name']) ?></div>
                                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                                <?= htmlspecialchars($srv['category_name']) ?> &bull; Type: <?= $srv['type'] ?>
                                            </div>
                                        </td>
                                        <td class="cell-rate"><?= rate_per_k($rate) ?></td>
                                        <td class="cell-qty"><?= number_format($srv['min_quantity']) ?> / <?= number_short($srv['max_quantity']) ?></td>
                                        <td><?= $srv['refill'] ? '<span class="badge badge-success">30d Auto</span>' : '<span style="color:var(--text-muted);">&mdash;</span>' ?></td>
                                        <td><?= $srv['cancel'] ? '<span class="badge badge-info">Yes</span>' : '<span style="color:var(--text-muted);">&mdash;</span>' ?></td>
                                        <td style="text-align: right;">
                                            <a href="/user/new-order.php" class="btn btn-xs btn-primary">Order Now</a>
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
</body>
</html>
