<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataAccessControl;
use PDO;

final class ClubReportRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * Headline numbers for the club dashboard.
     *
     * @return array<string, float|int>
     */
    public function dashboard(): array
    {
        [$ownerWhere, $ownerParams] = $this->ownerWhere('c.created_by');
        $start30 = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $start90 = date('Y-m-d 00:00:00', strtotime('-90 days'));
        $monthStart = date('Y-m-01 00:00:00');
        $now = \current_datetime();

        $kpi = [
            'members' => (int) $this->scalar('SELECT COUNT(*) FROM customers c WHERE c.deleted_at IS NULL' . $ownerWhere, $ownerParams),
            'active_90' => (int) $this->scalar(
                "SELECT COUNT(DISTINCT p.customer_id) FROM purchases p JOIN customers c ON c.id = p.customer_id
                 WHERE p.status = 'active' AND c.deleted_at IS NULL AND p.created_at >= :since" . $ownerWhere,
                array_merge(['since' => $start90], $ownerParams)
            ),
            'new_this_month' => (int) $this->scalar(
                'SELECT COUNT(*) FROM customers c WHERE c.deleted_at IS NULL AND c.created_at >= :since' . $ownerWhere,
                array_merge(['since' => $monthStart], $ownerParams)
            ),
            'wallet_liability' => (float) $this->scalar(
                'SELECT COALESCE(SUM(c.wallet_balance), 0) FROM customers c WHERE c.deleted_at IS NULL' . $ownerWhere,
                $ownerParams
            ),
            'issued_30' => (float) $this->scalar(
                "SELECT COALESCE(SUM(p.cashback_amount), 0) FROM purchases p JOIN customers c ON c.id = p.customer_id
                 WHERE p.status = 'active' AND c.deleted_at IS NULL AND p.created_at >= :since" . $ownerWhere,
                array_merge(['since' => $start30], $ownerParams)
            ),
            'redeemed_30' => (float) $this->scalar(
                "SELECT COALESCE(SUM(w.amount), 0) FROM wallet_transactions w JOIN customers c ON c.id = w.customer_id
                 WHERE w.type = 'reduction' AND c.deleted_at IS NULL AND w.created_at >= :since" . $ownerWhere,
                array_merge(['since' => $start30], $ownerParams)
            ),
            'expired_30' => (float) $this->scalar(
                "SELECT COALESCE(SUM(w.amount), 0) FROM wallet_transactions w JOIN customers c ON c.id = w.customer_id
                 WHERE w.type = 'expiry' AND c.deleted_at IS NULL AND w.created_at >= :since" . $ownerWhere,
                array_merge(['since' => $start30], $ownerParams)
            ),
            'purchase_total_30' => (float) $this->scalar(
                "SELECT COALESCE(SUM(p.amount), 0) FROM purchases p JOIN customers c ON c.id = p.customer_id
                 WHERE p.status = 'active' AND c.deleted_at IS NULL AND p.created_at >= :since" . $ownerWhere,
                array_merge(['since' => $start30], $ownerParams)
            ),
            'basket_30' => (float) $this->scalar(
                "SELECT COALESCE(AVG(p.amount), 0) FROM purchases p JOIN customers c ON c.id = p.customer_id
                 WHERE p.status = 'active' AND c.deleted_at IS NULL AND p.created_at >= :since" . $ownerWhere,
                array_merge(['since' => $start30], $ownerParams)
            ),
            'segmented' => (int) $this->scalar('SELECT COUNT(*) FROM customer_segment_members', [], 0),
            'last_segment_at' => (string) $this->scalar('SELECT MAX(computed_at) FROM customer_segment_members', [], ''),
            'generated_at' => $now,
        ];

        $kpi['redemption_rate'] = $this->percent((float) $kpi['redeemed_30'], (float) $kpi['issued_30']);

        return $kpi;
    }

    /**
     * Purchase aggregates per customer for a period.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function purchaseReport(array $filters, int $limit = 500): array
    {
        $from = (string) ($filters['date_from'] ?? date('Y-m-01'));
        $to = (string) ($filters['date_to'] ?? date('Y-m-d'));

        $where = ['c.deleted_at IS NULL'];
        $params = ['date_from' => $from . ' 00:00:00', 'date_to' => $to . ' 23:59:59'];

        if (($filters['tier_id'] ?? '') !== '') {
            if ((string) $filters['tier_id'] === 'none') {
                $where[] = 'c.tier_id IS NULL';
            } else {
                $where[] = 'c.tier_id = :tier_id';
                $params['tier_id'] = (int) $filters['tier_id'];
            }
        }
        if (($filters['segment'] ?? '') !== '') {
            $where[] = 'm.segment_code = :segment';
            $params['segment'] = (string) $filters['segment'];
        }
        if (($filters['tag_id'] ?? '') !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM customer_tag_assignments ta WHERE ta.customer_id = c.id AND ta.tag_id = :tag_id)';
            $params['tag_id'] = (int) $filters['tag_id'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(' . \sql_normalize_persian('c.first_name') . ' LIKE :q1 OR ' . \sql_normalize_persian('c.last_name') . ' LIKE :q2 OR c.phone_number LIKE :q3)';
            $term = \search_like_term((string) $filters['q']);
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
        }
        DataAccessControl::applyOwnerScope($where, $params, 'c.created_by');

        $having = '';
        if ((float) ($filters['min_total'] ?? 0) > 0) {
            $having = ' HAVING COALESCE(SUM(p.amount), 0) >= :min_total';
            $params['min_total'] = (float) $filters['min_total'];
        }

        $sql = 'SELECT c.id, c.first_name, c.last_name, c.phone_number, c.tier_id,
                       t.name AS tier_name, m.segment_code,
                       COUNT(p.id) AS purchases,
                       COALESCE(SUM(p.amount), 0) AS total,
                       COALESCE(SUM(p.cashback_amount), 0) AS cashback,
                       COALESCE(AVG(p.amount), 0) AS average,
                       MAX(p.created_at) AS last_purchase
                FROM customers c
                JOIN purchases p ON p.customer_id = c.id AND p.status = \'active\'
                     AND p.created_at BETWEEN :date_from AND :date_to
                LEFT JOIN customer_tiers t ON t.id = c.tier_id
                LEFT JOIN customer_segment_members m ON m.customer_id = c.id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY c.id, c.first_name, c.last_name, c.phone_number, c.tier_id, t.name, m.segment_code'
                . $having .
                ' ORDER BY total DESC LIMIT ' . max(1, $limit);

        return $this->rows($sql, $params);
    }

    /** @return array{customers: int, purchases: int, total: float, cashback: float, average: float} */
    public function purchaseSummary(array $filters): array
    {
        $from = (string) ($filters['date_from'] ?? date('Y-m-01'));
        $to = (string) ($filters['date_to'] ?? date('Y-m-d'));
        [$ownerWhere, $ownerParams] = $this->ownerWhere('c.created_by');

        $sql = "SELECT COUNT(DISTINCT p.customer_id) AS customers, COUNT(*) AS purchases,
                       COALESCE(SUM(p.amount), 0) AS total,
                       COALESCE(SUM(p.cashback_amount), 0) AS cashback,
                       COALESCE(AVG(p.amount), 0) AS average
                FROM purchases p
                JOIN customers c ON c.id = p.customer_id
                WHERE p.status = 'active' AND c.deleted_at IS NULL
                  AND p.created_at BETWEEN :date_from AND :date_to" . $ownerWhere;

        $row = $this->one($sql, array_merge([
            'date_from' => $from . ' 00:00:00',
            'date_to' => $to . ' 23:59:59',
        ], $ownerParams));

        return [
            'customers' => (int) ($row['customers'] ?? 0),
            'purchases' => (int) ($row['purchases'] ?? 0),
            'total' => (float) ($row['total'] ?? 0),
            'cashback' => (float) ($row['cashback'] ?? 0),
            'average' => (float) ($row['average'] ?? 0),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function tierDistribution(): array
    {
        $sql = 'SELECT t.id, t.name, t.cashback_percent, t.min_lifetime_spend,
                       COUNT(c.id) AS customers,
                       COALESCE(SUM(c.wallet_balance), 0) AS wallet
                FROM customer_tiers t
                LEFT JOIN customers c ON c.tier_id = t.id AND c.deleted_at IS NULL
                GROUP BY t.id, t.name, t.cashback_percent, t.min_lifetime_spend
                ORDER BY t.min_lifetime_spend ASC';

        return $this->rows($sql, []);
    }

    public function tierlessCustomers(): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL AND tier_id IS NULL');
    }

    /**
     * Outstanding points and their expiry buckets, per customer.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function liability(array $filters = [], int $limit = 500): array
    {
        $where = ['c.deleted_at IS NULL', 'c.wallet_balance > 0'];
        $params = [];

        if (($filters['segment'] ?? '') !== '') {
            $where[] = 'm.segment_code = :segment';
            $params['segment'] = (string) $filters['segment'];
        }
        if (($filters['tier_id'] ?? '') !== '') {
            $where[] = 'c.tier_id = :tier_id';
            $params['tier_id'] = (int) $filters['tier_id'];
        }
        DataAccessControl::applyOwnerScope($where, $params, 'c.created_by');

        $remaining = '(l.amount - l.consumed_amount - l.expired_amount)';
        $sql = 'SELECT c.id, c.first_name, c.last_name, c.phone_number, c.wallet_balance,
                       t.name AS tier_name, m.segment_code,
                       MIN(l.expires_at) AS next_expiry,
                       COALESCE(SUM(CASE WHEN l.expires_at IS NULL THEN ' . $remaining . ' ELSE 0 END), 0) AS bucket_never,
                       COALESCE(SUM(CASE WHEN l.expires_at IS NOT NULL AND l.expires_at <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN ' . $remaining . ' ELSE 0 END), 0) AS bucket_30,
                       COALESCE(SUM(CASE WHEN l.expires_at > DATE_ADD(NOW(), INTERVAL 30 DAY) AND l.expires_at <= DATE_ADD(NOW(), INTERVAL 90 DAY) THEN ' . $remaining . ' ELSE 0 END), 0) AS bucket_90,
                       COALESCE(SUM(CASE WHEN l.expires_at > DATE_ADD(NOW(), INTERVAL 90 DAY) THEN ' . $remaining . ' ELSE 0 END), 0) AS bucket_later
                FROM customers c
                LEFT JOIN cashback_lots l ON l.customer_id = c.id AND ' . $remaining . ' > 0
                LEFT JOIN customer_tiers t ON t.id = c.tier_id
                LEFT JOIN customer_segment_members m ON m.customer_id = c.id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY c.id, c.first_name, c.last_name, c.phone_number, c.wallet_balance, t.name, m.segment_code
                ORDER BY c.wallet_balance DESC LIMIT ' . max(1, $limit);

        return $this->rows($sql, $params);
    }

    /** @return array{never: float, d30: float, d90: float, later: float, total: float, customers: int} */
    public function liabilityTotals(): array
    {
        [$ownerWhere, $ownerParams] = $this->ownerWhere('c.created_by');
        $row = $this->one(
            'SELECT COALESCE(SUM(c.wallet_balance), 0) AS total, COUNT(*) AS customers
             FROM customers c WHERE c.deleted_at IS NULL AND c.wallet_balance > 0' . $ownerWhere,
            $ownerParams
        );

        $remaining = '(l.amount - l.consumed_amount - l.expired_amount)';
        $buckets = $this->one(
            'SELECT
                COALESCE(SUM(CASE WHEN l.expires_at IS NULL THEN ' . $remaining . ' ELSE 0 END), 0) AS never_bucket,
                COALESCE(SUM(CASE WHEN l.expires_at IS NOT NULL AND l.expires_at <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN ' . $remaining . ' ELSE 0 END), 0) AS d30,
                COALESCE(SUM(CASE WHEN l.expires_at > DATE_ADD(NOW(), INTERVAL 30 DAY) AND l.expires_at <= DATE_ADD(NOW(), INTERVAL 90 DAY) THEN ' . $remaining . ' ELSE 0 END), 0) AS d90,
                COALESCE(SUM(CASE WHEN l.expires_at > DATE_ADD(NOW(), INTERVAL 90 DAY) THEN ' . $remaining . ' ELSE 0 END), 0) AS later_bucket
             FROM cashback_lots l JOIN customers c ON c.id = l.customer_id
             WHERE c.deleted_at IS NULL AND ' . $remaining . ' > 0' . $ownerWhere,
            $ownerParams
        );

        return [
            'never' => (float) ($buckets['never_bucket'] ?? 0),
            'd30' => (float) ($buckets['d30'] ?? 0),
            'd90' => (float) ($buckets['d90'] ?? 0),
            'later' => (float) ($buckets['later_bucket'] ?? 0),
            'total' => (float) ($row['total'] ?? 0),
            'customers' => (int) ($row['customers'] ?? 0),
        ];
    }

    /**
     * Earned / redeemed / expired / reversed per day or month.
     *
     * @return array<int, array<string, mixed>>
     */
    public function earnRedeem(string $from, string $to, string $groupBy = 'day'): array
    {
        $bucket = $groupBy === 'month' ? "DATE_FORMAT(w.created_at, '%Y-%m')" : 'DATE(w.created_at)';
        [$ownerWhere, $ownerParams] = $this->ownerWhere('c.created_by');

        $sql = "SELECT {$bucket} AS bucket,
                       COALESCE(SUM(CASE WHEN w.type = 'cashback' THEN w.amount ELSE 0 END), 0) AS earned,
                       COALESCE(SUM(CASE WHEN w.type = 'reduction' THEN w.amount ELSE 0 END), 0) AS redeemed,
                       COALESCE(SUM(CASE WHEN w.type = 'expiry' THEN w.amount ELSE 0 END), 0) AS expired,
                       COALESCE(SUM(CASE WHEN w.type = 'reversal' THEN w.amount ELSE 0 END), 0) AS reversed
                FROM wallet_transactions w
                JOIN customers c ON c.id = w.customer_id
                WHERE c.deleted_at IS NULL AND w.created_at BETWEEN :date_from AND :date_to" . $ownerWhere . "
                GROUP BY bucket ORDER BY bucket ASC";

        return $this->rows($sql, array_merge([
            'date_from' => $from . ' 00:00:00',
            'date_to' => $to . ' 23:59:59',
        ], $ownerParams));
    }

    /** @return array<int, array<string, mixed>> */
    public function leadFunnel(string $from, string $to): array
    {
        $sql = "SELECT f.sales_status, COUNT(*) AS leads,
                       COALESCE(SUM(COALESCE(f.invoice_amount, f.pre_invoice_amount, 0)), 0) AS amount,
                       COALESCE(AVG(f.lead_score), 0) AS average_score
                FROM customer_followups f
                WHERE f.followup_date BETWEEN :date_from AND :date_to
                GROUP BY f.sales_status";

        return $this->rows($sql, [
            'date_from' => $from . ' 00:00:00',
            'date_to' => $to . ' 23:59:59',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function leadBySource(string $from, string $to): array
    {
        $sql = "SELECT COALESCE(f.lead_source, 'other') AS lead_source,
                       COUNT(*) AS leads,
                       SUM(CASE WHEN f.sales_status = 'won' THEN 1 ELSE 0 END) AS won,
                       SUM(CASE WHEN f.sales_status = 'lost' THEN 1 ELSE 0 END) AS lost,
                       COALESCE(SUM(CASE WHEN f.sales_status = 'won' THEN COALESCE(f.invoice_amount, f.pre_invoice_amount, 0) ELSE 0 END), 0) AS won_amount,
                       COALESCE(AVG(f.lead_score), 0) AS average_score
                FROM customer_followups f
                WHERE f.followup_date BETWEEN :date_from AND :date_to
                GROUP BY COALESCE(f.lead_source, 'other')
                ORDER BY won_amount DESC";

        return $this->rows($sql, [
            'date_from' => $from . ' 00:00:00',
            'date_to' => $to . ' 23:59:59',
        ]);
    }

    /** @return array<string, float|int> */
    public function leadCycle(): array
    {
        $row = $this->one(
            "SELECT COUNT(*) AS won,
                    COALESCE(AVG(DATEDIFF(COALESCE(finalized_at, updated_at), created_at)), 0) AS average_days
             FROM customer_followups
             WHERE sales_status = 'won'"
        );

        return [
            'won' => (int) ($row['won'] ?? 0),
            'average_days' => (float) ($row['average_days'] ?? 0),
        ];
    }

    /**
     * Reminder sends and the share of customers who bought within 30 days after it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reminderEffectiveness(string $from, string $to): array
    {
        $sql = "SELECT r.kind, COUNT(*) AS sent,
                       SUM(CASE WHEN EXISTS (
                            SELECT 1 FROM purchases p
                            WHERE p.customer_id = r.customer_id AND p.status = 'active'
                              AND p.created_at BETWEEN r.sent_at AND DATE_ADD(r.sent_at, INTERVAL 30 DAY)
                       ) THEN 1 ELSE 0 END) AS returned
                FROM club_reminders r
                WHERE r.status = 'sent' AND r.sent_at BETWEEN :date_from AND :date_to
                GROUP BY r.kind ORDER BY sent DESC";

        return $this->rows($sql, [
            'date_from' => $from . ' 00:00:00',
            'date_to' => $to . ' 23:59:59',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function topBuyers(int $limit = 10): array
    {
        [$ownerWhere, $ownerParams] = $this->ownerWhere('c.created_by');
        $sql = "SELECT c.id, c.first_name, c.last_name, c.phone_number,
                       COUNT(p.id) AS purchases, COALESCE(SUM(p.amount), 0) AS total
                FROM customers c
                JOIN purchases p ON p.customer_id = c.id AND p.status = 'active'
                WHERE c.deleted_at IS NULL" . $ownerWhere . "
                GROUP BY c.id, c.first_name, c.last_name, c.phone_number
                ORDER BY total DESC LIMIT " . max(1, $limit);

        return $this->rows($sql, $ownerParams);
    }

    /** @return array<int, array<string, mixed>> */
    public function atRisk(int $limit = 10): array
    {
        [$ownerWhere, $ownerParams] = $this->ownerWhere('c.created_by');
        $sql = "SELECT c.id, c.first_name, c.last_name, c.phone_number, c.wallet_balance,
                       m.segment_code, m.recency_days, m.monetary
                FROM customer_segment_members m
                JOIN customers c ON c.id = m.customer_id
                WHERE c.deleted_at IS NULL AND m.segment_code IN ('at_risk', 'cant_lose')
                " . $ownerWhere . "
                ORDER BY m.monetary DESC LIMIT " . max(1, $limit);

        return $this->rows($sql, $ownerParams);
    }

    /** @return array<int, array<string, mixed>> */
    public function expiringSoon(int $days = 30, int $limit = 15): array
    {
        $sql = 'SELECT c.id, c.first_name, c.last_name, c.phone_number,
                       SUM(l.amount - l.consumed_amount - l.expired_amount) AS expiring_amount,
                       MIN(l.expires_at) AS expires_at
                FROM cashback_lots l
                JOIN customers c ON c.id = l.customer_id
                WHERE c.deleted_at IS NULL
                  AND l.expires_at IS NOT NULL
                  AND l.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ' . max(1, $days) . ' DAY)
                  AND (l.amount - l.consumed_amount - l.expired_amount) > 0
                GROUP BY c.id, c.first_name, c.last_name, c.phone_number
                ORDER BY expiring_amount DESC LIMIT ' . max(1, $limit);

        return $this->rows($sql, []);
    }

    /** @return array<int, array<string, mixed>> */
    public function recentReminders(int $limit = 10): array
    {
        $sql = "SELECT r.*, c.first_name, c.last_name
                FROM club_reminders r
                JOIN customers c ON c.id = r.customer_id
                WHERE r.status = 'sent'
                ORDER BY r.sent_at DESC LIMIT " . max(1, $limit);

        return $this->rows($sql, []);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    private function rows(string $sql, array $params): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function one(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();

            return $row ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** @param array<string, mixed> $params */
    private function scalar(string $sql, array $params = [], mixed $default = 0): mixed
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();

            return $value === false || $value === null ? $default : $value;
        } catch (\Throwable) {
            return $default;
        }
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function ownerWhere(string $ownerColumn): array
    {
        $where = [];
        $params = [];
        DataAccessControl::applyOwnerScope($where, $params, $ownerColumn);

        return [$where ? ' AND ' . implode(' AND ', $where) : '', $params];
    }

    private function percent(float $part, float $whole): float
    {
        return $whole > 0 ? round(($part / $whole) * 100, 1) : 0.0;
    }
}
