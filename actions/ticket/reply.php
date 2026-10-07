<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: Reply to Support Ticket
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Validator;
use Core\Security;

if (!Auth::check()) {
    redirect('/login.php');
}

if (!Csrf::validate()) {
    flash('error', 'Token expired.');
    redirect('/user/tickets.php');
}

$user = Auth::user();
$ticketId = (int)($_POST['ticket_id'] ?? 0);
$message = Security::clean($_POST['message'] ?? '');

$v = Validator::make($_POST, [
    'ticket_id' => 'required|numeric',
    'message'   => 'required|min:2',
]);

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect('/user/ticket.php?id=' . $ticketId);
}

// Verify ticket ownership unless admin
$isAdmin = Auth::isAdmin();
$ticket = Database::fetch("SELECT * FROM tickets WHERE id = :id" . ($isAdmin ? "" : " AND user_id = :uid"), [
    ':id'  => $ticketId,
    ...($isAdmin ? [] : [':uid' => $user['id']]),
]);

if (!$ticket) {
    flash('error', 'Ticket not found.');
    redirect('/user/tickets.php');
}

Database::beginTransaction();
try {
    Database::insert('ticket_messages', [
        'ticket_id' => $ticketId,
        'user_id'   => $user['id'],
        'message'   => $message,
        'is_admin'  => $isAdmin ? 1 : 0,
    ]);

    // Update ticket status
    $newStatus = $isAdmin ? TICKET_ANSWERED : TICKET_PENDING;
    Database::update('tickets', [
        'status'     => $newStatus,
        'updated_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', [':id' => $ticketId]);

    Database::commit();

    flash('success', 'Reply posted successfully.');
    $redirectUrl = $isAdmin ? "/admin/tickets/view.php?id={$ticketId}" : "/user/ticket.php?id={$ticketId}";
    redirect($redirectUrl);
} catch (\Exception $e) {
    Database::rollBack();
    flash('error', 'Failed to post reply.');
    redirect('/user/ticket.php?id=' . $ticketId);
}
