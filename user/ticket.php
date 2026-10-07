<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * User: Ticket Thread & Reply
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();
$ticketId = (int)($_GET['id'] ?? 0);

$ticket = Database::fetch("SELECT * FROM tickets WHERE id = :id AND user_id = :uid", [
    ':id'  => $ticketId,
    ':uid' => $user['id']
]);

if (!$ticket) {
    flash('error', 'Ticket not found.');
    redirect('/user/tickets.php');
}

$messages = Database::fetchAll("SELECT * FROM ticket_messages WHERE ticket_id = :tid ORDER BY id ASC", [':tid' => $ticketId]);

$error = flash('error');
$success = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?= $ticket['id'] ?> - ApexSMM Enterprise</title>
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
            <div class="topbar-title">Ticket #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['subject']) ?></div>
            <div class="topbar-actions">
                <a href="/user/tickets.php" class="btn btn-secondary btn-sm">&larr; Back to Tickets</a>
            </div>
        </header>

        <div class="panel-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <!-- Ticket Meta Card -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h3 style="font-size: 16px; margin-bottom: 4px;"><?= htmlspecialchars($ticket['subject']) ?></h3>
                        <div style="font-size: 13px; color: var(--text-muted);">
                            Opened on <?= format_date($ticket['created_at']) ?> &bull; Priority: <?= ucfirst($ticket['priority']) ?>
                        </div>
                    </div>
                    <div>
                        <?= status_badge($ticket['status']) ?>
                    </div>
                </div>
            </div>

            <!-- Messages Stream -->
            <div class="ticket-stream">
                <?php foreach ($messages as $msg): ?>
                    <div class="ticket-message-card <?= $msg['is_admin'] ? 'is-staff' : 'is-user' ?>">
                        <div class="message-header">
                            <span class="author-name"><?= $msg['is_admin'] ? 'Support Agent (Staff)' : e($user['username']) ?></span>
                            <span class="message-time"><?= format_date($msg['created_at']) ?></span>
                        </div>
                        <div class="message-body">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Reply Box (if not closed) -->
            <?php if ($ticket['status'] !== 'closed'): ?>
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3>Post a Reply</h3>
                    </div>
                    <div class="card-body">
                        <form action="/actions/ticket/reply.php" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

                            <div class="form-group">
                                <textarea name="message" rows="4" class="form-control" placeholder="Write your reply or additional details..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Send Reply
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="margin-top: 20px;">This ticket is closed. Please open a new ticket if you need further help.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
