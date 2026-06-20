<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ServiceRecordRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO service_records (
                customer_id, technician_id, service_date, service_type, description,
                paid_amount, payment_status, sms_log_id, created_by, created_at, updated_at
            ) VALUES (
                :customer_id, :technician_id, :service_date, :service_type, :description,
                :paid_amount, :payment_status, :sms_log_id, :created_by, :created_at, :updated_at
            )'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, c.first_name, c.last_name, c.contract_number, c.phone_number,
                    t.name AS technician_name, u.name AS created_by_name,
                    sl.status AS sms_status
             FROM service_records s
             JOIN customers c ON c.id = s.customer_id
             JOIN users t ON t.id = s.technician_id
             LEFT JOIN users u ON u.id = s.created_by
             LEFT JOIN sms_logs sl ON sl.id = s.sms_log_id
             WHERE s.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function forCustomer(int $customerId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, t.name AS technician_name, sl.status AS sms_status
             FROM service_records s
             JOIN users t ON t.id = s.technician_id
             LEFT JOIN sms_logs sl ON sl.id = s.sms_log_id
             WHERE s.customer_id = :customer_id
             ORDER BY s.service_date DESC, s.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

    public function search(array $filters, int $limit = 300): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT s.*, c.first_name, c.last_name, c.national_code, c.contract_number, c.phone_number,
                       t.name AS technician_name, sl.status AS sms_status
                FROM service_records s
                JOIN customers c ON c.id = s.customer_id
                JOIN users t ON t.id = s.technician_id
                LEFT JOIN sms_logs sl ON sl.id = s.sms_log_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY s.service_date DESC, s.id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function summary(array $filters): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT
                    COUNT(*) AS service_count,
                    COALESCE(SUM(s.paid_amount), 0) AS paid_total,
                    SUM(CASE WHEN s.payment_status = \'paid\' THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN s.payment_status = \'unpaid\' THEN 1 ELSE 0 END) AS unpaid_count
                FROM service_records s
                JOIN customers c ON c.id = s.customer_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [
            'service_count' => 0,
            'paid_total' => 0,
            'paid_count' => 0,
            'unpaid_count' => 0,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function byTechnician(array $filters): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT t.id, t.name AS technician_name,
                       COUNT(*) AS service_count,
                       COALESCE(SUM(s.paid_amount), 0) AS paid_total,
                       SUM(CASE WHEN s.payment_status = \'paid\' THEN 1 ELSE 0 END) AS paid_count,
                       SUM(CASE WHEN s.payment_status = \'unpaid\' THEN 1 ELSE 0 END) AS unpaid_count
                FROM service_records s
                JOIN customers c ON c.id = s.customer_id
                JOIN users t ON t.id = s.technician_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY t.id, t.name ORDER BY service_count DESC, t.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    public function filters(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 's.service_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 's.service_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['technician_id'])) {
            $where[] = 's.technician_id = :technician_id';
            $params['technician_id'] = (int) $filters['technician_id'];
        }
        if (!empty($filters['payment_status']) && in_array($filters['payment_status'], ['paid', 'unpaid'], true)) {
            $where[] = 's.payment_status = :payment_status';
            $params['payment_status'] = $filters['payment_status'];
        }
        if (!empty($filters['service_type']) && in_array($filters['service_type'], ['periodic', 'repair', 'inspection', 'other'], true)) {
            $where[] = 's.service_type = :service_type';
            $params['service_type'] = $filters['service_type'];
        }
        if (($filters['contract_number'] ?? '') !== '') {
            $where[] = 'c.contract_number LIKE :contract_number';
            $params['contract_number'] = \search_like_term((string) $filters['contract_number']);
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(' . \sql_normalize_persian('c.first_name') . ' LIKE :q1 OR ' . \sql_normalize_persian('c.last_name') . ' LIKE :q2 OR c.national_code LIKE :q3 OR c.phone_number LIKE :q4 OR c.contract_number LIKE :q5)';
            $term = \search_like_term((string) $filters['q']);
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
            $params['q5'] = $term;
        }
        foreach (['first_name', 'last_name', 'national_code', 'phone_number'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                if (in_array($field, ['first_name', 'last_name'], true)) {
                    $where[] = \sql_normalize_persian("c.{$field}") . " LIKE :{$field}";
                } else {
                    $where[] = "c.{$field} LIKE :{$field}";
                }
                $params[$field] = \search_like_term((string) $filters[$field]);
            }
        }

        return [$where, $params];
    }

    public function updateSmsLogId(int $id, ?int $smsLogId): void
    {
        $stmt = $this->pdo->prepare('UPDATE service_records SET sms_log_id = :sms_log_id, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'sms_log_id' => $smsLogId,
            'updated_at' => \current_datetime(),
            'id' => $id,
        ]);
    }
}
