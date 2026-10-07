<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Authentication & Security Helpers
 */

use Core\Auth;
use Core\Csrf;
use Core\Security;

if (!function_exists('auth')) {
    function auth(): ?array {
        return Auth::user();
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int {
        return Auth::id();
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return Auth::check();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool {
        return Auth::isAdmin();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return Csrf::field();
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string {
        return Security::e($value);
    }
}
