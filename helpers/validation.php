<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Flash & Validation Input Helpers
 */

use Core\Session;

if (!function_exists('old')) {
    function old(string $field, mixed $default = ''): mixed {
        $oldInputs = Session::get('_old_inputs', []);
        return $oldInputs[$field] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value = null): mixed {
        return Session::flash($key, $value);
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $key): bool {
        return Session::hasFlash($key);
    }
}

if (!function_exists('has_error')) {
    function has_error(string $field): bool {
        $errors = Session::get('_form_errors', []);
        return isset($errors[$field]);
    }
}

if (!function_exists('get_error')) {
    function get_error(string $field): ?string {
        $errors = Session::get('_form_errors', []);
        return $errors[$field][0] ?? null;
    }
}
