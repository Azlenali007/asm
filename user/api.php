<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * User: API Documentation & Key Management
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();
$error = flash('error');
$success = flash('success');

$apiUrl = url('api/v2.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API v2 Integration - ApexSMM Enterprise</title>
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
            <a href="/user/api.php" class="nav-item active">
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
            <div class="topbar-title">Standard Reseller API v2</div>
        </header>

        <div class="panel-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <!-- API Key Card -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h3>Your Private API Credentials</h3>
                </div>
                <div class="card-body">
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">Keep this key confidential. Never expose it in client-side code or public repositories.</p>
                    
                    <div class="api-box">
                        <input type="text" id="userKeyInput" value="<?= e($user['api_key'] ?? '') ?>" readonly class="form-control" style="font-family: monospace;">
                        <button type="button" class="btn btn-secondary" onclick="navigator.clipboard.writeText(document.getElementById('userKeyInput').value); this.innerText = 'Copied!'; setTimeout(() => this.innerText = 'Copy Key', 2000);">Copy Key</button>
                    </div>

                    <form action="/actions/user/api_key.php" method="POST" style="margin-top: 14px;" onsubmit="return confirm('Regenerating your key will invalidate the previous key. Continue?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-ghost btn-sm" style="color: #f87171;">Regenerate New API Key</button>
                    </form>
                </div>
            </div>

            <!-- API Endpoints Documentation -->
            <div class="card">
                <div class="card-header">
                    <h3>API Specifications (v2 Standard)</h3>
                </div>
                <div class="card-body">
                    <div style="font-size: 14px; margin-bottom: 16px;">
                        <strong>Endpoint URL:</strong> <code><?= $apiUrl ?></code><br>
                        <strong>HTTP Method:</strong> <code>POST</code> (or GET for services/balance)<br>
                        <strong>Response Format:</strong> <code>application/json</code>
                    </div>

                    <h4 style="font-size: 15px; margin: 24px 0 8px; color: #60a5fa;">1. Service List</h4>
                    <pre class="code-block">POST <?= $apiUrl ?>
key=<?= e($user['api_key'] ?? 'YOUR_API_KEY') ?>&action=services</pre>

                    <h4 style="font-size: 15px; margin: 24px 0 8px; color: #60a5fa;">2. Add Order</h4>
                    <pre class="code-block">POST <?= $apiUrl ?>
key=<?= e($user['api_key'] ?? 'YOUR_API_KEY') ?>&action=add&service=101&link=https://instagram.com/p/xxx&quantity=1000

// Response:
{
  "order": 12845
}</pre>

                    <h4 style="font-size: 15px; margin: 24px 0 8px; color: #60a5fa;">3. Order Status</h4>
                    <pre class="code-block">POST <?= $apiUrl ?>
key=<?= e($user['api_key'] ?? 'YOUR_API_KEY') ?>&action=status&order=12845

// Response:
{
  "charge": "0.8500",
  "start_count": "1420",
  "status": "COMPLETED",
  "remains": "0",
  "currency": "USD"
}</pre>

                    <h4 style="font-size: 15px; margin: 24px 0 8px; color: #60a5fa;">4. Account Balance</h4>
                    <pre class="code-block">POST <?= $apiUrl ?>
key=<?= e($user['api_key'] ?? 'YOUR_API_KEY') ?>&action=balance

// Response:
{
  "balance": "84.5000",
  "currency": "USD"
}</pre>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
