<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ApiKeyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT k.*, u.name AS created_by_name FROM api_keys k LEFT JOIN users u ON u.id = k.created_by ORDER BY k.id DESC')->fetchAll();
    }

    public function findByPrefix(string $prefix): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM api_keys WHERE key_prefix = :prefix AND is_active = 1 LIMIT 1');
        $stmt->execute(['prefix' => $prefix]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $keyHash, string $prefix, int $createdBy): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO api_keys (name, key_hash, key_prefix, is_active, created_by, created_at)
             VALUES (:name, :key_hash, :key_prefix, 1, :created_by, :created_at)'
        );
        $stmt->execute([
            'name' => $name,
            'key_hash' => $keyHash,
            'key_prefix' => $prefix,
            'created_by' => $createdBy,
            'created_at' => \current_datetime(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function touchUsed(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE api_keys SET last_used_at = :last_used_at WHERE id = :id');
        $stmt->execute(['last_used_at' => \current_datetime(), 'id' => $id]);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE api_keys SET is_active = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
