<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class WalletRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(int $customerId, string $type, float $amount, float $balanceAfter, ?string $reason, ?int $purchaseId, int $createdBy): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO wallet_transactions (customer_id, type, amount, balance_after, reason, purchase_id, created_by, created_at) VALUES (:customer_id, :type, :amount, :balance_after, :reason, :purchase_id, :created_by, :created_at)');
        $stmt->execute([
            'customer_id' => $customerId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reason' => $reason,
            'purchase_id' => $purchaseId,
            'created_by' => $createdBy,
            'created_at' => \current_datetime(),
        ]);
    }

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->pdo->prepare('SELECT w.*, u.name AS created_by_name FROM wallet_transactions w LEFT JOIN users u ON u.id = w.created_by WHERE w.customer_id = :customer_id ORDER BY w.id DESC');
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }
}
