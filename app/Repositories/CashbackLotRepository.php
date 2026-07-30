<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Cashback "lots": every cashback credit is stored as its own lot with an expiry date.
 * Remaining balance of a lot = amount - consumed_amount - expired_amount.
 */
final class CashbackLotRepository
{
    private const REMAINING = '(amount - consumed_amount - expired_amount)';

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(
        int $customerId,
        float $amount,
        string $source,
        ?int $walletTransactionId,
        ?int $purchaseId,
        ?string $expiresAt
    ): int {
        $now = \current_datetime();
        $stmt = $this->pdo->prepare(
            'INSERT INTO cashback_lots
                (customer_id, wallet_transaction_id, purchase_id, source, amount, credited_at, expires_at, created_at, updated_at)
             VALUES
                (:customer_id, :wallet_transaction_id, :purchase_id, :source, :amount, :credited_at, :expires_at, :created_at, :updated_at)'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'wallet_transaction_id' => $walletTransactionId,
            'purchase_id' => $purchaseId,
            'source' => $source,
            'amount' => $amount,
            'credited_at' => $now,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Open lots (still holding value) for a customer, oldest first — the FIFO consumption order.
     *
     * @return list<array<string, mixed>>
     */
    public function openLots(int $customerId, string $order = 'ASC'): array
    {
        $direction = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $stmt = $this->pdo->prepare(
            'SELECT id, amount, consumed_amount, expired_amount, purchase_id, credited_at, expires_at,
                    ' . self::REMAINING . ' AS remaining
             FROM cashback_lots
             WHERE customer_id = :customer_id AND ' . self::REMAINING . ' > 0
             ORDER BY (expires_at IS NULL) ' . $direction . ', expires_at ' . $direction . ', id ' . $direction
        );
        $stmt->execute(['customer_id' => $customerId]);

        return $stmt->fetchAll();
    }

    public function addConsumed(int $lotId, float $amount): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cashback_lots SET consumed_amount = consumed_amount + :amount, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute(['amount' => $amount, 'updated_at' => \current_datetime(), 'id' => $lotId]);
    }

    public function addExpired(int $lotId, float $amount): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cashback_lots SET expired_amount = expired_amount + :amount, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute(['amount' => $amount, 'updated_at' => \current_datetime(), 'id' => $lotId]);
    }

    /**
     * Customers holding lots that are past their expiry date but still carry value.
     *
     * @return list<array{customer_id: int}>
     */
    public function customersWithExpiredLots(string $now): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT customer_id FROM cashback_lots
             WHERE expires_at IS NOT NULL AND expires_at <= :now AND ' . self::REMAINING . ' > 0'
        );
        $stmt->execute(['now' => $now]);

        return $stmt->fetchAll();
    }

    /** Expired-but-unsettled lots for one customer, oldest first. */
    public function expiredLots(int $customerId, string $now): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, amount, consumed_amount, expired_amount, expires_at,
                    ' . self::REMAINING . ' AS remaining
             FROM cashback_lots
             WHERE customer_id = :customer_id AND expires_at IS NOT NULL AND expires_at <= :now
               AND ' . self::REMAINING . ' > 0
             ORDER BY expires_at ASC, id ASC'
        );
        $stmt->execute(['customer_id' => $customerId, 'now' => $now]);

        return $stmt->fetchAll();
    }

    /**
     * Customers with value expiring inside the warning window and no warning sent yet,
     * with the total at risk and the nearest expiry date.
     *
     * @return list<array<string, mixed>>
     */
    public function dueForExpiryWarning(string $now, string $until): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.customer_id,
                    SUM(' . self::REMAINING . ') AS expiring_amount,
                    MIN(l.expires_at) AS expires_at,
                    c.first_name, c.last_name, c.phone_number, c.wallet_balance
             FROM cashback_lots l
             INNER JOIN customers c ON c.id = l.customer_id
             WHERE l.expires_at IS NOT NULL
               AND l.expires_at > :now AND l.expires_at <= :until
               AND l.warned_at IS NULL
               AND ' . self::REMAINING . ' > 0
               AND c.deleted_at IS NULL
             GROUP BY l.customer_id, c.first_name, c.last_name, c.phone_number, c.wallet_balance
             HAVING expiring_amount > 0'
        );
        $stmt->execute(['now' => $now, 'until' => $until]);

        return $stmt->fetchAll();
    }

    public function markWarned(int $customerId, string $now, string $until): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cashback_lots SET warned_at = :warned_at, updated_at = :updated_at
             WHERE customer_id = :customer_id AND warned_at IS NULL
               AND expires_at IS NOT NULL AND expires_at > :now AND expires_at <= :until
               AND ' . self::REMAINING . ' > 0'
        );
        $stmt->execute([
            'warned_at' => \current_datetime(),
            'updated_at' => \current_datetime(),
            'customer_id' => $customerId,
            'now' => $now,
            'until' => $until,
        ]);
    }

    /**
     * Wallet value that is scheduled to expire, for display on the customer page.
     *
     * @return array{expiring_amount: float, expires_at: ?string}
     */
    public function nextExpiry(int $customerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT MIN(expires_at) AS expires_at FROM cashback_lots
             WHERE customer_id = :customer_id AND expires_at IS NOT NULL AND ' . self::REMAINING . ' > 0'
        );
        $stmt->execute(['customer_id' => $customerId]);
        $expiresAt = $stmt->fetchColumn();
        if (!$expiresAt) {
            return ['expiring_amount' => 0.0, 'expires_at' => null];
        }

        $stmt = $this->pdo->prepare(
            'SELECT SUM(' . self::REMAINING . ') FROM cashback_lots
             WHERE customer_id = :customer_id AND expires_at = :expires_at AND ' . self::REMAINING . ' > 0'
        );
        $stmt->execute(['customer_id' => $customerId, 'expires_at' => $expiresAt]);

        return [
            'expiring_amount' => (float) $stmt->fetchColumn(),
            'expires_at' => (string) $expiresAt,
        ];
    }

    /** Cached so the hot paths (every purchase, every redemption) don't re-probe the schema. */
    public function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $exists = (bool) $this->pdo->query("SHOW TABLES LIKE 'cashback_lots'")?->fetch();
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }
}
