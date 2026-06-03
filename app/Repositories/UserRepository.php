<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE username = :username';
        $params = ['username' => $username];
        if ($exceptId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function activeOperatorsAndAdmins(): array
    {
        return $this->pdo->query('SELECT id, name, username, role, is_active, created_at FROM users ORDER BY id DESC')->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, username, password_hash, role, permissions, is_active, created_at, updated_at)
             VALUES (:name, :username, :password_hash, :role, :permissions, :is_active, :created_at, :updated_at)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = ['name = :name', 'username = :username', 'role = :role', 'permissions = :permissions', 'is_active = :is_active', 'updated_at = :updated_at'];
        if (!empty($data['password_hash'])) {
            $fields[] = 'password_hash = :password_hash';
        } else {
            unset($data['password_hash']);
        }
        $data['id'] = $id;
        $stmt = $this->pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($data);
    }

    public static function defaultPermissionsJson(string $role): string
    {
        $perms = $role === 'admin'
            ? \App\Core\Auth::permissionsFromUser(['role' => 'admin', 'permissions' => null])
            : \App\Core\Auth::permissionsFromUser(['role' => 'operator', 'permissions' => null]);
        return json_encode($perms, JSON_UNESCAPED_UNICODE);
    }
}
