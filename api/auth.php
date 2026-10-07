<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Auth API Route (Token validation & profile)
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;

header('Content-Type: application/json; charset=utf-8');

$key = $_REQUEST['key'] ?? '';
$user = Auth::authenticateApiKey($key);

if (!$user) {
    json_response(['error' => 'Unauthorized. Invalid API key.'], 401);
}

json_response([
    'status'   => 'authenticated',
    'username' => $user['username'],
    'email'    => $user['email'],
    'balance'  => number_format((float)$user['balance'], 4, '.', ''),
    'spent'    => number_format((float)$user['spent'], 4, '.', ''),
    'role'     => $user['role'],
]);
