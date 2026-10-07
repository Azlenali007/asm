<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core RateLimiter (Protection against Brute Force & API Flood)
 */

namespace Core;

class RateLimiter
{
    /**
     * Check if client exceeds allowed requests in time window
     * Uses file/session cache to remain dependency-free without requiring external Redis
     */
    public static function check(string $key, int $maxAttempts = 60, int $decaySeconds = 60): bool
    {
        $ip = self::getClientIp();
        $cacheKey = 'rl_' . md5($key . '_' . $ip);
        
        $storageDir = dirname(__DIR__) . '/storage/ratelimit';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }

        $file = $storageDir . '/' . $cacheKey . '.json';
        $now = time();

        $data = ['attempts' => 0, 'expires_at' => $now + $decaySeconds];
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $parsed = $content ? json_decode($content, true) : null;
            if ($parsed && isset($parsed['expires_at']) && $parsed['expires_at'] > $now) {
                $data = $parsed;
            }
        }

        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }

        $data['attempts']++;
        if (!isset($data['expires_at']) || $data['expires_at'] <= $now) {
            $data['expires_at'] = $now + $decaySeconds;
        }

        @file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }

    /**
     * Clear rate limit for key on successful action
     */
    public static function clear(string $key): void
    {
        $ip = self::getClientIp();
        $cacheKey = 'rl_' . md5($key . '_' . $ip);
        $file = dirname(__DIR__) . '/storage/ratelimit/' . $cacheKey . '.json';
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
