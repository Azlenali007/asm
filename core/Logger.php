<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core Application Logger (Sanitizes passwords/secrets before writing)
 */

namespace Core;

class Logger
{
    private static function write(string $level, string $message, array $context = []): void
    {
        $logDir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Mask any secrets in context (passwords, api_keys, tokens)
        $cleanContext = self::maskSensitive($context);
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($cleanContext) ? ' ' . json_encode($cleanContext) : '';
        $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function audit(string $message, array $context = []): void
    {
        self::write('AUDIT', $message, $context);
    }

    private static function maskSensitive(array $data): array
    {
        $sensitiveKeys = ['password', 'pass', 'api_key', 'key', 'token', 'secret', 'credit_card'];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $data[$key] = self::maskSensitive($val);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '***REDACTED***';
            }
        }
        return $data;
    }
}
