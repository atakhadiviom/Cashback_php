<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ActivityLogRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(?int $userId, string $type, string $description, ?int $customerId = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO activity_logs (user_id, activity_type, description, customer_id, ip_address, created_at) VALUES (:user_id, :activity_type, :description, :customer_id, :ip_address, :created_at)');
        $stmt->execute([
            'user_id' => $userId,
            'activity_type' => $type,
            'description' => mb_substr($description, 0, 500),
            'customer_id' => $customerId,
            'ip_address' => \request_ip(),
            'created_at' => \current_datetime(),
        ]);
    }

    public function search(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['date_from'])) {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['activity_type'])) {
            $where[] = 'a.activity_type = :activity_type';
            $params['activity_type'] = $filters['activity_type'];
        }
        if (!empty($filters['customer'])) {
            $where[] = '(' . \sql_normalize_persian('c.first_name') . ' LIKE :customer1 OR ' . \sql_normalize_persian('c.last_name') . ' LIKE :customer2 OR c.national_code LIKE :customer3)';
            $term = \search_like_term((string) $filters['customer']);
            $params['customer1'] = $term;
            $params['customer2'] = $term;
            $params['customer3'] = $term;
        }
        $sql = 'SELECT a.*, u.name AS user_name, c.first_name, c.last_name, c.national_code FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id LEFT JOIN customers c ON c.id = a.customer_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY a.id DESC LIMIT 300';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
