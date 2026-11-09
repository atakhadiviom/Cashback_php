<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ContractRenewalRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contract_renewals (
                customer_id, operator_id, previous_starts_at, previous_ends_at,
                new_starts_at, new_ends_at, note, created_at
            ) VALUES (
                :customer_id, :operator_id, :previous_starts_at, :previous_ends_at,
                :new_starts_at, :new_ends_at, :note, :created_at
            )'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function search(array $filters, int $limit = 300): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT r.*, c.first_name, c.last_name, c.company, c.phone_number, c.contract_number,
                       u.name AS operator_name
                FROM contract_renewals r
                JOIN customers c ON c.id = r.customer_id
                JOIN users u ON u.id = r.operator_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.created_at DESC, r.id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function filters(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['customer_id'])) {
            $where[] = 'r.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }
        if (!empty($filters['operator_id'])) {
            $where[] = 'r.operator_id = :operator_id';
            $params['operator_id'] = (int) $filters['operator_id'];
        }
        if (\App\Core\Jalali::filterDate($filters['date_from'] ?? '') !== '') {
            $where[] = 'r.created_at >= :date_from';
            $params['date_from'] = \App\Core\Jalali::filterDate($filters['date_from']) . ' 00:00:00';
        }
        if (\App\Core\Jalali::filterDate($filters['date_to'] ?? '') !== '') {
            $where[] = 'r.created_at <= :date_to';
            $params['date_to'] = \App\Core\Jalali::filterDate($filters['date_to']) . ' 23:59:59';
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(' . \sql_normalize_persian('c.first_name') . ' LIKE :q1 OR ' . \sql_normalize_persian('c.last_name') . ' LIKE :q2 OR c.phone_number LIKE :q3 OR c.contract_number LIKE :q4 OR ' . \sql_normalize_persian('c.company') . ' LIKE :q5)';
            $term = \search_like_term((string) $filters['q']);
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
            $params['q5'] = $term;
        }

        return [$where, $params];
    }
}
