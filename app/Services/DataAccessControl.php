<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

final class DataAccessControl
{
    /** @param list<string> $where @param array<string, mixed> $params */
    public static function applyOwnerScope(array &$where, array &$params, string $ownerColumn): void
    {
        $ids = self::visibleUserIds();
        if ($ids === null) {
            return;
        }
        if ($ids === []) {
            $where[] = '1 = 0';
            return;
        }
        $placeholders = [];
        foreach (array_values($ids) as $index => $id) {
            $key = 'acl_user_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        $where[] = $ownerColumn . ' IN (' . implode(', ', $placeholders) . ')';
    }

    public static function canViewOwner(?int $ownerId): bool
    {
        if ($ownerId === null || Auth::isAdmin()) {
            return true;
        }
        $ids = self::visibleUserIds();
        return $ids === null || in_array($ownerId, $ids, true);
    }

    public static function canModifyOwner(?int $ownerId): bool
    {
        if ($ownerId === null || Auth::isAdmin()) {
            return true;
        }
        $currentUserId = Auth::id();
        if ($currentUserId !== null && $ownerId === $currentUserId) {
            return true;
        }
        return self::canModifyOthers() && self::canViewOwner($ownerId);
    }

    /** @return list<int>|null Null means unrestricted. */
    public static function visibleUserIds(): ?array
    {
        if (!Auth::check() || Auth::isAdmin()) {
            return null;
        }
        $currentUserId = Auth::id();
        if ($currentUserId === null) {
            return [];
        }
        $permissions = Auth::user()['permissions'] ?? [];
        if (($permissions['data_access_scope'] ?? 'self') === 'all') {
            return null;
        }
        $ids = [$currentUserId];
        if (($permissions['data_access_scope'] ?? 'self') === 'selected') {
            foreach (($permissions['data_access_user_ids'] ?? []) as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }
        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }

    private static function canModifyOthers(): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }
        $permissions = Auth::user()['permissions'] ?? [];
        return !empty($permissions['data_access_can_modify_others']);
    }
}
