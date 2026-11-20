<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TierRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM customer_tiers ORDER BY min_lifetime_spend DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM customer_tiers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function forLifetimeSpend(float $spend): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM customer_tiers 
             WHERE is_active = 1 
               AND min_lifetime_spend <= :spend 
               AND (max_lifetime_spend IS NULL OR max_lifetime_spend > :spend2)
             ORDER BY min_lifetime_spend DESC LIMIT 1'
        );
        $stmt->execute(['spend' => $spend, 'spend2' => $spend]);
        return $stmt->fetch() ?: null;
    }

    public function findTierBySpend(float $spend): ?array
    {
        return $this->forLifetimeSpend($spend);
    }

    /**
     * Next tier above the given spend (for the "progress to next tier" bar).
     */
    public function nextTier(float $spend): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM customer_tiers
             WHERE is_active = 1 AND min_lifetime_spend > :spend
             ORDER BY min_lifetime_spend ASC LIMIT 1'
        );
        $stmt->execute(['spend' => $spend]);

        return $stmt->fetch() ?: null;
    }

    public function customerCount(int $tierId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM customers WHERE tier_id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $tierId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Delete a tier and detach it from every customer in the same transaction.
     */
    public function delete(int $id): void
    {
        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $stmt = $this->pdo->prepare('UPDATE customers SET tier_id = NULL WHERE tier_id = :id');
            $stmt->execute(['id' => $id]);
            $stmt = $this->pdo->prepare('DELETE FROM customer_tiers WHERE id = :id');
            $stmt->execute(['id' => $id]);
            if ($ownTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($ownTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO customer_tiers (name, min_lifetime_spend, max_lifetime_spend, cashback_percent, is_active, sort_order, created_at)
             VALUES (:name, :min_lifetime_spend, :max_lifetime_spend, :cashback_percent, :is_active, :sort_order, :created_at)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE customer_tiers SET name = :name, min_lifetime_spend = :min_lifetime_spend,
             max_lifetime_spend = :max_lifetime_spend, cashback_percent = :cashback_percent,
             is_active = :is_active, sort_order = :sort_order WHERE id = :id'
        );
        $stmt->execute($data);
    }
}
