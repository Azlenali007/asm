<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core CSRF Protection
 */

namespace Core;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Generate or return existing CSRF token
     */
    public static function token(): string
    {
        Session::start();
        $token = Session::get(self::SESSION_KEY);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    /**
     * Validate request CSRF token
     */
    public static function validate(?string $token = null): bool
    {
        Session::start();
        $stored = Session::get(self::SESSION_KEY);
        if (!$stored) {
            return false;
        }

        if ($token === null) {
            $token = $_POST['_csrf_token'] 
                ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
                ?? null;
        }

        if (!$token) {
            return false;
        }

        return hash_equals($stored, $token);
    }

    /**
     * Generates a hidden HTML input field
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    /**
     * Regenerate token after sensitive action
     */
    public static function regenerate(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_KEY, $token);
        return $token;
    }
}
