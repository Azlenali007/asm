<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Action: Regenerate API Key
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Security;

if (!Auth::check()) {
    redirect('/login.php');
}

if (!Csrf::validate()) {
    flash('error', 'Token mismatch. Please refresh.');
    redirect('/user/api.php');
}

$user = Auth::user();
$newKey = Security::generateApiKey();

Database::update('users', ['api_key' => $newKey], 'id = :id', [':id' => $user['id']]);
flash('success', 'Your API Key was regenerated successfully.');
redirect('/user/api.php');
