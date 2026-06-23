<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CashbackSettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function settings(): array
    {
        $row = $this->pdo->query('SELECT * FROM cashback_settings WHERE id = 1')->fetch();
        if (!$row) {
            return [];
        }

        // Safely read enabled_menus even if the column doesn't exist yet (pre-migration 017)
        $enabledRaw = null;
        if (array_key_exists('enabled_menus', $row)) {
            $enabledRaw = $row['enabled_menus'];
        } else {
            // Column missing → try a safe separate query
            try {
                $stmt = $this->pdo->prepare('SELECT enabled_menus FROM cashback_settings WHERE id = 1');
                $stmt->execute();
                $enabledRaw = $stmt->fetchColumn();
            } catch (\Throwable $e) {
                $enabledRaw = null;
            }
        }

        if (!empty($enabledRaw)) {
            $decoded = json_decode((string)$enabledRaw, true);
            $row['enabled_menus'] = is_array($decoded) ? $decoded : null;
        } else {
            $row['enabled_menus'] = null; // null = show all menus
        }

        return $row;
    }

    public function update(array $data): void
    {
        // Note: enabled_menus is handled separately in the controller for safety
        // (in case migration 017 has not been run yet on existing databases).
        $stmt = $this->pdo->prepare(
            'UPDATE cashback_settings SET
                cashback_percent = :cashback_percent,
                min_purchase_amount = :min_purchase_amount,
                max_cashback_per_purchase = :max_cashback_per_purchase,
                min_redemption_amount = :min_redemption_amount,
                max_redemption_percent_of_purchase = :max_redemption_percent_of_purchase,
                large_redemption_threshold = :large_redemption_threshold,
                birthday_bonus_amount = :birthday_bonus_amount,
                referral_bonus_amount = :referral_bonus_amount,
                duplicate_purchase_window_minutes = :duplicate_purchase_window_minutes,
                updated_at = :updated_at
            WHERE id = 1'
        );
        $stmt->execute($data);
    }

    /**
     * Safely update only the enabled_menus column.
     * Returns true if the update was applied, false if the column doesn't exist yet or on error.
     */
    public function updateEnabledMenus(?string $json): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE cashback_settings SET enabled_menus = :enabled_menus, updated_at = :updated_at WHERE id = 1'
            );
            $stmt->execute([
                'enabled_menus' => $json,
                'updated_at' => \current_datetime(),
            ]);
            return true;
        } catch (\Throwable $e) {
            // Column does not exist yet (migration 017 not run) or other DB error.
            return false;
        }
    }
}
