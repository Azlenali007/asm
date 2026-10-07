<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Automated Cron: Provider Balance & Sync
 */

if (php_sapi_name() !== 'cli' && empty($_GET['cron_key'])) {
    http_response_code(403);
    die("Access denied.");
}

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Database;
use Core\Logger;
use Providers\Adapters\StandardProvider;

Logger::info("Starting provider balance synchronization...");

$providers = Database::fetchAll("SELECT * FROM providers WHERE status = 1");

foreach ($providers as $prov) {
    try {
        $adapter = new StandardProvider($prov['api_url'], $prov['api_key']);
        $bal = $adapter->balance();

        if (isset($bal['balance'])) {
            Database::update('providers', [
                'balance'  => (float)$bal['balance'],
                'currency' => $bal['currency'] ?? 'USD',
            ], 'id = :id', [':id' => $prov['id']]);
            echo "Provider #{$prov['id']} ({$prov['name']}) Balance: {$bal['balance']} {$bal['currency']}\n";
        }
    } catch (\Exception $e) {
        Logger::error("Sync failed for provider #{$prov['id']}: " . $e->getMessage());
    }
}

Logger::info("Provider balance sync complete.");
