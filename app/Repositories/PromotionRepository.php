<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PromotionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function activeNow(): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM promotions WHERE is_active = 1 AND starts_at <= :now AND ends_at >= :now ORDER BY id DESC LIMIT 1'
        );
        $now = \current_datetime();
        $stmt->execute(['now' => $now]);
        return $stmt->fetch() ?: null;
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM promotions ORDER BY id DESC')->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO promotions (name, percent_bonus, fixed_bonus, starts_at, ends_at, is_active, created_at)
             VALUES (:name, :percent_bonus, :fixed_bonus, :starts_at, :ends_at, :is_active, :created_at)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE promotions SET name = :name, percent_bonus = :percent_bonus, fixed_bonus = :fixed_bonus,
             starts_at = :starts_at, ends_at = :ends_at, is_active = :is_active WHERE id = :id'
        );
        $stmt->execute($data);
    }
}
