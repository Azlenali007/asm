<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: Create Support Ticket
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
    flash('error', 'Session token invalid.');
    redirect('/user/tickets.php');
}

$user = Auth::user();
$subject = Security::clean($_POST['subject'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
$message = Security::clean($_POST['message'] ?? '');

$v = Validator::make($_POST, [
    'subject' => 'required|min:5|max:191',
    'message' => 'required|min:10',
]);

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect('/user/tickets.php');
}

Database::beginTransaction();
try {
    $ticketId = Database::insert('tickets', [
        'user_id'  => $user['id'],
        'subject'  => $subject,
        'priority' => in_array($priority, ['low', 'medium', 'high']) ? $priority : 'medium',
        'status'   => TICKET_PENDING,
    ]);

    Database::insert('ticket_messages', [
        'ticket_id' => $ticketId,
        'user_id'   => $user['id'],
        'message'   => $message,
        'is_admin'  => 0,
    ]);

    Database::commit();

    flash('success', "Ticket #{$ticketId} created successfully. Our team will respond shortly.");
    redirect('/user/ticket.php?id=' . $ticketId);
} catch (\Exception $e) {
    Database::rollBack();
    flash('error', 'Could not open ticket.');
    redirect('/user/tickets.php');
}
