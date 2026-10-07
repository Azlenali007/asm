<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Application Bootstrap
 */

// Strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Load Constants
require_once __DIR__ . '/../config/constants.php';

// PSR-4 Style Autoloader
spl_autoload_register(function ($class) {
    $prefixes = [
        'Core\\'      => PATH_CORE . '/',
        'Providers\\' => PATH_PROVIDERS . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load Helpers
require_once PATH_HELPERS . '/auth.php';
require_once PATH_HELPERS . '/url.php';
require_once PATH_HELPERS . '/format.php';
require_once PATH_HELPERS . '/validation.php';
require_once PATH_HELPERS . '/response.php';

// Exception and Error Handling
set_exception_handler(function (Throwable $e) {
    $logDir = PATH_STORAGE . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
    $logMsg = sprintf(
        "[%s] %s: %s in %s:%d\nStack Trace:\n%s\n\n",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @file_put_contents($logFile, $logMsg, FILE_APPEND | LOCK_EX);

    if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        json_response(['error' => 'Internal Server Error. Please contact support.'], 500);
    }

    $debug = app_config('app.debug', false);
    http_response_code(500);

    $homeUrl = function_exists('url') ? url('/') : '/';
    $cssUrl = function_exists('asset') ? asset('css/style.css') : '/assets/css/style.css';
    $errorDetails = $debug 
        ? "<div style='margin-top:20px;padding:16px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-family:monospace;font-size:12px;color:#991b1b;text-align:left;overflow-x:auto;'>" . htmlspecialchars((string)$e) . "</div>"
        : "";

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Notice - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="{$cssUrl}">
</head>
<body class="theme-light" style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8fafc;padding:24px;">
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:36px;max-width:520px;width:100%;text-align:center;box-shadow:0 10px 25px -5px rgba(0,0,0,0.06);">
        <div style="width:56px;height:56px;border-radius:14px;background:#f5f3ff;color:#7c3aed;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:24px;">
            &#9888;
        </div>
        <h2 style="font-size:20px;font-weight:700;color:#0f172a;margin-bottom:8px;">Service Temporarily Unavailable</h2>
        <p style="font-size:14px;color:#64748b;line-height:1.5;margin-bottom:24px;">
            The system encountered a temporary condition while fulfilling your request. Technical details have been logged to the server logs.
        </p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <a href="{$homeUrl}" class="btn btn-primary" style="text-decoration:none;">Return to Homepage</a>
            <a href="javascript:location.reload()" class="btn btn-secondary" style="text-decoration:none;">Try Again</a>
        </div>
        {$errorDetails}
    </div>
</body>
</html>
HTML;
    exit(1);
});

// Apply Security Headers & Start Session
\Core\Security::applyHeaders();
\Core\Session::start();
