<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Global Application Constants
 */

// Order Status Constants
define('ORDER_PENDING',     'pending');
define('ORDER_PROCESSING',  'processing');
define('ORDER_IN_PROGRESS', 'inprogress');
define('ORDER_COMPLETED',   'completed');
define('ORDER_PARTIAL',     'partial');
define('ORDER_CANCELED',    'canceled');
define('ORDER_FAIL',        'fail');

// Service Types
define('SERVICE_TYPE_DEFAULT',       'default');
define('SERVICE_TYPE_CUSTOM_COMMENTS', 'custom_comments');
define('SERVICE_TYPE_PACKAGE',       'package');
define('SERVICE_TYPE_SUBSCRIPTION',  'subscription');

// Ticket Status Constants
define('TICKET_PENDING',  'pending');
define('TICKET_ANSWERED', 'answered');
define('TICKET_CLOSED',   'closed');

// User Status Constants
define('USER_ACTIVE',    'active');
define('USER_SUSPENDED', 'suspended');
define('USER_BANNED',    'banned');

// User Roles
define('ROLE_USER',  'user');
define('ROLE_STAFF', 'staff');
define('ROLE_ADMIN', 'admin');

// Transaction Types
define('TXN_CREDIT', 'credit'); // funds added
define('TXN_DEBIT',  'debit');  // order placed / fee charged
define('TXN_REFUND', 'refund'); // order partial/canceled refund

// Payment Statuses
define('PAYMENT_PENDING',   'pending');
define('PAYMENT_COMPLETED', 'completed');
define('PAYMENT_FAILED',    'failed');
define('PAYMENT_CANCELED',  'canceled');

// System Paths
define('PATH_ROOT',      dirname(__DIR__));
define('PATH_CONFIG',    PATH_ROOT . '/config');
define('PATH_CORE',      PATH_ROOT . '/core');
define('PATH_HELPERS',   PATH_ROOT . '/helpers');
define('PATH_PROVIDERS', PATH_ROOT . '/providers');
define('PATH_STORAGE',   PATH_ROOT . '/storage');
define('PATH_LOGS',      PATH_ROOT . '/storage/logs');
