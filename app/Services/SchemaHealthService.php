<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class SchemaHealthService
{
    /** @return list<array{label: string, table: string, migration: string, ok: bool}> */
    public function checks(): array
    {
        $checks = [
            [
                'label' => 'سررسیدهای پرداخت',
                'table' => 'payment_due_dates',
                'migration' => '018_payment_due_dates.sql',
            ],
            [
                'label' => 'تاریخچه پیامک سررسید',
                'table' => 'due_date_sms_history',
                'migration' => '018_payment_due_dates.sql',
            ],
        ];

        try {
            $pdo = Database::pdo();
            foreach ($checks as &$check) {
                $check['ok'] = $this->tableExists($pdo, $check['table']);
            }
            unset($check);
        } catch (\Throwable) {
            foreach ($checks as &$check) {
                $check['ok'] = false;
            }
            unset($check);
        }

        return $checks;
    }

    public function hasMissingTables(): bool
    {
        foreach ($this->checks() as $check) {
            if (!$check['ok']) {
                return true;
            }
        }

        return false;
    }

    /** @return array{applied: list<string>, repaired: list<string>} */
    public function runPendingMigrations(): array
    {
        $migrationDir = dirname(__DIR__, 2) . '/database/migrations';

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        return (new MigrationRunner(Database::pdo()))->runPending($migrationDir);
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));

        return (bool) ($stmt?->fetch());
    }
}
