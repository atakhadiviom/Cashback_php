<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ClubReminderRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * Candidate customers for a rule kind. "anchor_at" is the date the rule hangs
     * off (last purchase, last service, birthday, contract end, next expiry).
     *
     * @param array<string, mixed> $rule
     * @return array<int, array<string, mixed>>
     */
    public function candidates(array $rule): array
    {
        $kind = (string) ($rule['kind'] ?? '');
        $days = max(0, (int) ($rule['offset_days'] ?? 0));

        $select = 'SELECT c.id AS customer_id, c.phone_number, c.first_name, c.last_name,
                          c.wallet_balance, c.tier_id, c.birthday, c.contract_number,
                          m.segment_code, %s AS anchor_at,
                          (SELECT COALESCE(SUM(p2.amount), 0)
                             FROM purchases p2
                            WHERE p2.customer_id = c.id AND p2.status = \'active\') AS spend
                   FROM %s
                   LEFT JOIN customer_segment_members m ON m.customer_id = c.id
                   WHERE c.deleted_at IS NULL
                     AND c.phone_number IS NOT NULL AND c.phone_number <> \'\' %s %s %s
                   LIMIT 2000';

        $groupBy = 'GROUP BY c.id, c.phone_number, c.first_name, c.last_name, c.wallet_balance,
                             c.tier_id, c.birthday, c.contract_number, m.segment_code';

        try {
            switch ($kind) {
                case 'winback':
                    $sql = sprintf(
                        $select,
                        'MAX(p.created_at)',
                        'customers c JOIN purchases p ON p.customer_id = c.id AND p.status = \'active\'',
                        '',
                        $groupBy,
                        'HAVING MAX(p.created_at) <= DATE_SUB(NOW(), INTERVAL :days DAY)'
                    );
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['days' => $days]);
                    break;

                case 'service_due':
                    $sql = sprintf(
                        $select,
                        'MAX(s.service_date)',
                        'customers c JOIN service_records s ON s.customer_id = c.id',
                        '',
                        $groupBy,
                        'HAVING MAX(s.service_date) <= DATE_SUB(CURDATE(), INTERVAL :days DAY)'
                    );
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['days' => $days]);
                    break;

                case 'points_expiry':
                    $sql = sprintf(
                        $select,
                        'l.anchor_at',
                        '(SELECT customer_id, MIN(expires_at) AS anchor_at
                            FROM cashback_lots
                           WHERE expires_at IS NOT NULL
                             AND (amount - consumed_amount - expired_amount) > 0
                           GROUP BY customer_id) l
                         JOIN customers c ON c.id = l.customer_id',
                        'AND l.anchor_at > NOW()
                         AND DATE_SUB(l.anchor_at, INTERVAL :days DAY) <= NOW()',
                        '',
                        ''
                    );
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['days' => $days]);
                    break;

                case 'contract_renewal':
                    $sql = sprintf(
                        $select,
                        'c.contract_ends_at',
                        'customers c',
                        'AND c.contract_ends_at >= CURDATE()
                         AND DATE_SUB(c.contract_ends_at, INTERVAL :days DAY) <= NOW()',
                        '',
                        ''
                    );
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['days' => $days]);
                    break;

                case 'birthday':
                default:
                    $sql = sprintf(
                        $select,
                        'c.birthday',
                        'customers c',
                        'AND c.birthday IS NOT NULL',
                        '',
                        ''
                    );
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute();
                    break;
            }

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Insert a planned reminder; the unique dedupe_key makes this idempotent.
     *
     * @param array<string, mixed> $row
     */
    public function planInsert(array $row): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT IGNORE INTO club_reminders
                    (customer_id, rule_id, kind, due_at, status, message, meta, dedupe_key, created_at, updated_at)
                 VALUES
                    (:customer_id, :rule_id, :kind, :due_at, :status, :message, :meta, :dedupe_key, :created_at, :updated_at)'
            );
            $now = \current_datetime();
            $stmt->execute([
                'customer_id' => (int) $row['customer_id'],
                'rule_id' => (int) $row['rule_id'],
                'kind' => (string) $row['kind'],
                'due_at' => (string) $row['due_at'],
                'status' => 'pending',
                'message' => $row['message'] ?? null,
                'meta' => isset($row['meta']) ? json_encode($row['meta'], JSON_UNESCAPED_UNICODE) : null,
                'dedupe_key' => (string) $row['dedupe_key'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $stmt->rowCount() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Log a message that was sent outside the queue (group campaign), so the
     * daily cap and the "recently contacted" guard see it too.
     */
    public function recordSent(int $customerId, string $kind, string $message, ?int $smsLogId, string $dedupeKey): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT IGNORE INTO club_reminders
                    (customer_id, rule_id, kind, due_at, status, sent_at, sms_log_id, message, meta, dedupe_key, created_at, updated_at)
                 VALUES
                    (:customer_id, 0, :kind, :due_at, 'sent', :sent_at, :sms_log_id, :message, NULL, :dedupe_key, :created_at, :updated_at)"
            );
            $now = \current_datetime();
            $stmt->execute([
                'customer_id' => $customerId,
                'kind' => $kind,
                'due_at' => $now,
                'sent_at' => $now,
                'sms_log_id' => $smsLogId,
                'message' => mb_substr($message, 0, 500),
                'dedupe_key' => $dedupeKey,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable) {
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function dueNow(int $limit = 100): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.*, c.phone_number, c.first_name, c.last_name, c.wallet_balance, c.contract_number
                 FROM club_reminders r
                 JOIN customers c ON c.id = r.customer_id
                 WHERE r.status = 'pending' AND r.due_at <= :now AND c.deleted_at IS NULL
                 ORDER BY r.due_at ASC, r.id ASC
                 LIMIT " . max(1, $limit)
            );
            $stmt->execute(['now' => \current_datetime()]);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function markSent(int $id, ?int $smsLogId, ?string $message): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE club_reminders SET status = 'sent', sent_at = :sent_at, sms_log_id = :sms_log_id,
                    message = COALESCE(:message, message), updated_at = :updated_at WHERE id = :id"
        );
        $stmt->execute([
            'sent_at' => \current_datetime(),
            'sms_log_id' => $smsLogId,
            'message' => $message,
            'updated_at' => \current_datetime(),
            'id' => $id,
        ]);
    }

    public function markSkipped(int $id, string $reason): void
    {
        $this->markStatus($id, 'skipped', $reason);
    }

    public function markFailed(int $id, string $reason): void
    {
        $this->markStatus($id, 'failed', $reason);
    }

    /** @param array<string, mixed> $filters */
    public function search(array $filters, int $limit = 300): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'sent', 'skipped', 'failed'], true)) {
            $where[] = 'r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['kind'])) {
            $where[] = 'r.kind = :kind';
            $params['kind'] = $filters['kind'];
        }
        if (!empty($filters['customer_id'])) {
            $where[] = 'r.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'r.due_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'r.due_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT r.*, c.first_name, c.last_name, c.phone_number
                 FROM club_reminders r
                 JOIN customers c ON c.id = r.customer_id
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY r.due_at DESC, r.id DESC
                 LIMIT ' . max(1, $limit)
            );
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<string, int> */
    public function stats(): array
    {
        $stats = ['pending' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 0];
        try {
            $rows = $this->pdo->query('SELECT status, COUNT(*) AS total FROM club_reminders GROUP BY status')->fetchAll();
            foreach ($rows as $row) {
                $stats[(string) $row['status']] = (int) $row['total'];
            }
        } catch (\Throwable) {
        }

        return $stats;
    }

    /** @return array<int, array<string, mixed>> */
    public function upcomingForCustomer(int $customerId, int $limit = 5): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM club_reminders
                 WHERE customer_id = :customer_id AND status IN ('pending','sent')
                 ORDER BY due_at DESC LIMIT " . max(1, $limit)
            );
            $stmt->execute(['customer_id' => $customerId]);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function countSentSince(int $customerId, string $since): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM club_reminders WHERE customer_id = :customer_id AND status = 'sent' AND sent_at >= :since"
            );
            $stmt->execute(['customer_id' => $customerId, 'since' => $since]);

            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function countSentToday(): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM club_reminders WHERE status = 'sent' AND sent_at >= :today"
            );
            $stmt->execute(['today' => date('Y-m-d') . ' 00:00:00']);

            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function markStatus(int $id, string $status, string $reason): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE club_reminders SET status = :status, meta = :meta, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'meta' => json_encode(['reason' => mb_substr($reason, 0, 200)], JSON_UNESCAPED_UNICODE),
            'updated_at' => \current_datetime(),
            'id' => $id,
        ]);
    }
}
