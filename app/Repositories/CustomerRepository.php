<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataAccessControl;
use PDO;

final class CustomerRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function nationalCodeExists(string $nationalCode, ?int $exceptId = null): bool
    {
        if ($nationalCode === '') {
            return false;
        }

        $sql = 'SELECT id FROM customers WHERE national_code = :national_code';
        $params = ['national_code' => $nationalCode];
        if ($exceptId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function contractNumberExists(string $contractNumber, ?int $exceptId = null): bool
    {
        if ($contractNumber === '') {
            return false;
        }

        $sql = 'SELECT id FROM customers WHERE contract_number = :contract_number';
        $params = ['contract_number' => $contractNumber];
        if ($exceptId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO customers (first_name, last_name, company, national_code, phone_number, email, address, description, birthday, wallet_balance, created_by, referred_by_customer_id, contract_number, contract_starts_at, contract_ends_at, created_at, updated_at)
             VALUES (:first_name, :last_name, :company, :national_code, :phone_number, :email, :address, :description, :birthday, 0, :created_by, :referred_by_customer_id, :contract_number, :contract_starts_at, :contract_ends_at, :created_at, :updated_at)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM customers WHERE phone_number = :phone AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['phone' => $phone]);
        return $stmt->fetch() ?: null;
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE customers SET deleted_at = :deleted_at, updated_at = :updated_at WHERE id = :id');
        $stmt->execute(['deleted_at' => \current_datetime(), 'updated_at' => \current_datetime(), 'id' => $id]);
    }

    public function anonymize(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE customers SET first_name = :first_name, last_name = :last_name, national_code = :national_code,
             phone_number = :phone_number, birthday = NULL, deleted_at = :deleted_at, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute([
            'first_name' => 'حذف',
            'last_name' => 'شده',
            'national_code' => str_pad('9' . (string) $id, 10, '0', STR_PAD_LEFT),
            'phone_number' => '09' . str_pad((string) ($id % 100000000), 9, '0', STR_PAD_LEFT),
            'deleted_at' => \current_datetime(),
            'updated_at' => \current_datetime(),
            'id' => $id,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare('UPDATE customers SET first_name = :first_name, last_name = :last_name, company = :company, national_code = :national_code, phone_number = :phone_number, email = :email, address = :address, description = :description, birthday = :birthday, contract_number = :contract_number, contract_starts_at = :contract_starts_at, contract_ends_at = :contract_ends_at, updated_at = :updated_at WHERE id = :id');
        $stmt->execute($data);
    }

    public function find(int $id): ?array
    {
        $where = ['c.id = :id'];
        $params = ['id' => $id];
        DataAccessControl::applyOwnerScope($where, $params, 'c.created_by');
        $stmt = $this->pdo->prepare('SELECT c.*, u.name AS created_by_name FROM customers c LEFT JOIN users u ON u.id = c.created_by WHERE ' . implode(' AND ', $where));
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function search(array $filters, int $limit = 200): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT c.*, u.name AS created_by_name FROM customers c LEFT JOIN users u ON u.id = c.created_by';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function birthdayTodayWithoutHistory(int $year): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.* FROM customers c
             LEFT JOIN birthday_sms_history h ON h.customer_id = c.id AND h.sent_year = :sent_year
             WHERE c.deleted_at IS NULL AND c.birthday IS NOT NULL AND h.id IS NULL'
        );
        $stmt->execute(['sent_year' => $year]);
        $rows = $stmt->fetchAll();
        return array_values(array_filter($rows, static fn (array $row): bool => \App\Core\Jalali::isJalaliBirthdayToday($row['birthday'] ?? null)));
    }

    public function incrementWallet(int $id, float $amount): void
    {
        $stmt = $this->pdo->prepare('UPDATE customers SET wallet_balance = wallet_balance + :amount, updated_at = :updated_at WHERE id = :id');
        $stmt->execute(['amount' => $amount, 'updated_at' => \current_datetime(), 'id' => $id]);
    }

    public function updateTier(int $id, ?int $tierId): void
    {
        $stmt = $this->pdo->prepare('UPDATE customers SET tier_id = :tier_id, updated_at = :updated_at WHERE id = :id');
        $stmt->execute(['tier_id' => $tierId, 'updated_at' => \current_datetime(), 'id' => $id]);
    }

    public function reduceWallet(int $id, float $amount): void
    {
        $stmt = $this->pdo->prepare('UPDATE customers SET wallet_balance = wallet_balance - :amount, updated_at = :updated_at WHERE id = :id');
        $stmt->execute(['amount' => $amount, 'updated_at' => \current_datetime(), 'id' => $id]);
    }

    public function dueForContractRenewal(int $reminderDays = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.* FROM customers c
             LEFT JOIN contract_renewal_sms_history h
               ON h.customer_id = c.id
              AND h.contract_ends_at = c.contract_ends_at
              AND h.reminder_days = :reminder_days
             WHERE c.deleted_at IS NULL
               AND c.contract_ends_at IS NOT NULL
               AND c.contract_ends_at = DATE_ADD(CURDATE(), INTERVAL :reminder_days2 DAY)
               AND h.id IS NULL'
        );
        $stmt->execute([
            'reminder_days' => $reminderDays,
            'reminder_days2' => $reminderDays,
        ]);
        return $stmt->fetchAll();
    }

    private function filters(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];
        foreach (['first_name', 'last_name', 'company', 'email', 'national_code', 'phone_number', 'contract_number'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                if (in_array($field, ['first_name', 'last_name', 'company'], true)) {
                    $where[] = \sql_normalize_persian("c.{$field}") . " LIKE :{$field}";
                } else {
                    $where[] = "c.{$field} LIKE :{$field}";
                }
                $params[$field] = \search_like_term((string) $filters[$field]);
            }
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(' . \sql_normalize_persian('c.first_name') . ' LIKE :q1 OR ' . \sql_normalize_persian('c.last_name') . ' LIKE :q2 OR c.national_code LIKE :q3 OR c.phone_number LIKE :q4 OR c.contract_number LIKE :q5 OR ' . \sql_normalize_persian('c.company') . ' LIKE :q6 OR c.email LIKE :q7)';
            $term = \search_like_term((string) $filters['q']);
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
            $params['q5'] = $term;
            $params['q6'] = $term;
            $params['q7'] = $term;
        }
        if (($filters['birthday'] ?? '') !== '') {
            $where[] = 'c.birthday = :birthday';
            $params['birthday'] = \normalize_digits((string) $filters['birthday']);
        }
        if (($filters['birthday_month'] ?? '') !== '') {
            $where[] = 'MONTH(c.birthday) = :birthday_month';
            $params['birthday_month'] = (int) \normalize_digits((string) $filters['birthday_month']);
        }
        if (($filters['birthday_day'] ?? '') !== '') {
            $where[] = 'DAY(c.birthday) = :birthday_day';
            $params['birthday_day'] = (int) \normalize_digits((string) $filters['birthday_day']);
        }
        DataAccessControl::applyOwnerScope($where, $params, 'c.created_by');
        return [$where, $params];
    }
}
