<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Dedicated Orders API Route
 */

$_REQUEST['action'] = $_REQUEST['action'] ?? (isset($_REQUEST['service']) ? 'add' : 'status');
require_once __DIR__ . '/v2.php';
