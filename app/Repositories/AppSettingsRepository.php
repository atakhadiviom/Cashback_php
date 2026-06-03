<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AppSettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->ensureTable();
    }

    public function cashbackPercentage(): float
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = "cashback_percentage"');
        $stmt->execute();
        $value = $stmt->fetchColumn();
        if ($value === false || !is_numeric($value)) {
            return 5.0;
        }

        return max(0.0, min(100.0, (float) $value));
    }

    public function updateCashbackPercentage(float $percentage): void
    {
        $percentage = max(0.0, min(100.0, $percentage));
        $stmt = $this->pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_at)
             VALUES ("cashback_percentage", :setting_value, :updated_at)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)'
        );
        $stmt->execute([
            'setting_value' => number_format($percentage, 2, '.', ''),
            'updated_at' => \current_datetime(),
        ]);
    }

    private function ensureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO app_settings (setting_key, setting_value, updated_at)
             VALUES ("cashback_percentage", "5.00", :updated_at)'
        );
        $stmt->execute(['updated_at' => \current_datetime()]);
    }
}
