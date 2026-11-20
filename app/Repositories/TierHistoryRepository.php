<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TierHistoryRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function record(
        int $customerId,
        ?int $oldTierId,
        ?int $newTierId,
        string $basis,
        float $spend,
        string $reason = 'recalc',
        ?int $actorId = null
    ): void {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO customer_tier_history
                    (customer_id, old_tier_id, new_tier_id, basis, spend, reason, actor_id, created_at)
                 VALUES
                    (:customer_id, :old_tier_id, :new_tier_id, :basis, :spend, :reason, :actor_id, :created_at)'
            );
            $stmt->execute([
                'customer_id' => $customerId,
                'old_tier_id' => $oldTierId,
                'new_tier_id' => $newTierId,
                'basis' => $basis,
                'spend' => $spend,
                'reason' => $reason,
                'actor_id' => $actorId,
                'created_at' => \current_datetime(),
            ]);
        } catch (\Throwable) {
            // Migration 073 not applied yet: tier history is best-effort.
        }
    }

    public function forCustomer(int $customerId, int $limit = 20): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT h.*, o.name AS old_tier_name, n.name AS new_tier_name
                 FROM customer_tier_history h
                 LEFT JOIN customer_tiers o ON o.id = h.old_tier_id
                 LEFT JOIN customer_tiers n ON n.id = h.new_tier_id
                 WHERE h.customer_id = :customer_id
                 ORDER BY h.id DESC LIMIT ' . max(1, $limit)
            );
            $stmt->execute(['customer_id' => $customerId]);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array{date_from?:string, date_to?:string, customer_id?:string} $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(array $filters, int $limit = 500): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT h.*, c.first_name, c.last_name, c.phone_number,
                       o.name AS old_tier_name, n.name AS new_tier_name
                FROM customer_tier_history h
                JOIN customers c ON c.id = h.customer_id
                LEFT JOIN customer_tiers o ON o.id = h.old_tier_id
                LEFT JOIN customer_tiers n ON n.id = h.new_tier_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY h.id DESC LIMIT ' . max(1, $limit);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array{date_from?:string, date_to?:string, customer_id?:string} $filters
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function filters(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];
        if (!empty($filters['date_from'])) {
            $where[] = 'h.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'h.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (($filters['customer_id'] ?? '') !== '') {
            $where[] = 'h.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }

        return [$where, $params];
    }
}
