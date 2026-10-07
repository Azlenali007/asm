<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core Session Manager (Strict Cookie Security, Cross-Context & Multi-Type Flash Messages)
 */

namespace Core;

class Session
{
    private static bool $started = false;
    private static array $flashMessages = [];

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $configPath = dirname(__DIR__) . '/config/config.php';
        $config = file_exists($configPath) ? require $configPath : [];
        $sessConfig = $config['session'] ?? [
            'name'     => 'APEX_SMM_SESS',
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'domain'   => null,
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        session_name($sessConfig['name'] ?? 'APEX_SMM_SESS');

        $isHttps = function_exists('is_https') ? is_https() : (
            (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        );

        // In HTTPS/iframe preview environments, SameSite=None; Secure ensures cookie persistence
        $sameSite = $isHttps ? 'None' : 'Lax';

        session_set_cookie_params([
            'lifetime' => $sessConfig['lifetime'] ?? (86400 * 7),
            'path'     => $sessConfig['path'] ?? '/',
            'domain'   => $sessConfig['domain'] ?? null,
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        session_start();
        self::$started = true;

        // Move incoming flash messages into request memory and clear from $_SESSION
        if (isset($_SESSION['_flash']) && is_array($_SESSION['_flash'])) {
            self::$flashMessages = $_SESSION['_flash'];
            unset($_SESSION['_flash']);
        }

        // Idle session check
        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > ($sessConfig['lifetime'] ?? (86400 * 7)))) {
            self::destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Set a flash message for the next/current request, or retrieve current request's flash
     * Supports: success, error, warning, info
     */
    public static function flash(string $type, ?string $message = null): ?string
    {
        self::start();

        if ($message !== null) {
            $_SESSION['_flash'][$type] = $message;
            self::$flashMessages[$type] = $message;
            return null;
        }

        return self::$flashMessages[$type] ?? ($_SESSION['_flash'][$type] ?? null);
    }

    public static function hasFlash(string $type): bool
    {
        self::start();
        return !empty(self::$flashMessages[$type]) || !empty($_SESSION['_flash'][$type]);
    }

    public static function getAllFlashes(): array
    {
        self::start();
        $flashes = array_merge(self::$flashMessages, $_SESSION['_flash'] ?? []);
        self::$flashMessages = [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            self::$flashMessages = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            self::$started = false;
        }
    }
}
