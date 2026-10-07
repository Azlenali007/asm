<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Authentication: Logout Handler
 */

require_once __DIR__ . '/bootstrap/app.php';

use Core\Auth;

Auth::logout();
flash('success', 'You have been logged out securely.');
redirect('/login.php');
