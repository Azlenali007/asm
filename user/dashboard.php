<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * User Dashboard
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();

// Fetch summary metrics
$totalOrders = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid", [':uid' => $user['id']]);
$pendingOrders = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status IN ('pending', 'processing', 'inprogress')", [':uid' => $user['id']]);
$completedOrders = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status = 'completed'", [':uid' => $user['id']]);
$openTickets = (int)Database::fetchColumn("SELECT COUNT(*) FROM tickets WHERE user_id = :uid AND status != 'closed'", [':uid' => $user['id']]);

// Recent orders
$recentOrders = Database::fetchAll("SELECT o.*, s.name as service_name FROM orders o JOIN services s ON s.id = o.service_id WHERE o.user_id = :uid ORDER BY o.id DESC LIMIT 6", [':uid' => $user['id']]);

// Daily spend breakdown for the last 7 days (for ApexCharts)
$chartDates = [];
$chartAmounts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $chartDates[] = date('M d', strtotime($date));
    $daySpend = (float)Database::fetchColumn("SELECT SUM(ABS(amount)) FROM transactions WHERE user_id = :uid AND type = 'debit' AND DATE(created_at) = :dt", [':uid' => $user['id'], ':dt' => $date]);
    $chartAmounts[] = round($daySpend, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="theme-dark panel-layout" x-data="{ sidebarOpen: false }">

    <!-- Sidebar Navigation -->
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
            <a href="/user/dashboard.php" class="nav-item active">
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
            <?php if (Auth::isAdmin()): ?>
                <div class="sidebar-divider"></div>
                <a href="/admin/dashboard.php" class="nav-item" style="color: #60a5fa;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Admin Panel</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="/logout.php" class="btn btn-ghost btn-sm" style="width: 100%; justify-content: flex-start;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-panel">
        <header class="topbar">
            <button class="btn-hamburger" @click="sidebarOpen = true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <div class="topbar-title">Dashboard Overview</div>
            <div class="topbar-actions">
                <a href="/user/new-order.php" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    <span>Place Order</span>
                </a>
            </div>
        </header>

        <div class="panel-body">
            <?php if ($flash = flash('success')): ?>
                <div class="alert alert-success"><?= e($flash) ?></div>
            <?php endif; ?>

            <!-- Metrics Grid -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 10h20"/></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-label">Account Balance</div>
                        <div class="metric-value"><?= money($user['balance']) ?></div>
                    </div>
                    <a href="/user/add-funds.php" class="metric-link">+ Deposit</a>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-label">Total Spent</div>
                        <div class="metric-value"><?= money($user['spent']) ?></div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-label">Total Orders</div>
                        <div class="metric-value"><?= number_format($totalOrders) ?></div>
                    </div>
                    <span class="metric-sub"><?= $pendingOrders ?> In Progress</span>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-label">Support Tickets</div>
                        <div class="metric-value"><?= $openTickets ?></div>
                    </div>
                    <a href="/user/tickets.php" class="metric-link">Open New</a>
                </div>
            </div>

            <!-- Charts & Quick Action Section -->
            <div class="dashboard-split">
                <!-- ApexCharts Spend Trends -->
                <div class="card" style="flex: 2;">
                    <div class="card-header">
                        <h3>7-Day Spending Activity</h3>
                        <span style="font-size: 13px; color: var(--text-muted);">Real-time expenditure</span>
                    </div>
                    <div class="card-body">
                        <div id="spendChart" style="min-height: 280px;"></div>
                    </div>
                </div>

                <!-- Account Status & API Quick Card -->
                <div class="card" style="flex: 1;">
                    <div class="card-header">
                        <h3>API Credentials</h3>
                    </div>
                    <div class="card-body">
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">Integrate directly with your own panel or script using your API key:</p>
                        <div class="api-box">
                            <input type="password" value="<?= e($user['api_key'] ?? 'No key generated') ?>" id="quickApiKey" readonly class="form-control" style="font-family: monospace; font-size: 12px;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('quickApiKey').value); this.innerText = 'Copied!'; setTimeout(() => this.innerText = 'Copy', 2000);">Copy</button>
                        </div>
                        <a href="/user/api.php" class="btn btn-ghost btn-sm" style="width: 100%; margin-top: 14px; text-align: center;">View API v2 Documentation &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <h3>Recent Orders</h3>
                    <a href="/user/orders.php" style="color: var(--accent); font-size: 13px; text-decoration: none;">View All &rarr;</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Link</th>
                                    <th>Qty</th>
                                    <th>Charge</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                            No orders placed yet. <a href="/user/new-order.php" style="color: var(--accent);">Place your first order!</a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $ro): ?>
                                        <tr>
                                            <td class="cell-id">#<?= $ro['id'] ?></td>
                                            <td style="max-width: 250px; font-weight: 500;"><?= htmlspecialchars($ro['service_name']) ?></td>
                                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: var(--text-muted);">
                                                <a href="<?= e($ro['link']) ?>" target="_blank" style="color: var(--text-muted); text-decoration: underline;"><?= e($ro['link']) ?></a>
                                            </td>
                                            <td><?= number_format($ro['quantity']) ?></td>
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

    <!-- ApexCharts Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var options = {
                series: [{
                    name: 'Expenditure ($)',
                    data: <?= json_encode($chartAmounts) ?>
                }],
                chart: {
                    type: 'area',
                    height: 270,
                    toolbar: { show: false },
                    background: 'transparent'
                },
                colors: ['#3b82f6'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [0, 95, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: <?= json_encode($chartDates) ?>,
                    labels: { style: { colors: '#94a3b8', fontSize: '12px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        formatter: function(val) { return '$' + val.toFixed(2); },
                        style: { colors: '#94a3b8', fontSize: '12px' }
                    }
                },
                grid: {
                    borderColor: 'rgba(255, 255, 255, 0.06)',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: 'dark',
                    y: { formatter: function(val) { return '$' + val.toFixed(2); } }
                }
            };

            var chart = new ApexCharts(document.querySelector("#spendChart"), options);
            chart.render();
        });
    </script>
</body>
</html>
