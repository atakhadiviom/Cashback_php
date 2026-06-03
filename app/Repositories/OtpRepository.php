<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class OtpRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(string $phone, string $codeHash, ?int $customerId, string $expiresAt, ?string $ip): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO otp_codes (phone_number, code_hash, customer_id, expires_at, ip_address, created_at)
             VALUES (:phone_number, :code_hash, :customer_id, :expires_at, :ip_address, :created_at)'
        );
        $stmt->execute([
            'phone_number' => $phone,
            'code_hash' => $codeHash,
            'customer_id' => $customerId,
            'expires_at' => $expiresAt,
            'ip_address' => $ip,
            'created_at' => \current_datetime(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function latestValid(string $phone): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM otp_codes WHERE phone_number = :phone AND expires_at > :now ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['phone' => $phone, 'now' => \current_datetime()]);
        return $stmt->fetch() ?: null;
    }

    public function incrementAttempts(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countRecentByPhone(string $phone, int $hours): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM otp_codes WHERE phone_number = :phone AND created_at >= DATE_SUB(:now, INTERVAL :hours HOUR)'
        );
        $stmt->execute(['phone' => $phone, 'now' => \current_datetime(), 'hours' => $hours]);
        return (int) $stmt->fetchColumn();
    }
}
