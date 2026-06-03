<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class SmsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function settings(): array
    {
        $row = $this->pdo->query('SELECT * FROM sms_settings WHERE id = 1')->fetch();
        return $row ?: [];
    }

    public function updateSettings(array $data, bool $updateToken): void
    {
        $fields = [
            'sender_number = :sender_number',
            'sms_enabled = :sms_enabled',
            'purchase_sms_enabled = :purchase_sms_enabled',
            'birthday_sms_enabled = :birthday_sms_enabled',
            'wallet_reduction_sms_enabled = :wallet_reduction_sms_enabled',
            'welcome_sms_enabled = :welcome_sms_enabled',
            'purchase_template = :purchase_template',
            'birthday_template = :birthday_template',
            'wallet_reduction_template = :wallet_reduction_template',
            'welcome_template = :welcome_template',
            'updated_at = :updated_at',
        ];
        if ($updateToken) {
            $fields[] = 'api_token = :api_token';
        } else {
            unset($data['api_token']);
        }
        $stmt = $this->pdo->prepare('UPDATE sms_settings SET ' . implode(', ', $fields) . ' WHERE id = 1');
        $stmt->execute($data);
    }

    public function logPending(?int $customerId, string $phone, string $eventType, string $message): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO sms_logs (customer_id, phone_number, event_type, message, provider, status, created_at) VALUES (:customer_id, :phone_number, :event_type, :message, "ippanel", "pending", :created_at)');
        $stmt->execute([
            'customer_id' => $customerId,
            'phone_number' => $phone,
            'event_type' => $eventType,
            'message' => $message,
            'created_at' => \current_datetime(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateLog(int $id, string $status, string $response): void
    {
        $stmt = $this->pdo->prepare('UPDATE sms_logs SET status = :status, provider_response = :provider_response, sent_at = :sent_at, next_retry_at = NULL WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'provider_response' => $response,
            'sent_at' => $status === 'sent' ? \current_datetime() : null,
            'id' => $id,
        ]);
    }

    public function scheduleRetry(int $id, string $response, int $retryCount = 1): void
    {
        $maxRetries = 3;
        if ($retryCount >= $maxRetries) {
            $this->updateLog($id, 'failed', $response);
            return;
        }
        $delayMinutes = min(60, (int) pow(2, $retryCount) * 5);
        $next = date('Y-m-d H:i:s', time() + $delayMinutes * 60);
        $stmt = $this->pdo->prepare(
            'UPDATE sms_logs SET status = :status, provider_response = :provider_response, retry_count = :retry_count, next_retry_at = :next_retry_at WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'pending',
            'provider_response' => $response,
            'retry_count' => $retryCount,
            'next_retry_at' => $next,
            'id' => $id,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function dueForRetry(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM sms_logs WHERE status = 'pending' AND next_retry_at IS NOT NULL AND next_retry_at <= :now AND retry_count < 3 ORDER BY id ASC LIMIT 50"
        );
        $stmt->execute(['now' => \current_datetime()]);
        return $stmt->fetchAll();
    }

    public function search(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['event_type'])) {
            $where[] = 's.event_type = :event_type';
            $params['event_type'] = $filters['event_type'];
        }
        if (!empty($filters['status'])) {
            $where[] = 's.status = :status';
            $params['status'] = $filters['status'];
        }
        $sql = 'SELECT s.*, c.first_name, c.last_name FROM sms_logs s LEFT JOIN customers c ON c.id = s.customer_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY s.id DESC LIMIT 300';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function insertBirthdayHistory(int $customerId, int $year, ?int $smsLogId): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO birthday_sms_history (customer_id, sent_year, sms_log_id, created_at) VALUES (:customer_id, :sent_year, :sms_log_id, :created_at)');
        $stmt->execute(['customer_id' => $customerId, 'sent_year' => $year, 'sms_log_id' => $smsLogId, 'created_at' => \current_datetime()]);
    }
}
