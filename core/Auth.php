<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core Authentication Manager
 */

namespace Core;

use Exception;

class Auth
{
    private static ?array $cachedUser = null;

    /**
     * Authenticate user credentials
     */
    public static function attempt(string $usernameOrEmail, string $password): bool
    {
        Session::start();
        
        $sql = "SELECT * FROM `users` WHERE (`username` = :login OR `email` = :login) LIMIT 1";
        $user = Database::fetch($sql, [':login' => $usernameOrEmail]);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        if ($user['status'] !== USER_ACTIVE) {
            Session::flash('error', 'Your account has been ' . $user['status'] . '. Contact support.');
            return false;
        }

        // Login successful - regenerate session ID to prevent fixation
        Session::regenerate();
        Session::set('user_id', (int)$user['id']);
        Session::set('user_role', $user['role']);
        self::$cachedUser = $user;

        Logger::audit("User logged in", ['user_id' => $user['id'], 'username' => $user['username']]);

        // Update last login
        Database::update('users', [
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', [':id' => $user['id']]);

        return true;
    }

    /**
     * Check if current session is logged in
     */
    public static function check(): bool
    {
        Session::start();
        return Session::has('user_id');
    }

    /**
     * Get currently authenticated user data
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = Session::get('user_id');
        $user = Database::fetch("SELECT * FROM `users` WHERE `id` = :id LIMIT 1", [':id' => $userId]);

        if (!$user || $user['status'] !== USER_ACTIVE) {
            self::logout();
            return null;
        }

        self::$cachedUser = $user;
        return $user;
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function role(): string
    {
        return Session::get('user_role') ?? ROLE_USER;
    }

    public static function isAdmin(): bool
    {
        return self::check() && self::role() === ROLE_ADMIN;
    }

    public static function isStaff(): bool
    {
        return self::check() && (self::role() === ROLE_ADMIN || self::role() === ROLE_STAFF);
    }

    public static function logout(): void
    {
        if (self::check()) {
            Logger::audit("User logged out", ['user_id' => Session::get('user_id')]);
        }
        self::$cachedUser = null;
        Session::destroy();
    }

    /**
     * Authenticate via API Key (for external API v2 calls)
     */
    public static function authenticateApiKey(string $apiKey): ?array
    {
        if (empty($apiKey)) {
            return null;
        }

        $sql = "SELECT * FROM `users` WHERE `api_key` = :api_key AND `status` = :status LIMIT 1";
        $user = Database::fetch($sql, [
            ':api_key' => $apiKey,
            ':status'  => USER_ACTIVE
        ]);

        return $user ?: null;
    }

    /**
     * Hash password securely
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
