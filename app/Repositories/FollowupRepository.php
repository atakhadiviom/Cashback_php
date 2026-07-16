<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataAccessControl;
use PDO;

final class FollowupRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO customer_followups (
                customer_id, operator_id, followup_date, pre_invoice_amount, invoice_amount,
                sales_status, conversation_notes, next_contact_date, reminder_time, attachment_path,
                final_result, finalized_sale_amount, finalized_at, lost_reason, created_at, updated_at
            ) VALUES (
                :customer_id, :operator_id, :followup_date, :pre_invoice_amount, :invoice_amount,
                :sales_status, :conversation_notes, :next_contact_date, :reminder_time, :attachment_path,
                :final_result, :finalized_sale_amount, :finalized_at, :lost_reason, :created_at, :updated_at
            )'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE customer_followups SET
                customer_id = :customer_id,
                operator_id = :operator_id,
                followup_date = :followup_date,
                pre_invoice_amount = :pre_invoice_amount,
                invoice_amount = :invoice_amount,
                sales_status = :sales_status,
                conversation_notes = :conversation_notes,
                next_contact_date = :next_contact_date,
                reminder_time = :reminder_time,
                attachment_path = :attachment_path,
                final_result = :final_result,
                finalized_sale_amount = :finalized_sale_amount,
                finalized_at = :finalized_at,
                lost_reason = :lost_reason,
                updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public function find(int $id): ?array
    {
        $where = ['f.id = :id'];
        $params = ['id' => $id];
        DataAccessControl::applyOwnerScope($where, $params, 'f.operator_id');
        $stmt = $this->pdo->prepare(
            'SELECT f.*, c.first_name, c.last_name, c.company, c.phone_number, c.contract_number, u.name AS operator_name
             FROM customer_followups f
             JOIN customers c ON c.id = f.customer_id
             JOIN users u ON u.id = f.operator_id
             WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function forCustomer(int $customerId, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.*, u.name AS operator_name
             FROM customer_followups f
             JOIN users u ON u.id = f.operator_id
             WHERE f.customer_id = :customer_id
             ORDER BY f.followup_date DESC, f.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

    public function search(array $filters, int $limit = 300): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT f.*, c.first_name, c.last_name, c.company, c.phone_number, c.contract_number, u.name AS operator_name
                FROM customer_followups f
                JOIN customers c ON c.id = f.customer_id
                JOIN users u ON u.id = f.operator_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY f.followup_date DESC, f.id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function filters(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'f.followup_date >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'f.followup_date <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['operator_id'])) {
            $where[] = 'f.operator_id = :operator_id';
            $params['operator_id'] = (int) $filters['operator_id'];
        }
        if (!empty($filters['customer_id'])) {
            $where[] = 'f.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }
        if (!empty($filters['sales_status']) && array_key_exists((string) $filters['sales_status'], \App\Services\FollowupService::salesStatusOptions())) {
            $where[] = 'f.sales_status = :sales_status';
            $params['sales_status'] = (string) $filters['sales_status'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(' . \sql_normalize_persian('c.first_name') . ' LIKE :q1 OR ' . \sql_normalize_persian('c.last_name') . ' LIKE :q2 OR ' . \sql_normalize_persian('c.company') . ' LIKE :q3 OR c.phone_number LIKE :q4 OR c.contract_number LIKE :q5 OR f.conversation_notes LIKE :q6)';
            $term = \search_like_term((string) $filters['q']);
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
            $params['q5'] = $term;
            $params['q6'] = $term;
        }
        DataAccessControl::applyOwnerScope($where, $params, 'f.operator_id');
        return [$where, $params];
    }
}
