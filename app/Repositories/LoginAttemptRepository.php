<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LoginAttemptRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function record(string $username, ?string $ip, bool $success): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (username, ip_address, success, created_at) VALUES (:username, :ip_address, :success, :created_at)'
        );
        $stmt->execute([
            'username' => $username,
            'ip_address' => $ip,
            'success' => $success ? 1 : 0,
            'created_at' => \current_datetime(),
        ]);
    }

    public function failedCountSince(string $username, int $minutes): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE username = :username AND success = 0
             AND created_at >= DATE_SUB(:now, INTERVAL :minutes MINUTE)'
        );
        $stmt->execute(['username' => $username, 'now' => \current_datetime(), 'minutes' => $minutes]);
        return (int) $stmt->fetchColumn();
    }
}
