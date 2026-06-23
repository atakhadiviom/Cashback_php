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
        if ($row && !empty($row['enabled_menus'])) {
            $decoded = json_decode($row['enabled_menus'], true);
            $row['enabled_menus'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['enabled_menus'] = null; // null means all menus enabled
        }
        return $row ?: [];
    }

    public function update(array $data): void
    {
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
                enabled_menus = :enabled_menus,
                updated_at = :updated_at
            WHERE id = 1'
        );
        $stmt->execute($data);
    }
}
