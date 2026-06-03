<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class SystemUserService
{
    public static function actorId(): int
    {
        $authId = Auth::id();
        if ($authId) {
            return $authId;
        }
        static $fallback;
        if ($fallback === null) {
            $fallback = (int) Database::pdo()->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1")->fetchColumn();
            if ($fallback <= 0) {
                $fallback = (int) Database::pdo()->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
            }
        }
        return $fallback > 0 ? $fallback : 1;
    }
}
