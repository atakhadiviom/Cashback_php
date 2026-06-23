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

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO customer_tiers (name, min_lifetime_spend, cashback_percent, sort_order, created_at)
             VALUES (:name, :min_lifetime_spend, :cashback_percent, :sort_order, :created_at)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE customer_tiers SET name = :name, min_lifetime_spend = :min_lifetime_spend,
             cashback_percent = :cashback_percent, sort_order = :sort_order WHERE id = :id'
        );
        $stmt->execute($data);
    }
}
