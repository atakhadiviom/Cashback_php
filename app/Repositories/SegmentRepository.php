<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class SegmentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * Recency / frequency / monetary aggregates per customer (active purchases only).
     *
     * @return array<int, array<string, mixed>>
     */
    public function purchaseAggregates(int $limit = 0): array
    {
        $sql = "SELECT c.id AS customer_id,
                       MAX(p.created_at) AS last_at,
                       COUNT(*) AS frequency,
                       COALESCE(SUM(p.amount), 0) AS monetary
                FROM purchases p
                JOIN customers c ON c.id = p.customer_id
                WHERE p.status = 'active' AND c.deleted_at IS NULL
                GROUP BY c.id";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function catalog(): array
    {
        try {
            return $this->pdo->query(
                'SELECT * FROM customer_segments WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param list<array{customer_id:int, segment_code:string, r_score:int, f_score:int, m_score:int,
     *                  rfm_score:int, recency_days:int, frequency:int, monetary:float}> $rows
     */
    public function upsertMembers(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $sql = 'INSERT INTO customer_segment_members
                    (customer_id, segment_code, r_score, f_score, m_score, rfm_score, recency_days, frequency, monetary, computed_at)
                VALUES
                    (:customer_id, :segment_code, :r_score, :f_score, :m_score, :rfm_score, :recency_days, :frequency, :monetary, :computed_at)
                ON DUPLICATE KEY UPDATE
                    segment_code = VALUES(segment_code),
                    r_score = VALUES(r_score),
                    f_score = VALUES(f_score),
                    m_score = VALUES(m_score),
                    rfm_score = VALUES(rfm_score),
                    recency_days = VALUES(recency_days),
                    frequency = VALUES(frequency),
                    monetary = VALUES(monetary),
                    computed_at = VALUES(computed_at)';

        $now = \current_datetime();
        $saved = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            $pdo = $this->pdo;
            $ownTransaction = !$pdo->inTransaction();
            if ($ownTransaction) {
                $pdo->beginTransaction();
            }
            try {
                $stmt = $pdo->prepare($sql);
                foreach ($chunk as $row) {
                    $stmt->execute([
                        'customer_id' => (int) $row['customer_id'],
                        'segment_code' => (string) $row['segment_code'],
                        'r_score' => (int) $row['r_score'],
                        'f_score' => (int) $row['f_score'],
                        'm_score' => (int) $row['m_score'],
                        'rfm_score' => (int) $row['rfm_score'],
                        'recency_days' => (int) $row['recency_days'],
                        'frequency' => (int) $row['frequency'],
                        'monetary' => (float) $row['monetary'],
                        'computed_at' => $now,
                    ]);
                    $saved++;
                }
                if ($ownTransaction) {
                    $pdo->commit();
                }
            } catch (\Throwable $exception) {
                if ($ownTransaction && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        }

        return $saved;
    }

    /** @return array<string, mixed>|null */
    public function forCustomer(int $customerId): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM customer_segment_members WHERE customer_id = :id LIMIT 1');
            $stmt->execute(['id' => $customerId]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Badge map for a page of customers: [customer_id => segment row].
     *
     * @param list<int> $customerIds
     * @return array<int, array<string, mixed>>
     */
    public function mapForCustomers(array $customerIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $customerIds))));
        if ($ids === []) {
            return [];
        }
        try {
            $stmt = $this->pdo->prepare(
                'SELECT m.*, s.name AS segment_name
                 FROM customer_segment_members m
                 LEFT JOIN customer_segments s ON s.code = m.segment_code
                 WHERE m.customer_id IN (' . implode(',', $ids) . ')'
            );
            $stmt->execute();
            $map = [];
            foreach ($stmt->fetchAll() as $row) {
                $map[(int) $row['customer_id']] = $row;
            }

            return $map;
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function distribution(): array
    {
        try {
            return $this->pdo->query(
                'SELECT m.segment_code,
                        COUNT(*) AS members,
                        COALESCE(SUM(m.monetary), 0) AS revenue,
                        COALESCE(AVG(m.monetary), 0) AS average,
                        COALESCE(AVG(m.frequency), 0) AS average_frequency,
                        COALESCE(AVG(m.recency_days), 0) AS average_recency
                 FROM customer_segment_members m
                 GROUP BY m.segment_code
                 ORDER BY revenue DESC'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<int> */
    public function memberIds(string $segmentCode, int $limit = 0): array
    {
        try {
            $sql = 'SELECT customer_id FROM customer_segment_members WHERE segment_code = :code ORDER BY monetary DESC';
            if ($limit > 0) {
                $sql .= ' LIMIT ' . (int) $limit;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['code' => $segmentCode]);

            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (\Throwable) {
            return [];
        }
    }

    public function lastComputedAt(): ?string
    {
        try {
            $value = $this->pdo->query('SELECT MAX(computed_at) FROM customer_segment_members')->fetchColumn();

            return is_string($value) && $value !== '' ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Customers that never purchased do not belong to a segment; drop stale rows.
     */
    public function prune(): int
    {
        try {
            return (int) $this->pdo->exec(
                "DELETE m FROM customer_segment_members m
                 LEFT JOIN purchases p ON p.customer_id = m.customer_id AND p.status = 'active'
                 WHERE p.id IS NULL"
            );
        } catch (\Throwable) {
            return 0;
        }
    }
}
