<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/** Guards against sending a customer more than one expiry warning per expiry month. */
final class CashbackExpirySmsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function exists(int $customerId, string $expiryMonth): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM cashback_expiry_sms_history WHERE customer_id = :customer_id AND expiry_month = :expiry_month LIMIT 1'
        );
        $stmt->execute(['customer_id' => $customerId, 'expiry_month' => $expiryMonth]);

        return (bool) $stmt->fetchColumn();
    }

    public function insert(int $customerId, string $expiryMonth, float $amount, ?int $smsLogId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cashback_expiry_sms_history (customer_id, expiry_month, amount, sms_log_id, created_at)
             VALUES (:customer_id, :expiry_month, :amount, :sms_log_id, :created_at)
             ON DUPLICATE KEY UPDATE amount = VALUES(amount), sms_log_id = VALUES(sms_log_id)'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'expiry_month' => $expiryMonth,
            'amount' => $amount,
            'sms_log_id' => $smsLogId,
            'created_at' => \current_datetime(),
        ]);
    }
}
