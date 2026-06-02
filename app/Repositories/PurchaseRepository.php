<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PurchaseRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(int $customerId, float $amount, float $cashback, int $createdBy): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO purchases (customer_id, amount, cashback_amount, created_by, created_at) VALUES (:customer_id, :amount, :cashback_amount, :created_by, :created_at)');
        $stmt->execute([
            'customer_id' => $customerId,
            'amount' => $amount,
            'cashback_amount' => $cashback,
            'created_by' => $createdBy,
            'created_at' => \current_datetime(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->pdo->prepare('SELECT p.*, u.name AS created_by_name FROM purchases p LEFT JOIN users u ON u.id = p.created_by WHERE p.customer_id = :customer_id ORDER BY p.id DESC');
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }
}
