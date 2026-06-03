<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private const DEFAULT_OPERATOR_PERMISSIONS = [
        'purchase' => true,
        'reduce_wallet' => true,
        'export' => true,
        'void_purchase' => false,
        'manage_settings' => false,
        'manage_users' => false,
        'import_customers' => false,
        'manage_api' => false,
        'manage_loyalty' => false,
    ];

    private const ADMIN_PERMISSIONS = [
        'purchase' => true,
        'reduce_wallet' => true,
        'export' => true,
        'void_purchase' => true,
        'manage_settings' => true,
        'manage_users' => true,
        'import_customers' => true,
        'manage_api' => true,
        'manage_loyalty' => true,
    ];

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    public static function check(): bool
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }
        self::enforceIdleTimeout();
        return $_SESSION['user'] !== null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::isAdmin()) {
            return true;
        }
        $perms = $_SESSION['user']['permissions'] ?? self::DEFAULT_OPERATOR_PERMISSIONS;
        return !empty($perms[$permission]);
    }

    /** @return array<string, bool> */
    public static function permissionsFromUser(array $user): array
    {
        if ($user['role'] === 'admin') {
            return self::ADMIN_PERMISSIONS;
        }
        if (!empty($user['permissions'])) {
            $decoded = is_string($user['permissions']) ? json_decode($user['permissions'], true) : $user['permissions'];
            if (is_array($decoded)) {
                return array_merge(self::DEFAULT_OPERATOR_PERMISSIONS, $decoded);
            }
        }
        return self::DEFAULT_OPERATOR_PERMISSIONS;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
            'role' => $user['role'],
            'permissions' => self::permissionsFromUser($user),
        ];
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public static function portalCustomerId(): ?int
    {
        return isset($_SESSION['portal_customer_id']) ? (int) $_SESSION['portal_customer_id'] : null;
    }

    public static function loginPortal(int $customerId): void
    {
        $_SESSION['portal_customer_id'] = $customerId;
    }

    public static function logoutPortal(): void
    {
        unset($_SESSION['portal_customer_id']);
    }

    private static function enforceIdleTimeout(): void
    {
        $lifetime = (int) \config_value('security.session_lifetime_minutes', 120);
        if ($lifetime <= 0) {
            return;
        }
        $now = time();
        $last = (int) ($_SESSION['last_activity'] ?? $now);
        if ($now - $last > $lifetime * 60) {
            self::logout();
            return;
        }
        $_SESSION['last_activity'] = $now;
    }
}
