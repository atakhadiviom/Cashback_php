<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ReminderRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function upsertForFollowup(array $data): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO followup_reminders (
                followup_id, customer_id, operator_id, remind_at, status, seen_at, done_at, created_at, updated_at
            ) VALUES (
                :followup_id, :customer_id, :operator_id, :remind_at, :status, NULL, NULL, :created_at, :updated_at
            ) ON DUPLICATE KEY UPDATE
                customer_id = VALUES(customer_id),
                operator_id = VALUES(operator_id),
                remind_at = VALUES(remind_at),
                status = IF(status = 'done', 'done', VALUES(status)),
                updated_at = VALUES(updated_at)"
        );
        $stmt->execute($data);
    }

    public function deleteForFollowup(int $followupId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM followup_reminders WHERE followup_id = :followup_id');
        $stmt->execute(['followup_id' => $followupId]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, f.sales_status, f.conversation_notes, c.first_name, c.last_name, c.company, c.phone_number, u.name AS operator_name
             FROM followup_reminders r
             JOIN customer_followups f ON f.id = r.followup_id
             JOIN customers c ON c.id = r.customer_id
             JOIN users u ON u.id = r.operator_id
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function search(array $filters, int $limit = 300): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT r.*, f.sales_status, f.conversation_notes, c.first_name, c.last_name, c.company, c.phone_number, u.name AS operator_name
                FROM followup_reminders r
                JOIN customer_followups f ON f.id = r.followup_id
                JOIN customers c ON c.id = r.customer_id
                JOIN users u ON u.id = r.operator_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.remind_at ASC, r.id ASC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function markSeen(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE followup_reminders SET status = IF(status = 'pending', 'seen', status), seen_at = COALESCE(seen_at, :seen_at), updated_at = :updated_at WHERE id = :id AND status <> 'done'");
        $stmt->execute(['seen_at' => \current_datetime(), 'updated_at' => \current_datetime(), 'id' => $id]);
    }

    public function markDone(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE followup_reminders SET status = 'done', done_at = :done_at, updated_at = :updated_at WHERE id = :id");
        $stmt->execute(['done_at' => \current_datetime(), 'updated_at' => \current_datetime(), 'id' => $id]);
    }

    public function dashboardCounts(?int $operatorId = null): array
    {
        $operatorWhere = $operatorId !== null ? ' AND operator_id = :operator_id' : '';
        $stmt = $this->pdo->prepare(
            "SELECT
                SUM(CASE WHEN status IN ('pending','seen') AND DATE(remind_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN status IN ('pending','seen') AND remind_at < NOW() THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
             FROM followup_reminders
             WHERE 1=1{$operatorWhere}"
        );
        $params = [];
        if ($operatorId !== null) {
            $params['operator_id'] = $operatorId;
        }
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        return [
            'today' => (int) ($row['today'] ?? 0),
            'overdue' => (int) ($row['overdue'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
        ];
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function filters(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['operator_id'])) {
            $where[] = 'r.operator_id = :operator_id';
            $params['operator_id'] = (int) $filters['operator_id'];
        }
        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'seen', 'done', 'missed'], true)) {
            $where[] = 'r.status = :status';
            $params['status'] = (string) $filters['status'];
        }
        if (($filters['scope'] ?? '') === 'today') {
            $where[] = 'DATE(r.remind_at) = CURDATE()';
            $where[] = "r.status IN ('pending','seen')";
        }
        if (($filters['scope'] ?? '') === 'overdue') {
            $where[] = 'r.remind_at < NOW()';
            $where[] = "r.status IN ('pending','seen')";
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'r.remind_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'r.remind_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(' . \sql_normalize_persian('c.first_name') . ' LIKE :q1 OR ' . \sql_normalize_persian('c.last_name') . ' LIKE :q2 OR ' . \sql_normalize_persian('c.company') . ' LIKE :q3 OR c.phone_number LIKE :q4 OR f.conversation_notes LIKE :q5)';
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
