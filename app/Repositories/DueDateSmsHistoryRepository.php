<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class DueDateSmsHistoryRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function exists(int $dueDateId, string $reminderKind): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM due_date_sms_history
             WHERE due_date_id = :due_date_id AND reminder_kind = :reminder_kind
             LIMIT 1'
        );
        $stmt->execute([
            'due_date_id' => $dueDateId,
            'reminder_kind' => $reminderKind,
        ]);
        return (bool) $stmt->fetch();
    }

    public function insert(int $dueDateId, string $reminderKind, ?int $smsLogId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO due_date_sms_history (due_date_id, reminder_kind, sms_log_id, created_at)
             VALUES (:due_date_id, :reminder_kind, :sms_log_id, :created_at)'
        );
        $stmt->execute([
            'due_date_id' => $dueDateId,
            'reminder_kind' => $reminderKind,
            'sms_log_id' => $smsLogId,
            'created_at' => \current_datetime(),
        ]);
    }
}
