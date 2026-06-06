<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ReportRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function dashboard(): array
    {
        return [
            'customers' => (int) $this->pdo->query('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL')->fetchColumn(),
            'purchases' => (int) $this->pdo->query("SELECT COUNT(*) FROM purchases WHERE status = 'active'")->fetchColumn(),
            'purchase_amount' => (float) $this->pdo->query("SELECT COALESCE(SUM(amount),0) FROM purchases WHERE status = 'active'")->fetchColumn(),
            'cashback' => (float) $this->pdo->query("SELECT COALESCE(SUM(cashback_amount),0) FROM purchases WHERE status = 'active'")->fetchColumn(),
            'wallets' => (float) $this->pdo->query('SELECT COALESCE(SUM(wallet_balance),0) FROM customers WHERE deleted_at IS NULL')->fetchColumn(),
            'birthdays_today' => (int) $this->pdo->query('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL AND birthday IS NOT NULL AND MONTH(birthday) = MONTH(CURDATE()) AND DAY(birthday) = DAY(CURDATE())')->fetchColumn(),
            'cashback_month' => (float) $this->pdo->query("SELECT COALESCE(SUM(cashback_amount),0) FROM purchases WHERE status = 'active' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn(),
            'reductions_month' => (float) $this->pdo->query("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type = 'reduction' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn(),
            'outstanding_liability' => $this->outstandingLiability(),
        ];
    }

    public function outstandingLiability(): float
    {
        return (float) $this->pdo->query('SELECT COALESCE(SUM(wallet_balance),0) FROM customers WHERE deleted_at IS NULL')->fetchColumn();
    }

    public function cashbackIssuedVsRedeemed(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                (SELECT COALESCE(SUM(cashback_amount),0) FROM purchases WHERE status = 'active' AND created_at BETWEEN :from1 AND :to1) AS issued,
                (SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type = 'reduction' AND created_at BETWEEN :from2 AND :to2) AS redeemed"
        );
        $stmt->execute([
            'from1' => $from . ' 00:00:00',
            'to1' => $to . ' 23:59:59',
            'from2' => $from . ' 00:00:00',
            'to2' => $to . ' 23:59:59',
        ]);
        return $stmt->fetch() ?: ['issued' => 0, 'redeemed' => 0];
    }

    public function inactiveCustomers(int $days, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.* FROM customers c
             WHERE c.deleted_at IS NULL
             AND NOT EXISTS (
                SELECT 1 FROM purchases p WHERE p.customer_id = c.id AND p.status = 'active'
                AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             )
             ORDER BY c.id DESC LIMIT " . $limit
        );
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    public function summary(array $filters): array
    {
        [$where, $params] = $this->purchaseFilters($filters);
        $sql = 'SELECT COUNT(DISTINCT c.id) total_customers, COUNT(p.id) total_purchases, COALESCE(SUM(p.amount),0) total_amount, COALESCE(SUM(p.cashback_amount),0) total_cashback, COALESCE(AVG(p.cashback_amount),0) avg_cashback FROM customers c LEFT JOIN purchases p ON p.customer_id = c.id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        $row['wallet_balances'] = (float) $this->pdo->query('SELECT COALESCE(SUM(wallet_balance),0) FROM customers')->fetchColumn();
        return $row;
    }

    public function topByAmount(): array
    {
        return $this->pdo->query("SELECT c.id, c.first_name, c.last_name, c.national_code, SUM(p.amount) total FROM customers c JOIN purchases p ON p.customer_id = c.id WHERE p.status = 'active' GROUP BY c.id ORDER BY total DESC LIMIT 10")->fetchAll();
    }

    public function topByCashback(): array
    {
        return $this->pdo->query("SELECT c.id, c.first_name, c.last_name, c.national_code, SUM(p.cashback_amount) total FROM customers c JOIN purchases p ON p.customer_id = c.id WHERE p.status = 'active' GROUP BY c.id ORDER BY total DESC LIMIT 10")->fetchAll();
    }

    public function birthdays(string $period): array
    {
        switch ($period) {
            case 'today':
                $condition = 'MONTH(birthday) = MONTH(CURDATE()) AND DAY(birthday) = DAY(CURDATE())';
                break;
            case 'week':
                $condition = 'birthday IS NOT NULL AND DAYOFYEAR(birthday) BETWEEN DAYOFYEAR(CURDATE()) AND DAYOFYEAR(DATE_ADD(CURDATE(), INTERVAL 7 DAY))';
                break;
            default:
                $condition = 'MONTH(birthday) = MONTH(CURDATE())';
                break;
        }
        return $this->pdo->query("SELECT * FROM customers WHERE deleted_at IS NULL AND birthday IS NOT NULL AND {$condition} ORDER BY DAY(birthday)")->fetchAll();
    }

    public function purchases(array $filters, int $limit = 300): array
    {
        [$where, $params] = $this->purchaseFilters($filters);
        $sql = 'SELECT p.*, c.first_name, c.last_name, c.national_code, c.phone_number, c.birthday, u.name AS created_by_name FROM purchases p JOIN customers c ON c.id = p.customer_id LEFT JOIN users u ON u.id = p.created_by';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY p.id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function purchaseFilters(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['date_from'])) {
            $where[] = 'p.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'p.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        foreach (['first_name', 'last_name', 'national_code', 'phone_number'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "c.{$field} LIKE :{$field}";
                $params[$field] = '%' . \normalize_digits((string) $filters[$field]) . '%';
            }
        }
        if (!empty($filters['birthday'])) {
            $where[] = 'c.birthday = :birthday';
            $params['birthday'] = \normalize_digits((string) $filters['birthday']);
        }
        if (!empty($filters['birthday_month'])) {
            $where[] = 'MONTH(c.birthday) = :birthday_month';
            $params['birthday_month'] = (int) \normalize_digits((string) $filters['birthday_month']);
        }
        if (!empty($filters['purchase_min'])) {
            $where[] = 'p.amount >= :purchase_min';
            $params['purchase_min'] = (float) \normalize_digits((string) $filters['purchase_min']);
        }
        if (!empty($filters['purchase_max'])) {
            $where[] = 'p.amount <= :purchase_max';
            $params['purchase_max'] = (float) \normalize_digits((string) $filters['purchase_max']);
        }
        if (!empty($filters['cashback_min'])) {
            $where[] = 'p.cashback_amount >= :cashback_min';
            $params['cashback_min'] = (float) \normalize_digits((string) $filters['cashback_min']);
        }
        if (!empty($filters['cashback_max'])) {
            $where[] = 'p.cashback_amount <= :cashback_max';
            $params['cashback_max'] = (float) \normalize_digits((string) $filters['cashback_max']);
        }
        if (!empty($filters['created_by'])) {
            $where[] = 'p.created_by = :created_by';
            $params['created_by'] = (int) $filters['created_by'];
        }
        if (empty($filters['include_voided'])) {
            $where[] = "p.status = 'active'";
        }
        return [$where, $params];
    }
}
