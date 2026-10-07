<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core Database Handler (Singleton PDO with Transaction Safety & Auto-Recovery)
 */

namespace Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private static string $activeDriver = 'mysql';

    private function __construct()
    {
        $configPath = dirname(__DIR__) . '/config/database.php';
        $config = file_exists($configPath) ? require $configPath : [];

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int)($config['port'] ?? 3306);
        $dbname = $config['database'] ?? 'smm_panel';
        $user = $config['username'] ?? 'root';
        $pass = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        // 1. Try MySQL / MariaDB connection first
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            $options = $config['options'] ?? [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // 3 second connection timeout to prevent hanging if host unreachable
            $options[PDO::ATTR_TIMEOUT] = 3;

            $this->connection = new PDO($dsn, $user, $pass, $options);
            self::$activeDriver = 'mysql';
            return;
        } catch (PDOException $e) {
            if (class_exists('\\Core\\Logger')) {
                Logger::warning("MySQL connection unavailable: " . $e->getMessage() . ". Checking SQLite resilient fallback.");
            }
        }

        // 2. Resilient SQLite fallback (ensures panel functions seamlessly even before MySQL service is started)
        try {
            $storageDir = dirname(__DIR__) . '/storage';
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0755, true);
            }

            $sqlitePath = $storageDir . '/database.sqlite';
            $isNewDb = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;

            $this->connection = new PDO("sqlite:{$sqlitePath}", null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            self::$activeDriver = 'sqlite';

            // Auto-bootstrap schema into SQLite fallback if new
            if ($isNewDb) {
                self::bootstrapSqliteSchema($this->connection);
            }
        } catch (PDOException $e2) {
            if (class_exists('\\Core\\Logger')) {
                Logger::error("Fatal: Both MySQL and SQLite database connections failed: " . $e2->getMessage());
            }
            throw new Exception("Database could not be initialized. Please run the installer at /install/ or check config/database.php.");
        }
    }

    /**
     * Bootstraps SQLite tables and initial seeds if MySQL is offline
     */
    private static function bootstrapSqliteSchema(PDO $pdo): void
    {
        $schemaSql = <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'user',
            balance REAL NOT NULL DEFAULT 0.0000,
            spent REAL NOT NULL DEFAULT 0.0000,
            status TEXT NOT NULL DEFAULT 'active',
            api_key TEXT UNIQUE,
            custom_rates TEXT,
            two_factor_secret TEXT,
            two_factor_enabled INTEGER DEFAULT 0,
            referral_code TEXT UNIQUE,
            referred_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            icon TEXT DEFAULT 'folder',
            sort_order INTEGER DEFAULT 0,
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS providers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            api_url TEXT NOT NULL,
            api_key TEXT NOT NULL,
            balance REAL DEFAULT 0.0000,
            currency TEXT DEFAULT 'USD',
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            type TEXT DEFAULT 'default',
            rate REAL DEFAULT 0.0000,
            min_quantity INTEGER DEFAULT 10,
            max_quantity INTEGER DEFAULT 100000,
            description TEXT,
            dripfeed INTEGER DEFAULT 0,
            refill INTEGER DEFAULT 0,
            cancel INTEGER DEFAULT 0,
            provider_id INTEGER,
            provider_service_id TEXT,
            status INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            service_id INTEGER NOT NULL,
            provider_id INTEGER,
            provider_order_id TEXT,
            link TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            start_count INTEGER DEFAULT 0,
            remains INTEGER DEFAULT 0,
            charge REAL DEFAULT 0.0000,
            status TEXT DEFAULT 'pending',
            order_type TEXT DEFAULT 'default',
            custom_comments TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            method TEXT NOT NULL,
            transaction_id TEXT UNIQUE NOT NULL,
            amount REAL NOT NULL,
            fee REAL DEFAULT 0.0000,
            net_amount REAL NOT NULL,
            status TEXT DEFAULT 'pending',
            raw_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            type TEXT NOT NULL,
            description TEXT NOT NULL,
            balance_after REAL NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            priority TEXT DEFAULT 'medium',
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS ticket_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            is_admin INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT
        );

        -- Default seeds
        INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES 
        ('site_name', 'ApexSMM Enterprise'),
        ('site_currency', '$'),
        ('site_currency_code', 'USD'),
        ('min_deposit', '5.00'),
        ('max_deposit', '5000.00'),
        ('maintenance_mode', '0');

        -- Seed Default Admin: admin / Admin@123456
        INSERT OR IGNORE INTO users (id, username, email, password, role, balance, spent, status, api_key)
        VALUES (1, 'admin', 'admin@apexsmm.com', '$2y$12$E2F.yQ8t790kYxlV7A21DOZ8htaXGve2DG4qbQe8BX0d.sud8jRlm', 'admin', 500.00, 0.00, 'active', 'smm_admin_enterprise_key_998811');

        -- Seed Demo User: demouser / Demo@123456
        INSERT OR IGNORE INTO users (id, username, email, password, role, balance, spent, status, api_key)
        VALUES (2, 'demouser', 'demo@apexsmm.com', '$2y$12$NhycMHSzuk/WnV/r3h5Ye.nERI.Nl/WlRIbYyl0f00wIe.OFdqGFO', 'user', 84.50, 165.20, 'active', 'smm_demo_client_key_112233');

        -- Seed Categories
        INSERT OR IGNORE INTO categories (id, name, icon, sort_order, status) VALUES
        (1, 'Instagram Followers [Guaranteed / Real]', 'instagram', 1, 1),
        (2, 'Instagram Likes & Engagements', 'heart', 2, 1),
        (3, 'YouTube Views & Watch Time', 'youtube', 3, 1),
        (4, 'TikTok Followers & Likes', 'video', 4, 1),
        (5, 'Telegram Members & Channel Boost', 'send', 5, 1),
        (6, 'Twitter / X Followers & Retweets', 'twitter', 6, 1);

        -- Seed Services
        INSERT OR IGNORE INTO services (id, category_id, name, type, rate, min_quantity, max_quantity, description, refill, cancel, status, sort_order) VALUES
        (101, 1, 'Instagram Followers [HQ - 30 Days Auto-Refill - Non-Drop]', 'default', 0.8500, 50, 50000, 'High quality worldwide followers. Instant start (0-15m), speed: 10K/Day. Auto refill 30 days enabled.', 1, 0, 1, 1),
        (102, 1, 'Instagram Followers [Real Active - Lifetime Guarantee]', 'default', 1.4500, 100, 20000, '100% Real active looking profiles with posts and stories. 0% Drop rate with lifetime guarantee.', 1, 0, 1, 2),
        (103, 2, 'Instagram Likes [Instant - Super Fast 50K/Day]', 'default', 0.1800, 20, 100000, 'Instant delivery right after placing order. High quality accounts.', 0, 1, 1, 3),
        (104, 2, 'Instagram Custom Comments [English / Positive]', 'custom_comments', 3.2000, 10, 2000, 'Add custom comments per line. Delivered from verified-looking profiles.', 0, 0, 1, 4),
        (105, 3, 'YouTube High Retention Views [Monetizable - Real Traffic]', 'default', 1.8000, 500, 1000000, 'High watch time retention (3-5 minutes average). 100% safe for AdSense monetization.', 1, 0, 1, 5),
        (106, 3, 'YouTube Subscribers [Non-Drop - Organic Delivery]', 'default', 12.5000, 50, 10000, 'Real organic subscribers. Drop safe with 60 days refill protection.', 1, 0, 1, 6),
        (107, 4, 'TikTok Followers [Instant Delivery - Worldwide]', 'default', 0.9500, 100, 50000, 'Super fast startup, instant followers, top notch delivery speed.', 1, 0, 1, 7),
        (108, 4, 'TikTok Video Views [100% Real / Algorithmic Push]', 'default', 0.0300, 500, 5000000, 'Triggers ForYou page recommendations. Ultra cheap and instant.', 0, 1, 1, 8),
        (109, 5, 'Telegram Channel Members [0% Drop - Permanent]', 'default', 0.6500, 100, 100000, 'High quality Telegram channel & group members. Stable and permanent.', 1, 0, 1, 9),
        (110, 6, 'X / Twitter Followers [Real NFT / Crypto Profiles]', 'default', 2.1000, 50, 30000, 'Targeted Crypto / Tech followers for accounts and projects.', 1, 0, 1, 10);
SQL;
        $pdo->exec($schemaSql);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public static function pdo(): PDO
    {
        return self::getInstance()->getConnection();
    }

    public static function getDriver(): string
    {
        return self::$activeDriver;
    }

    /**
     * Execute a prepared query and return PDOStatement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all matching rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single column scalar value
     */
    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert a record and return last insert ID
     */
    public static function insert(string $table, array $data): int|string
    {
        $keys = array_keys($data);
        $fields = implode(', ', array_map(fn($k) => "`$k`", $keys));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", $keys));

        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";
        $params = [];
        foreach ($data as $k => $v) {
            $params[":$k"] = $v;
        }
        self::query($sql, $params);

        return self::pdo()->lastInsertId();
    }

    /**
     * Update records
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $val) {
            $fields[] = "`$key` = :upd_$key";
            $params["upd_$key"] = $val;
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $fields) . " WHERE {$where}";
        $stmt = self::query($sql, array_merge($params, $whereParams));

        return $stmt->rowCount();
    }

    /**
     * Delete records
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Transactions
     */
    public static function beginTransaction(): bool
    {
        return self::pdo()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::pdo()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::pdo()->inTransaction()) {
            return self::pdo()->rollBack();
        }
        return false;
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton"); }
}
