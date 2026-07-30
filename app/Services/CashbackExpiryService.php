<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Jalali;
use App\Repositories\CashbackExpirySmsRepository;
use App\Repositories\CashbackLotRepository;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\SmsRepository;
use App\Repositories\WalletRepository;

/**
 * Cashback expiry, tracked as FIFO lots.
 *
 * Every cashback credit becomes a lot with its own expiry date. Redemptions consume the
 * soonest-to-expire lots first, so only cashback the customer never spent can expire.
 * A warning SMS goes out one month (configurable) before the money is taken back.
 */
final class CashbackExpiryService
{
    /** 0 = cashback never expires. Expiry is opt-in: an admin has to set a period. */
    public const DEFAULT_EXPIRY_MONTHS = 0;
    public const DEFAULT_WARNING_DAYS = 30;

    private CashbackLotRepository $lots;

    public function __construct(?CashbackLotRepository $lots = null)
    {
        $this->lots = $lots ?? new CashbackLotRepository();
    }

    /**
     * Split $amount across $lots in the given order, never taking more than a lot holds.
     * Pure math — the allocation rule the whole feature rests on.
     *
     * @param list<array<string, mixed>> $lots each with `id` and `remaining`
     * @return list<array{lot_id: int, amount: float}>
     */
    public static function allocate(array $lots, float $amount): array
    {
        $allocations = [];
        $left = round($amount, 2);

        foreach ($lots as $lot) {
            if ($left <= 0.004) {
                break;
            }
            $remaining = round((float) $lot['remaining'], 2);
            if ($remaining <= 0) {
                continue;
            }
            $take = min($remaining, $left);
            $allocations[] = ['lot_id' => (int) $lot['id'], 'amount' => round($take, 2)];
            $left = round($left - $take, 2);
        }

        return $allocations;
    }

    /** Expiry period in months; 0 means cashback never expires. */
    public function expiryMonths(): int
    {
        $settings = (new CashbackSettingsRepository())->settings();

        return max(0, (int) ($settings['cashback_expiry_months'] ?? self::DEFAULT_EXPIRY_MONTHS));
    }

    /** How many days before expiry the warning SMS goes out. */
    public function warningDays(): int
    {
        $settings = (new CashbackSettingsRepository())->settings();
        $days = (int) ($settings['cashback_expiry_warning_days'] ?? self::DEFAULT_WARNING_DAYS);

        return $days > 0 ? $days : self::DEFAULT_WARNING_DAYS;
    }

    public function expiryDateFor(?string $creditedAt = null): ?string
    {
        $months = $this->expiryMonths();
        if ($months <= 0) {
            return null;
        }

        $base = strtotime($creditedAt ?? \current_datetime()) ?: time();

        return date('Y-m-d H:i:s', strtotime('+' . $months . ' months', $base));
    }

    /**
     * Record a cashback credit as a new lot. Must be called inside the caller's transaction,
     * right after the wallet transaction row is written.
     */
    public function credit(int $customerId, float $amount, string $source, ?int $walletTransactionId = null, ?int $purchaseId = null): void
    {
        if ($amount <= 0 || !$this->lots->tableExists()) {
            return;
        }

        $this->lots->create($customerId, round($amount, 2), $source, $walletTransactionId, $purchaseId, $this->expiryDateFor());
    }

    /**
     * Draw $amount out of the customer's lots, soonest-to-expire first.
     * Returns the amount actually drawn (may be less if lot data predates a credit).
     */
    public function consume(int $customerId, float $amount): float
    {
        if ($amount <= 0 || !$this->lots->tableExists()) {
            return 0.0;
        }

        $drawn = 0.0;
        foreach (self::allocate($this->lots->openLots($customerId), $amount) as $allocation) {
            $this->lots->addConsumed($allocation['lot_id'], $allocation['amount']);
            $drawn = round($drawn + $allocation['amount'], 2);
        }

        return $drawn;
    }

    /**
     * Claw back a voided purchase's cashback.
     *
     * The wallet balance has already been debited by $amount, so the same $amount has to come
     * off the lots or the two would drift apart. The voided purchase's own lot is drained first
     * (that cashback should never have existed); if the customer already spent part of it, the
     * shortfall is taken from the remaining lots in the normal soonest-to-expire order.
     */
    public function reverse(int $customerId, float $amount, ?int $purchaseId = null): void
    {
        if ($amount <= 0 || !$this->lots->tableExists()) {
            return;
        }

        $left = round($amount, 2);

        if ($purchaseId !== null) {
            $ownLots = array_values(array_filter(
                $this->lots->openLots($customerId),
                static fn (array $lot): bool => (int) ($lot['purchase_id'] ?? 0) === $purchaseId
            ));
            foreach (self::allocate($ownLots, $left) as $allocation) {
                $this->lots->addConsumed($allocation['lot_id'], $allocation['amount']);
                $left = round($left - $allocation['amount'], 2);
            }
        }

        if ($left > 0.004) {
            $this->consume($customerId, $left);
        }
    }

    /**
     * Take back cashback whose expiry date has passed and that was never spent.
     *
     * @return array{ok: bool, messages: string[]}
     */
    public function runExpiry(): array
    {
        if (!$this->lots->tableExists()) {
            return ['ok' => true, 'messages' => ['Cashback expiry table is missing; run php database/migrate.php.']];
        }

        $now = \current_datetime();
        $messages = [];
        $customers = new CustomerRepository();
        $wallet = new WalletRepository();

        foreach ($this->lots->customersWithExpiredLots($now) as $row) {
            $customerId = (int) $row['customer_id'];
            $customer = $customers->find($customerId);
            if (!$customer) {
                continue;
            }

            $expired = $this->lots->expiredLots($customerId, $now);
            $total = 0.0;
            foreach ($expired as $lot) {
                $total = round($total + (float) $lot['remaining'], 2);
            }
            if ($total <= 0) {
                continue;
            }

            // Never push the wallet negative: the balance column stays authoritative.
            $deductible = min($total, round((float) $customer['wallet_balance'], 2));

            $pdo = Database::pdo();
            $pdo->beginTransaction();
            try {
                foreach ($expired as $lot) {
                    $this->lots->addExpired((int) $lot['id'], round((float) $lot['remaining'], 2));
                }

                if ($deductible > 0) {
                    $customers->reduceWallet($customerId, $deductible);
                    $updated = $customers->find($customerId);
                    $wallet->create(
                        $customerId,
                        'expiry',
                        $deductible,
                        (float) $updated['wallet_balance'],
                        'انقضای کش‌بک استفاده‌نشده',
                        null,
                        SystemUserService::actorId()
                    );
                }
                $pdo->commit();
            } catch (\Throwable $exception) {
                $pdo->rollBack();
                $messages[] = "Cashback expiry failed for customer #{$customerId}: {$exception->getMessage()}";
                continue;
            }

            (new ActivityLogger())->log('cashback_expiry', 'انقضای کش‌بک: ' . \money($deductible), $customerId);
            $messages[] = "Expired " . \money($deductible) . " cashback for customer #{$customerId}.";
        }

        return ['ok' => true, 'messages' => $messages ?: ['No cashback to expire today.']];
    }

    /**
     * Warn customers whose cashback expires inside the warning window (one month by default).
     *
     * @return array{ok: bool, messages: string[]}
     */
    public function runExpiryWarnings(): array
    {
        if (!$this->lots->tableExists()) {
            return ['ok' => true, 'messages' => ['Cashback expiry table is missing; run php database/migrate.php.']];
        }

        $settings = (new SmsRepository())->settings();
        if (empty($settings['sms_enabled']) || empty($settings['cashback_expiry_sms_enabled'])) {
            return ['ok' => true, 'messages' => ['Cashback expiry SMS is disabled.']];
        }

        $now = \current_datetime();
        $until = date('Y-m-d H:i:s', strtotime('+' . $this->warningDays() . ' days', strtotime($now) ?: time()));

        $messages = [];
        $history = new CashbackExpirySmsRepository();

        foreach ($this->lots->dueForExpiryWarning($now, $until) as $row) {
            $customerId = (int) $row['customer_id'];
            $expiresAt = (string) $row['expires_at'];
            $expiringAmount = round((float) $row['expiring_amount'], 2);
            $expiryMonth = substr($expiresAt, 0, 7);

            if ($expiringAmount <= 0 || $history->exists($customerId, $expiryMonth)) {
                $this->lots->markWarned($customerId, $now, $until);
                continue;
            }

            $logId = (new SmsService())->sendEvent('cashback_expiry', [
                'id' => $customerId,
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'phone_number' => $row['phone_number'] ?? '',
                'wallet_balance' => $row['wallet_balance'] ?? 0,
            ], [
                'expiring_amount' => $expiringAmount,
                'expiry_date' => Jalali::formatDate($expiresAt),
            ]);

            $history->insert($customerId, $expiryMonth, $expiringAmount, $logId);
            $this->lots->markWarned($customerId, $now, $until);
            $messages[] = "Cashback expiry warning attempted for customer #{$customerId} (" . \money($expiringAmount) . ").";
        }

        return ['ok' => true, 'messages' => $messages ?: ['No cashback expiry warnings to send today.']];
    }

    /** @return array{expiring_amount: float, expires_at: ?string} */
    public function nextExpiry(int $customerId): array
    {
        if (!$this->lots->tableExists()) {
            return ['expiring_amount' => 0.0, 'expires_at' => null];
        }

        return $this->lots->nextExpiry($customerId);
    }
}
