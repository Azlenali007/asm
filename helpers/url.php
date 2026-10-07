<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * URL, Asset, and Navigation Helpers
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

if (!function_exists('is_https')) {
    function is_https(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protos = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO']);
            if (strtolower(trim($protos[0])) === 'https') {
                return true;
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
            return true;
        }
        if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower((string)$_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }
        if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        return false;
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path(): string {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = dirname($scriptName);
        // Strip application subfolders (like /user, /admin, /actions, /install, /api, /cron)
        $baseDir = preg_replace('#/(user|admin|actions(/.*)?|install|api|cron)(/.*)?$#', '', $dir);
        $base = ($baseDir === '/' || $baseDir === '\\' || $baseDir === '.') ? '' : rtrim($baseDir, '/');
        return $base;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        $path = ltrim($path, '/');
        $basePath = app_base_path();
        $target = $basePath !== '' ? $basePath . '/' . $path : '/' . $path;
        return $path === '' && $basePath === '' ? '/' : rtrim($target, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }
        return base_url($path);
    }
}

if (!function_exists('full_url')) {
    function full_url(string $path = ''): string {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $protocol = is_https() ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host . url($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string {
        $path = ltrim($path, '/');
        $basePath = app_base_path();
        // Always return root-relative asset URL to prevent Mixed-Content blocks across proxies
        return ($basePath !== '' ? $basePath : '') . '/assets/' . $path;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $status = 302): never {
        $target = str_starts_with($path, 'http://') || str_starts_with($path, 'https://') 
            ? $path 
            : url($path);

        // Crucial: ensure session data is completely committed before sending redirect header
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        header("Location: {$target}", true, $status);
        exit;
    }
}

if (!function_exists('current_url')) {
    function current_url(): string {
        $protocol = is_https() ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $protocol . $host . $uri;
    }
}
