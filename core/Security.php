<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core Security Layer (XSS Sanitization, Input Cleaning, Security Headers)
 */

namespace Core;

class Security
{
    /**
     * Escape HTML output for XSS protection
     */
    public static function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitize user string input (strip tags, trim)
     */
    public static function clean(mixed $input): string
    {
        if (!is_string($input)) {
            return '';
        }
        return trim(strip_tags($input));
    }

    /**
     * Generate secure random token
     */
    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Generate API key format (e.g. key_64chars)
     */
    public static function generateApiKey(): string
    {
        return 'smm_' . bin2hex(random_bytes(28));
    }

    /**
     * Apply strict HTTP security headers
     */
    public static function applyHeaders(): void
    {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    /**
     * Mask sensitive strings (like API keys) in logs and UI
     */
    public static function mask(string $str, int $visibleChars = 4): string
    {
        $len = strlen($str);
        if ($len <= $visibleChars * 2) {
            return str_repeat('*', $len);
        }
        return substr($str, 0, $visibleChars) . str_repeat('*', $len - ($visibleChars * 2)) . substr($str, -$visibleChars);
    }
}
