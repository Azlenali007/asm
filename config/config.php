<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Configuration File
 */

return [
    'app' => [
        'name'          => 'ApexSMM Enterprise',
        'url'           => 'http://localhost:3000',
        'version'       => '3.2.0',
        'env'           => 'production', // 'development' or 'production'
        'debug'         => false,
        'timezone'      => 'UTC',
        'currency'      => '$',
        'currency_code' => 'USD',
        'locale'        => 'en_US',
    ],

    'session' => [
        'name'     => 'APEX_SMM_SESS',
        'lifetime' => 86400 * 7, // 7 days
        'path'     => '/',
        'domain'   => null,
        'secure'   => false, // set true in production HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    'security' => [
        'csrf_token_name'  => '_csrf_token',
        'max_login_attempts' => 5,
        'lockout_time'     => 900, // 15 minutes
        'api_rate_limit'   => 120, // requests per minute
        'bcrypt_cost'      => 12,
    ],

    'mail' => [
        'from_name'    => 'ApexSMM Support',
        'from_address' => 'noreply@apexsmm.com',
        'smtp_host'    => 'smtp.mailtrap.io',
        'smtp_port'    => 587,
        'smtp_user'    => '',
        'smtp_pass'    => '',
        'smtp_secure'  => 'tls',
    ],

    'maintenance' => [
        'enabled' => false,
        'message' => 'We are undergoing scheduled maintenance. Please check back shortly.',
    ],
];
