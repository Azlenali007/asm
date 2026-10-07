<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Flash & Validation Input Helpers
 */

use Core\Session;
use Core\Security;

if (!function_exists('old')) {
    function old(string $field, mixed $default = ''): mixed {
        $oldInputs = Session::get('_old_inputs', []);
        return $oldInputs[$field] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $type, ?string $message = null): ?string {
        return Session::flash($type, $message);
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $type): bool {
        return Session::hasFlash($type);
    }
}

if (!function_exists('render_flashes')) {
    function render_flashes(): string {
        $flashes = Session::getAllFlashes();
        if (empty($flashes)) {
            return '';
        }

        $html = '';
        foreach ($flashes as $type => $message) {
            if (empty($message)) continue;

            $safeMsg = Security::e($message);
            $typeClass = match ($type) {
                'success' => 'alert-success',
                'error', 'danger' => 'alert-error',
                'warning' => 'alert-warning',
                'info'    => 'alert-info',
                default   => 'alert-info',
            };

            $iconSvg = match ($type) {
                'success' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
                'error', 'danger' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
                'warning' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                default   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            };

            $html .= "<div class=\"alert {$typeClass}\">"
                   . "<span class=\"alert-icon\">{$iconSvg}</span>"
                   . "<div class=\"alert-content\">{$safeMsg}</div>"
                   . "</div>";
        }

        return $html;
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
