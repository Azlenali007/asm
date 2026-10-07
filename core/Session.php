<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core Session Manager (Strict Cookie Security & Flash Messages)
 */

namespace Core;

class Session
{
    private static bool $started = false;

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

        session_name($sessConfig['name']);

        session_set_cookie_params([
            'lifetime' => $sessConfig['lifetime'],
            'path'     => $sessConfig['path'],
            'domain'   => $sessConfig['domain'],
            'secure'   => $sessConfig['secure'] || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => $sessConfig['httponly'],
            'samesite' => $sessConfig['samesite'],
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        session_start();
        self::$started = true;

        // Auto-expire idle sessions if needed
        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > ($sessConfig['lifetime']))) {
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

    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        if (isset($_SESSION['_flash'][$key])) {
            $msg = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }

        return null;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$key]);
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
