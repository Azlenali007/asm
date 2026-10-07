<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * URL and Navigation Helpers
 */

if (!function_exists('app_config')) {
    function app_config(string $key, mixed $default = null): mixed {
        static $cfg = null;
        if ($cfg === null) {
            $path = dirname(__DIR__) . '/config/config.php';
            $cfg = file_exists($path) ? require $path : [];
        }
        $parts = explode('.', $key);
        $curr = $cfg;
        foreach ($parts as $p) {
            if (!is_array($curr) || !array_key_exists($p, $curr)) {
                return $default;
            }
            $curr = $curr[$p];
        }
        return $curr;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $baseUrl = rtrim(app_config('app.url', ''), '/');
        $path = ltrim($path, '/');
        return $baseUrl ? $baseUrl . '/' . $path : '/' . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $status = 302): never {
        $target = str_starts_with($path, 'http') ? $path : url($path);
        header("Location: {$target}", true, $status);
        exit;
    }
}

if (!function_exists('current_url')) {
    function current_url(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    }
}
