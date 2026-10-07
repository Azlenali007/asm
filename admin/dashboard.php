<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Admin Dashboard - Master Executive Control Panel
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check() || !Auth::isAdmin()) {
    flash('error', 'Administrator access required.');
    redirect('/login.php');
}

$user = Auth::user();

// Key Metrics
$totalUsers = (int)Database::fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'user'");
$totalOrders = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders");
$totalRevenue = (float)Database::fetchColumn("SELECT SUM(charge) FROM orders");
$totalDeposits = (float)Database::fetchColumn("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
$totalUserBalances = (float)Database::fetchColumn("SELECT SUM(balance) FROM users");

// Status distribution
$pendingOrders = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$inProgressOrders = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE status IN ('processing', 'inprogress')");
$completedOrders = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
$canceledOrders = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE status IN ('canceled', 'fail')");

// Providers
$providers = Database::fetchAll("SELECT * FROM providers ORDER BY id ASC");

// Recent System Orders
$recentOrders = Database::fetchAll("SELECT o.*, u.username, s.name as service_name FROM orders o JOIN users u ON u.id = o.user_id JOIN services s ON s.id = o.service_id ORDER BY o.id DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin Panel - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="theme-dark panel-layout" x-data="{ sidebarOpen: false }">

    <!-- Admin Sidebar -->
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

        <div class="sidebar-user-card" style="border-left: 3px solid #ef4444;">
            <div class="user-avatar" style="background: #991b1b; color: #fecaca;"><?= strtoupper(substr($user['username'], 0, 2)) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= e($user['username']) ?> <span class="badge badge-danger">ADMIN</span></div>
                <div class="user-balance" style="font-size: 11px; color: var(--text-muted);"><?= e($user['email']) ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="/admin/dashboard.php" class="nav-item active">
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
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                <span>Users</span>
            </a>
            <a href="/admin/providers/index.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                <span>Providers</span>
            </a>
            <a href="/admin/settings/index.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                <span>System Settings</span>
            </a>
            <div class="sidebar-divider"></div>
            <a href="/user/dashboard.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                <span>Switch to Client View</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="/logout.php" class="btn btn-ghost btn-sm" style="width: 100%; justify-content: flex-start;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <button class="btn-hamburger" @click="sidebarOpen = true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <div class="topbar-title">Master Executive Control</div>
            <div class="topbar-actions">
                <span class="badge badge-success">System Online &bull; v3.2.0</span>
            </div>
        </header>

        <div class="panel-body">
            <!-- Executive Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-label">Gross Order Volume</div>
                        <div class="metric-value"><?= money($totalRevenue) ?></div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 10h20"/></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-label">Total Deposits Received</div>
                        <div class="metric-value"><?= money($totalDeposits) ?></div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-label">Registered Clients</div>
                        <div class="metric-value"><?= number_format($totalUsers) ?></div>
                    </div>
                    <span class="metric-sub"><?= money($totalUserBalances) ?> in user wallets</span>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-label">Total Panel Orders</div>
                        <div class="metric-value"><?= number_format($totalOrders) ?></div>
                    </div>
                    <span class="metric-sub"><?= $pendingOrders ?> Queue</span>
                </div>
            </div>

            <!-- Charts & Provider Balances -->
            <div class="dashboard-split" style="margin-top: 24px;">
                <!-- ApexCharts Status Donut -->
                <div class="card" style="flex: 2;">
                    <div class="card-header">
                        <h3>Order Status Distribution</h3>
                    </div>
                    <div class="card-body">
                        <div id="statusDonutChart" style="min-height: 280px;"></div>
                    </div>
                </div>

                <!-- Upstream Provider API Balances -->
                <div class="card" style="flex: 2;">
                    <div class="card-header">
                        <h3>Upstream Provider Balances</h3>
                        <a href="/admin/providers/index.php" style="color: var(--accent); font-size: 13px; text-decoration: none;">Manage &rarr;</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($providers)): ?>
                            <p style="color: var(--text-muted); font-size: 13px;">No external SMM providers configured yet. <a href="/admin/providers/index.php" style="color: var(--accent);">Add your first provider API</a></p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($providers as $p): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px;">
                                        <div>
                                            <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($p['name']) ?></div>
                                            <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($p['api_url']) ?></div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-weight: 700; color: #10b981; font-size: 15px;"><?= money($p['balance']) ?></div>
                                            <span class="badge badge-success">Connected</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Master Recent Orders -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <h3>Recent System Orders</h3>
                    <a href="/admin/orders/index.php" style="color: var(--accent); font-size: 13px; text-decoration: none;">View All Orders &rarr;</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Service</th>
                                    <th>Target Link</th>
                                    <th>Charge</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">No orders recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $ro): ?>
                                        <tr>
                                            <td class="cell-id">#<?= $ro['id'] ?></td>
                                            <td><strong><?= e($ro['username']) ?></strong></td>
                                            <td style="max-width: 200px;"><?= htmlspecialchars($ro['service_name']) ?></td>
                                            <td style="max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <a href="<?= e($ro['link']) ?>" target="_blank" style="color: var(--accent);"><?= e($ro['link']) ?></a>
                                            </td>
                                            <td style="font-weight: 600; color: #10b981;"><?= money($ro['charge']) ?></td>
                                            <td><?= status_badge($ro['status']) ?></td>
                                            <td style="font-size: 12px; color: var(--text-muted);"><?= format_date($ro['created_at'], 'M d, H:i') ?></td>
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

    <!-- ApexCharts Donut -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var donutOptions = {
                series: [<?= $completedOrders ?>, <?= $inProgressOrders ?>, <?= $pendingOrders ?>, <?= $canceledOrders ?>],
                chart: {
                    type: 'donut',
                    height: 270,
                    background: 'transparent'
                },
                labels: ['Completed', 'In Progress', 'Pending', 'Canceled'],
                colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                legend: {
                    position: 'bottom',
                    labels: { colors: '#94a3b8' }
                },
                stroke: { colors: ['#111827'], width: 2 },
                dataLabels: { enabled: true },
                tooltip: { theme: 'dark' }
            };

            var donutChart = new ApexCharts(document.querySelector("#statusDonutChart"), donutOptions);
            donutChart.render();
        });
    </script>
</body>
</html>
