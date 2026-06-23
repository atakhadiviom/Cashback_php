<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CrmReportRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function followups(array $filters, int $limit = 500): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT f.*, c.first_name, c.last_name, c.company, c.phone_number, c.contract_number, 
                       u.name AS operator_name, t.name AS tier_name, p.amount AS purchase_amount
                FROM customer_followups f
                JOIN customers c ON c.id = f.customer_id
                JOIN users u ON u.id = f.operator_id
                LEFT JOIN customer_tiers t ON t.id = c.tier_id
                LEFT JOIN purchases p ON p.id = f.purchase_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY f.followup_date DESC, f.id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function summary(array $filters): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT 
                    COUNT(*) AS total_followups,
                    SUM(CASE WHEN f.sales_status = "won" THEN 1 ELSE 0 END) AS won_count,
                    SUM(CASE WHEN f.sales_status = "lost" THEN 1 ELSE 0 END) AS lost_count,
                    SUM(CASE WHEN f.sales_status NOT IN ("won","lost") THEN 1 ELSE 0 END) AS open_count,
                    COALESCE(SUM(f.invoice_amount), 0) AS total_invoice_amount,
                    COALESCE(SUM(CASE WHEN f.sales_status = "won" THEN f.invoice_amount ELSE 0 END), 0) AS won_amount
                FROM customer_followups f
                JOIN customers c ON c.id = f.customer_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [
            'total_followups' => 0, 'won_count' => 0, 'lost_count' => 0, 
            'open_count' => 0, 'total_invoice_amount' => 0, 'won_amount' => 0
        ];
    }

    public function byOperator(array $filters): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT u.id, u.name,
                    COUNT(f.id) AS total,
                    SUM(CASE WHEN f.sales_status = "won" THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN f.sales_status = "lost" THEN 1 ELSE 0 END) AS lost,
                    COALESCE(SUM(f.invoice_amount), 0) AS total_amount
                FROM customer_followups f
                JOIN users u ON u.id = f.operator_id
                JOIN customers c ON c.id = f.customer_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY u.id, u.name ORDER BY total DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function byStatus(array $filters): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT f.sales_status, COUNT(*) AS count,
                    COALESCE(SUM(f.invoice_amount), 0) AS total_amount
                FROM customer_followups f
                JOIN customers c ON c.id = f.customer_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY f.sales_status';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function buildFilters(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['operator_id'])) {
            $where[] = 'f.operator_id = :operator_id';
            $params['operator_id'] = (int) $filters['operator_id'];
        }
        if (!empty($filters['customer_id'])) {
            $where[] = 'f.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'f.followup_date >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'f.followup_date <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['sales_status'])) {
            $where[] = 'f.sales_status = :sales_status';
            $params['sales_status'] = (string) $filters['sales_status'];
        }
        if (!empty($filters['tier_id'])) {
            $where[] = 'c.tier_id = :tier_id';
            $params['tier_id'] = (int) $filters['tier_id'];
        }
        if (!empty($filters['final_only'])) {
            $where[] = "f.sales_status IN ('won','lost')";
        }
        if (!empty($filters['pending_only'])) {
            $where[] = "f.sales_status NOT IN ('won','lost')";
        }

        return [$where, $params];
    }
}
