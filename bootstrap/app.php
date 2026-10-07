<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Application Bootstrap
 */

// Strict error reporting in dev, quiet in prod
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
    if (class_exists('\\Core\\Logger')) {
        \Core\Logger::error("Uncaught Exception: " . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }

    if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        json_response(['error' => 'Internal Server Error'], 500);
    }

    $debug = app_config('app.debug', false);
    if ($debug) {
        echo "<h1>Fatal Error</h1><pre>" . htmlspecialchars((string)$e) . "</pre>";
    } else {
        http_response_code(500);
        echo "<!DOCTYPE html><html><head><title>System Error</title><style>body{font-family:sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}</style></head><body><div style='text-align:center'><h2>Service Temporarily Unavailable</h2><p>Please try again later or contact support.</p></div></body></html>";
    }
    exit(1);
});

// Apply Security Headers & Start Session
\Core\Security::applyHeaders();
\Core\Session::start();
