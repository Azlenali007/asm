<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core Database Handler (Singleton PDO with Transaction Safety)
 */

namespace Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private function __construct()
    {
        $configPath = dirname(__DIR__) . '/config/database.php';
        if (!file_exists($configPath)) {
            throw new Exception("Database configuration not found.");
        }

        $config = require $configPath;
        $dsn = sprintf(
            "%s:host=%s;port=%d;dbname=%s;charset=%s",
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            // Write to system log without leaking credentials
            if (class_exists('\\Core\\Logger')) {
                Logger::error("Database connection failed: " . $e->getMessage());
            }
            throw new Exception("Database connection error. Please verify server settings.");
        }
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
        self::query($sql, $data);

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
        return self::pdo()->rollBack();
    }

    /**
     * Prevent clone & unserialize
     */
    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton"); }
}
