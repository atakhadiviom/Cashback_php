<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class FestivalRepository
{
    public const AUDIENCES = [
        'all' => 'همه مشتریان',
        'club_members' => 'اعضای باشگاه (دارای خرید)',
        'reminder_recipients' => 'دریافت‌کنندگان یادآوری',
        'segment' => 'یک بخش RFM',
        'tier' => 'یک سطح مشتری',
        'tag' => 'یک برچسب',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        try {
            return $this->pdo->query(
                'SELECT f.*, (SELECT COUNT(*) FROM club_festival_recipients r WHERE r.festival_id = f.id) AS recipients
                 FROM club_festivals f
                 ORDER BY f.id DESC'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM club_festivals WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO club_festivals
                (name, description, discount_text, discount_percent, discount_code,
                 starts_at, ends_at, send_at, audience, audience_value, template, status, sent_count, created_by, created_at, updated_at)
             VALUES
                (:name, :description, :discount_text, :discount_percent, :discount_code,
                 :starts_at, :ends_at, :send_at, :audience, :audience_value, :template, :status, 0, :created_by, :created_at, :updated_at)'
        );
        $now = \current_datetime();
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'discount_text' => $data['discount_text'],
            'discount_percent' => $data['discount_percent'],
            'discount_code' => $data['discount_code'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'send_at' => $data['send_at'],
            'audience' => $data['audience'],
            'audience_value' => $data['audience_value'],
            'template' => $data['template'],
            'status' => $data['status'],
            'created_by' => $data['created_by'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM club_festivals WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $stmt = $this->pdo->prepare('DELETE FROM club_festival_recipients WHERE festival_id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function setStatus(int $id, string $status, ?int $sentCount = null): void
    {
        $sql = 'UPDATE club_festivals SET status = :status, updated_at = :updated_at WHERE id = :id';
        $params = ['status' => $status, 'updated_at' => \current_datetime(), 'id' => $id];
        if ($sentCount !== null) {
            $sql = 'UPDATE club_festivals SET status = :status, sent_count = :sent_count, updated_at = :updated_at WHERE id = :id';
            $params['sent_count'] = $sentCount;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /** @return array<int, array<string, mixed>> */
    public function due(): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM club_festivals
                 WHERE status IN ('scheduled', 'sending') AND send_at IS NOT NULL AND send_at <= :now
                 ORDER BY send_at ASC LIMIT 20"
            );
            $stmt->execute(['now' => \current_datetime()]);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Audience of a festival (customers table rows).
     *
     * @param array<string, mixed> $festival
     * @return array<int, array<string, mixed>>
     */
    public function audience(array $festival, int $limit = 0): array
    {
        [$where, $params] = $this->audienceWhere((string) $festival['audience'], (string) ($festival['audience_value'] ?? ''));
        $sql = 'SELECT c.* FROM customers c WHERE ' . implode(' AND ', $where) . ' ORDER BY c.id ASC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $festival
     */
    public function audienceCount(array $festival): int
    {
        [$where, $params] = $this->audienceWhere((string) $festival['audience'], (string) ($festival['audience_value'] ?? ''));
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM customers c WHERE ' . implode(' AND ', $where));
            $stmt->execute($params);

            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Count customers for an audience pair without a stored festival (preview).
     */
    public function countForAudience(string $audience, string $value): int
    {
        return $this->audienceCount(['audience' => $audience, 'audience_value' => $value]);
    }

    public function wasSent(int $festivalId, int $customerId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM club_festival_recipients WHERE festival_id = :festival_id AND customer_id = :customer_id LIMIT 1'
            );
            $stmt->execute(['festival_id' => $festivalId, 'customer_id' => $customerId]);

            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    public function recordRecipient(int $festivalId, int $customerId, ?int $smsLogId, string $status = 'sent'): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT IGNORE INTO club_festival_recipients (festival_id, customer_id, status, sms_log_id, sent_at)
                 VALUES (:festival_id, :customer_id, :status, :sms_log_id, :sent_at)'
            );
            $stmt->execute([
                'festival_id' => $festivalId,
                'customer_id' => $customerId,
                'status' => $status,
                'sms_log_id' => $smsLogId,
                'sent_at' => \current_datetime(),
            ]);
        } catch (\Throwable) {
        }
    }

    public function recipientsCount(int $festivalId): int
    {
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM club_festival_recipients WHERE festival_id = :id');
            $stmt->execute(['id' => $festivalId]);

            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function audienceWhere(string $audience, string $value): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        switch ($audience) {
            case 'club_members':
                $where[] = "EXISTS (SELECT 1 FROM purchases p WHERE p.customer_id = c.id AND p.status = 'active')";
                break;

            case 'reminder_recipients':
                $where[] = "EXISTS (SELECT 1 FROM club_reminders r WHERE r.customer_id = c.id AND r.status = 'sent')";
                break;

            case 'segment':
                if ($value !== '') {
                    $where[] = 'EXISTS (SELECT 1 FROM customer_segment_members m WHERE m.customer_id = c.id AND m.segment_code = :segment)';
                    $params['segment'] = $value;
                }
                break;

            case 'tier':
                if ($value !== '' && (int) $value > 0) {
                    $where[] = 'c.tier_id = :tier_id';
                    $params['tier_id'] = (int) $value;
                }
                break;

            case 'tag':
                if ($value !== '' && (int) $value > 0) {
                    $where[] = 'EXISTS (SELECT 1 FROM customer_tag_assignments ta WHERE ta.customer_id = c.id AND ta.tag_id = :tag_id)';
                    $params['tag_id'] = (int) $value;
                }
                break;

            case 'all':
            default:
                break;
        }

        return [$where, $params];
    }
}
