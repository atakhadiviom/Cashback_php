<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CustomerTagRepository
{
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
                'SELECT t.*, COUNT(a.customer_id) AS customers
                 FROM customer_tags t
                 LEFT JOIN customer_tag_assignments a ON a.tag_id = t.id
                 GROUP BY t.id, t.name, t.created_at
                 ORDER BY t.name ASC'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function create(string $name): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO customer_tags (name, created_at) VALUES (:name, :created_at)');
        $stmt->execute(['name' => $name, 'created_at' => \current_datetime()]);

        return (int) $this->pdo->lastInsertId();
    }

    public function rename(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE customer_tags SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM customer_tags WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function exists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM customer_tags WHERE name = :name';
        $params = ['name' => $name];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        try {
            $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
            $stmt->execute($params);

            return (bool) $stmt->fetch();
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function forCustomer(int $customerId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT t.* FROM customer_tags t
                 JOIN customer_tag_assignments a ON a.tag_id = t.id
                 WHERE a.customer_id = :customer_id
                 ORDER BY t.name ASC'
            );
            $stmt->execute(['customer_id' => $customerId]);

            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Badge map for a page of customers: [customer_id => list of tag names].
     *
     * @param list<int> $customerIds
     * @return array<int, list<string>>
     */
    public function mapForCustomers(array $customerIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $customerIds))));
        if ($ids === []) {
            return [];
        }
        try {
            $stmt = $this->pdo->prepare(
                'SELECT a.customer_id, t.name
                 FROM customer_tag_assignments a
                 JOIN customer_tags t ON t.id = a.tag_id
                 WHERE a.customer_id IN (' . implode(',', $ids) . ')
                 ORDER BY t.name'
            );
            $stmt->execute();
            $map = [];
            foreach ($stmt->fetchAll() as $row) {
                $map[(int) $row['customer_id']][] = (string) $row['name'];
            }

            return $map;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Replace the customer's tags with the given set.
     *
     * @param list<int> $tagIds
     */
    public function sync(int $customerId, array $tagIds): void
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        try {
            $ownTransaction = !$this->pdo->inTransaction();
            if ($ownTransaction) {
                $this->pdo->beginTransaction();
            }
            $stmt = $this->pdo->prepare('DELETE FROM customer_tag_assignments WHERE customer_id = :customer_id');
            $stmt->execute(['customer_id' => $customerId]);

            if ($tagIds !== []) {
                $insert = $this->pdo->prepare(
                    'INSERT IGNORE INTO customer_tag_assignments (customer_id, tag_id) VALUES (:customer_id, :tag_id)'
                );
                foreach ($tagIds as $tagId) {
                    $insert->execute(['customer_id' => $customerId, 'tag_id' => $tagId]);
                }
            }
            if ($ownTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $exception) {
            if (isset($ownTransaction) && $ownTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
