<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ContractRenewalSmsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function exists(int $customerId, string $contractEndsAt, int $reminderDays): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM contract_renewal_sms_history
             WHERE customer_id = :customer_id AND contract_ends_at = :contract_ends_at AND reminder_days = :reminder_days
             LIMIT 1'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'contract_ends_at' => $contractEndsAt,
            'reminder_days' => $reminderDays,
        ]);
        return (bool) $stmt->fetch();
    }

    public function insert(int $customerId, string $contractEndsAt, int $reminderDays, ?int $smsLogId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO contract_renewal_sms_history (customer_id, contract_ends_at, reminder_days, sms_log_id, created_at)
             VALUES (:customer_id, :contract_ends_at, :reminder_days, :sms_log_id, :created_at)'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'contract_ends_at' => $contractEndsAt,
            'reminder_days' => $reminderDays,
            'sms_log_id' => $smsLogId,
            'created_at' => \current_datetime(),
        ]);
    }
}
