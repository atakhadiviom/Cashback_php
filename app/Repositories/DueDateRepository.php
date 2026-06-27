<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Jalali;
use App\Services\DueDateService;
use PDO;

final class DueDateRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payment_due_dates (
                customer_id, purchase_id, operator_id, due_date, amount, due_type,
                reference_number, description, status, created_at, updated_at
            ) VALUES (
                :customer_id, :purchase_id, :operator_id, :due_date, :amount, :due_type,
                :reference_number, :description, :status, :created_at, :updated_at
            )'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE payment_due_dates SET
                customer_id = :customer_id,
                purchase_id = :purchase_id,
                operator_id = :operator_id,
                due_date = :due_date,
                amount = :amount,
                due_type = :due_type,
                reference_number = :reference_number,
                description = :description,
                status = :status,
                updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM payment_due_dates WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*, c.first_name, c.last_name, c.company, c.phone_number,
                    u.name AS operator_name, p.invoice_ref AS purchase_invoice_ref
             FROM payment_due_dates d
             JOIN customers c ON c.id = d.customer_id
             JOIN users u ON u.id = d.operator_id
             LEFT JOIN purchases p ON p.id = d.purchase_id
             WHERE d.id = :id AND c.deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function search(array $filters, int $limit = 300): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT d.*, c.first_name, c.last_name, c.company, c.phone_number,
                       u.name AS operator_name, p.invoice_ref AS purchase_invoice_ref
                FROM payment_due_dates d
                JOIN customers c ON c.id = d.customer_id
                JOIN users u ON u.id = d.operator_id
                LEFT JOIN purchases p ON p.id = d.purchase_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY d.due_date ASC, d.id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function markOverduePending(): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE payment_due_dates
             SET status = 'overdue', updated_at = :updated_at
             WHERE status = 'pending' AND due_date < CURDATE()"
        );
        $stmt->execute(['updated_at' => \current_datetime()]);
        return $stmt->rowCount();
    }

    /** @return array<int, array<string, mixed>> */
    public function dueForReminder(string $reminderKind): array
    {
        $sql = 'SELECT d.*, c.first_name, c.last_name, c.phone_number
                FROM payment_due_dates d
                JOIN customers c ON c.id = d.customer_id
                WHERE c.deleted_at IS NULL';

        $params = [];
        switch ($reminderKind) {
            case 'before_3d':
                $sql .= " AND d.status = 'pending' AND d.due_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
                break;
            case 'before_1d':
                $sql .= " AND d.status = 'pending' AND d.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'on_day':
                $sql .= " AND d.status = 'pending' AND d.due_date = CURDATE()";
                break;
            case 'after_overdue':
                $sql .= " AND d.status = 'overdue' AND d.due_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            default:
                return [];
        }

        return $this->pdo->query($sql)->fetchAll();
    }

    /** @return array<string, int|float> */
    public function dashboardStats(): array
    {
        $row = $this->pdo->query(
            "SELECT
                SUM(CASE WHEN due_date = CURDATE() AND status IN ('pending','overdue') THEN 1 ELSE 0 END) AS today_count,
                SUM(CASE WHEN due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND status = 'pending' THEN 1 ELSE 0 END) AS tomorrow_count,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count,
                COALESCE(SUM(CASE WHEN due_date = CURDATE() AND status IN ('pending','overdue') THEN amount ELSE 0 END), 0) AS today_amount,
                SUM(CASE WHEN due_type = 'check' AND status = 'pending' THEN 1 ELSE 0 END) AS pending_checks,
                SUM(CASE WHEN due_type = 'installment' AND status = 'overdue' THEN 1 ELSE 0 END) AS overdue_installments
             FROM payment_due_dates d
             JOIN customers c ON c.id = d.customer_id
             WHERE c.deleted_at IS NULL"
        )->fetch() ?: [];

        return [
            'today_count' => (int) ($row['today_count'] ?? 0),
            'tomorrow_count' => (int) ($row['tomorrow_count'] ?? 0),
            'overdue_count' => (int) ($row['overdue_count'] ?? 0),
            'today_amount' => (float) ($row['today_amount'] ?? 0),
            'pending_checks' => (int) ($row['pending_checks'] ?? 0),
            'overdue_installments' => (int) ($row['overdue_installments'] ?? 0),
        ];
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function filters(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        $scope = (string) ($filters['scope'] ?? '');
        if ($scope === 'today') {
            $where[] = 'd.due_date = CURDATE()';
            $where[] = "d.status IN ('pending','overdue')";
        } elseif ($scope === 'tomorrow') {
            $where[] = 'd.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
            $where[] = "d.status = 'pending'";
        } elseif ($scope === 'next_7_days') {
            $where[] = 'd.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
            $where[] = "d.status IN ('pending','overdue')";
        } elseif ($scope === 'overdue') {
            $where[] = "(d.status = 'overdue' OR (d.status = 'pending' AND d.due_date < CURDATE()))";
        } elseif ($scope === 'daily') {
            $where[] = 'd.due_date = CURDATE()';
        } elseif ($scope === 'weekly') {
            $where[] = 'd.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
        } elseif ($scope === 'monthly') {
            [$jy, $jm] = array_values(Jalali::gregorianToJalali((int) date('Y'), (int) date('n'), (int) date('j')));
            $jm = (int) $jm;
            $startJy = $jy;
            $startJm = $jm;
            $endJy = $jy;
            $endJm = $jm;
            if ($jm === 12) {
                $endJy = $jy + 1;
                $endJm = 1;
            } else {
                $endJm = $jm + 1;
            }
            [$startGy, $startGm, $startGd] = Jalali::jalaliToGregorian($startJy, $startJm, 1);
            [$endGy, $endGm, $endGd] = Jalali::jalaliToGregorian($endJy, $endJm, 1);
            $monthStart = sprintf('%04d-%02d-%02d', $startGy, $startGm, $startGd);
            $monthEnd = date('Y-m-d', strtotime(sprintf('%04d-%02d-%02d', $endGy, $endGm, $endGd) . ' -1 day'));
            $where[] = 'd.due_date BETWEEN :month_start AND :month_end';
            $params['month_start'] = $monthStart;
            $params['month_end'] = $monthEnd;
        }

        if (!empty($filters['customer_id'])) {
            $where[] = 'd.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }
        if (!empty($filters['operator_id'])) {
            $where[] = 'd.operator_id = :operator_id';
            $params['operator_id'] = (int) $filters['operator_id'];
        }
        if (!empty($filters['due_type']) && array_key_exists((string) $filters['due_type'], DueDateService::dueTypeOptions())) {
            $where[] = 'd.due_type = :due_type';
            $params['due_type'] = (string) $filters['due_type'];
        }
        if (!empty($filters['status']) && array_key_exists((string) $filters['status'], DueDateService::statusOptions())) {
            $where[] = 'd.status = :status';
            $params['status'] = (string) $filters['status'];
        }
        if (($filters['reference_number'] ?? '') !== '') {
            $where[] = 'd.reference_number LIKE :reference_number';
            $params['reference_number'] = \search_like_term((string) $filters['reference_number']);
        }
        if (($filters['amount'] ?? '') !== '') {
            $amount = (float) str_replace(',', '', \normalize_digits((string) $filters['amount']));
            if ($amount > 0) {
                $where[] = 'd.amount = :amount';
                $params['amount'] = $amount;
            }
        }
        if (($filters['due_date_from'] ?? '') !== '') {
            $from = $this->parseFilterDate((string) $filters['due_date_from']);
            if ($from !== null && $from !== '') {
                $where[] = 'd.due_date >= :due_date_from';
                $params['due_date_from'] = $from;
            }
        }
        if (($filters['due_date_to'] ?? '') !== '') {
            $to = $this->parseFilterDate((string) $filters['due_date_to']);
            if ($to !== null && $to !== '') {
                $where[] = 'd.due_date <= :due_date_to';
                $params['due_date_to'] = $to;
            }
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(' . \sql_normalize_persian('c.first_name') . ' LIKE :q1 OR ' . \sql_normalize_persian('c.last_name') . ' LIKE :q2 OR ' . \sql_normalize_persian('c.company') . ' LIKE :q3 OR c.phone_number LIKE :q4 OR d.reference_number LIKE :q5 OR p.invoice_ref LIKE :q6)';
            $term = \search_like_term((string) $filters['q']);
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
            $params['q5'] = $term;
            $params['q6'] = $term;
        }

        return [$where, $params];
    }

    private function parseFilterDate(string $raw): ?string
    {
        $raw = trim(\normalize_digits($raw));
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        return Jalali::parseInputToGregorian($raw);
    }
}
