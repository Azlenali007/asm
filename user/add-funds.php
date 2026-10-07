<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * User: Add Funds (Deposit)
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();
$error = flash('error');
$success = flash('success');

$methods = [
    ['id' => 'Cryptocurrency (USDT / BTC / ETH)', 'fee' => '0%', 'min' => 5, 'badge' => 'Instant & Zero Fee'],
    ['id' => 'Credit / Debit Card (Stripe)', 'fee' => '2.9% + $0.30', 'min' => 10, 'badge' => 'Automated'],
    ['id' => 'PayPal Express', 'fee' => '4.5%', 'min' => 10, 'badge' => 'Verified accounts only'],
    ['id' => 'Wire / Bank Transfer', 'fee' => '0%', 'min' => 200, 'badge' => 'Manual review (1-2h)'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Funds - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-light panel-layout" x-data="{ sidebarOpen: false }">

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
            <a href="/user/services.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Services List</span>
            </a>
            <a href="/user/add-funds.php" class="nav-item active">
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
            <div class="topbar-title">Deposit Balance</div>
            <div class="topbar-actions">
                <div class="header-balance">Current: <strong><?= money($user['balance']) ?></strong></div>
            </div>
        </header>

        <div class="panel-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="dashboard-split">
                <div class="card" style="flex: 3;">
                    <div class="card-header">
                        <h3>Deposit Payment</h3>
                    </div>
                    <div class="card-body">
                        <form action="/actions/payment/add_funds.php" method="POST">
                            <?= csrf_field() ?>

                            <div class="form-group">
                                <label for="method">Payment Gateway</label>
                                <select id="method" name="method" class="form-control" required>
                                    <?php foreach ($methods as $m): ?>
                                        <option value="<?= htmlspecialchars($m['id']) ?>">
                                            <?= htmlspecialchars($m['id']) ?> (Fee: <?= $m['fee'] ?>) - Min: $<?= $m['min'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="amount">Deposit Amount (USD)</label>
                                <input type="number" id="amount" name="amount" class="form-control" min="5" max="5000" step="1" value="50" required>
                                <span class="field-help">Min: $5.00 | Max: $5,000.00 per transaction.</span>
                            </div>

                            <!-- Preset Amount Buttons -->
                            <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('amount').value=25">$25</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('amount').value=50">$50</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('amount').value=100">$100</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('amount').value=250">$250</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('amount').value=500">$500</button>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                Pay & Credit Balance
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card" style="flex: 2;">
                    <div class="card-header">
                        <h3>Payment Security</h3>
                    </div>
                    <div class="card-body">
                        <div style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                            <p style="margin-bottom: 12px;"><strong style="color: #fff;">Automatic Crediting:</strong> Crypto and card deposits are confirmed automatically by webhooks within 60 seconds.</p>
                            <p style="margin-bottom: 12px;"><strong style="color: #fff;">Zero Chargeback Policy:</strong> Due to wholesale pricing, funds once credited are non-refundable to original payment methods.</p>
                            <p><strong style="color: #fff;">Need Higher Limits?</strong> Contact support to upgrade your account tier for VIP higher limits and volume discounts.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
