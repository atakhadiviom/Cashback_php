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

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO purchases (customer_id, amount, cashback_amount, cashback_percent_applied, status, invoice_ref, promotion_id, idempotency_key, created_by, created_at)
             VALUES (:customer_id, :amount, :cashback_amount, :cashback_percent_applied, :status, :invoice_ref, :promotion_id, :idempotency_key, :created_by, :created_at)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT p.*, u.name AS created_by_name FROM purchases p LEFT JOIN users u ON u.id = p.created_by WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function forCustomer(int $customerId, bool $includeVoided = true): array
    {
        $sql = 'SELECT p.*, u.name AS created_by_name FROM purchases p LEFT JOIN users u ON u.id = p.created_by WHERE p.customer_id = :customer_id';
        if (!$includeVoided) {
            $sql .= " AND p.status = 'active'";
        }
        $sql .= ' ORDER BY p.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

    public function existsActiveByInvoiceRef(string $ref, ?int $exceptId = null): bool
    {
        $sql = "SELECT id FROM purchases WHERE invoice_ref = :invoice_ref AND status = 'active'";
        $params = ['invoice_ref' => $ref];
        if ($exceptId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function findByIdempotencyKey(string $key): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM purchases WHERE idempotency_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        return $stmt->fetch() ?: null;
    }

    public function recentDuplicate(int $customerId, float $amount, int $windowMinutes): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM purchases WHERE customer_id = :customer_id AND amount = :amount AND status = 'active'
             AND created_at >= DATE_SUB(:now, INTERVAL :window MINUTE) ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'amount' => $amount,
            'now' => \current_datetime(),
            'window' => $windowMinutes,
        ]);
        return $stmt->fetch() ?: null;
    }

    public function voidPurchase(int $id, string $reason): void
    {
        $stmt = $this->pdo->prepare("UPDATE purchases SET status = 'voided', void_reason = :void_reason WHERE id = :id");
        $stmt->execute(['void_reason' => $reason, 'id' => $id]);
    }

    public function lifetimeSpend(int $customerId): float
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM purchases WHERE customer_id = :customer_id AND status = 'active'");
        $stmt->execute(['customer_id' => $customerId]);
        return (float) $stmt->fetchColumn();
    }

    public function lifetimeCashbackEarned(int $customerId): float
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(cashback_amount), 0) FROM purchases WHERE customer_id = :customer_id AND status = 'active'");
        $stmt->execute(['customer_id' => $customerId]);
        return (float) $stmt->fetchColumn();
    }

    public function activePurchaseCount(int $customerId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM purchases WHERE customer_id = :customer_id AND status = 'active'");
        $stmt->execute(['customer_id' => $customerId]);
        return (int) $stmt->fetchColumn();
    }

    public function firstActivePurchaseId(int $customerId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM purchases WHERE customer_id = :customer_id AND status = 'active' ORDER BY id ASC LIMIT 1");
        $stmt->execute(['customer_id' => $customerId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }
}
