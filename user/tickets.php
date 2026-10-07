<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * User: Support Tickets List & Creation
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();
$tickets = Database::fetchAll("SELECT * FROM tickets WHERE user_id = :uid ORDER BY id DESC", [':uid' => $user['id']]);

$error = flash('error');
$success = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-dark panel-layout" x-data="{ sidebarOpen: false, createModal: false }">

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
            <a href="/user/tickets.php" class="nav-item active">
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
            <div class="topbar-title">Support Desk</div>
            <div class="topbar-actions">
                <button class="btn btn-primary btn-sm" @click="createModal = true">
                    + New Ticket
                </button>
            </div>
        </header>

        <div class="panel-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tickets)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                            No open support tickets. Need help with an order? Open a ticket!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tickets as $t): ?>
                                        <tr>
                                            <td class="cell-id">#<?= $t['id'] ?></td>
                                            <td style="font-weight: 500;">
                                                <a href="/user/ticket.php?id=<?= $t['id'] ?>" style="color: #fff; text-decoration: none;">
                                                    <?= htmlspecialchars($t['subject']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge <?= $t['priority'] === 'high' ? 'badge-danger' : 'badge-default' ?>">
                                                    <?= ucfirst($t['priority']) ?>
                                                </span>
                                            </td>
                                            <td><?= status_badge($t['status']) ?></td>
                                            <td style="font-size: 12px; color: var(--text-muted);"><?= format_date($t['updated_at']) ?></td>
                                            <td style="text-align: right;">
                                                <a href="/user/ticket.php?id=<?= $t['id'] ?>" class="btn btn-xs btn-secondary">View Thread &rarr;</a>
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

    <!-- Create Ticket Modal -->
    <div class="modal-backdrop" x-show="createModal" style="display: none;" @keydown.escape.window="createModal = false">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Open Support Ticket</h3>
                <button type="button" class="btn-close-modal" @click="createModal = false">&times;</button>
            </div>
            <form action="/actions/ticket/create.php" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Order #1234 inquiry or refill" required>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High (Urgent)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" rows="5" class="form-control" placeholder="Describe your question or issue in detail..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" @click="createModal = false">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
