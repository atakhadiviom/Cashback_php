<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CashbackSettingsRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\TierHistoryRepository;
use App\Repositories\TierRepository;

final class CustomerTierService
{
    private TierRepository $tiers;
    private PurchaseRepository $purchases;
    private CustomerRepository $customers;
    private CashbackSettingsRepository $settings;
    private TierHistoryRepository $history;

    public function __construct()
    {
        $this->tiers = new TierRepository();
        $this->purchases = new PurchaseRepository();
        $this->customers = new CustomerRepository();
        $this->settings = new CashbackSettingsRepository();
        $this->history = new TierHistoryRepository();
    }

    /**
     * Spend used for tier calculation, honouring the configured basis.
     */
    public function basisSpend(int $customerId, ?array $options = null): float
    {
        $options ??= $this->settings->clubOptions();
        if (($options['tier_basis'] ?? 'lifetime') === 'rolling') {
            return $this->purchases->spendSince($customerId, (int) $options['tier_window_months']);
        }

        return $this->purchases->lifetimeSpend($customerId);
    }

    public function tierFor(int $customerId): ?array
    {
        return $this->tiers->findTierBySpend($this->basisSpend($customerId));
    }

    /**
     * Move the customer to the tier their spend qualifies for.
     *
     * @return array{old_tier_id: ?int, new_tier_id: ?int, changed: bool, basis: string, spend: float}
     */
    public function recalculate(int $customerId, string $reason = 'recalc', bool $notify = true): array
    {
        $options = $this->settings->clubOptions();
        $basis = (string) ($options['tier_basis'] ?? 'lifetime');
        $spend = $this->basisSpend($customerId, $options);
        $target = $this->tiers->findTierBySpend($spend);
        $newTierId = $target !== null ? (int) $target['id'] : null;

        $customer = $this->customers->find($customerId);
        if (!$customer) {
            return ['old_tier_id' => null, 'new_tier_id' => null, 'changed' => false, 'basis' => $basis, 'spend' => $spend];
        }

        $oldTierId = !empty($customer['tier_id']) ? (int) $customer['tier_id'] : null;
        if ($oldTierId === $newTierId) {
            return ['old_tier_id' => $oldTierId, 'new_tier_id' => $newTierId, 'changed' => false, 'basis' => $basis, 'spend' => $spend];
        }

        if ($oldTierId !== null && $this->isDowngrade($oldTierId, $newTierId)) {
            $grace = (int) ($options['tier_downgrade_grace_days'] ?? 0);
            $changedAt = $customer['tier_changed_at'] ?? null;
            if ($grace > 0 && is_string($changedAt) && $changedAt !== ''
                && strtotime($changedAt) > strtotime('-' . $grace . ' days')) {
                return [
                    'old_tier_id' => $oldTierId,
                    'new_tier_id' => $oldTierId,
                    'changed' => false,
                    'basis' => $basis,
                    'spend' => $spend,
                    'grace' => true,
                ];
            }
        }

        $oldTier = $oldTierId !== null ? $this->tiers->find($oldTierId) : null;

        $this->customers->updateTier($customerId, $newTierId);
        $this->history->record($customerId, $oldTierId, $newTierId, $basis, $spend, $reason);

        (new ActivityLogger())->log(
            'customer_tier_update',
            'سطح مشتری از ' . ($oldTier['name'] ?? 'نامشخص') . ' به ' . ($target['name'] ?? 'نامشخص') . ' تغییر کرد.',
            $customerId
        );

        if ($notify) {
            $this->notifyTierChange($customer, $oldTier, $target, $options);
        }

        return ['old_tier_id' => $oldTierId, 'new_tier_id' => $newTierId, 'changed' => true, 'basis' => $basis, 'spend' => $spend];
    }

    /**
     * Backwards compatible entry point used by the CRM follow-up flow.
     */
    public function recalculateForCustomer(int $customerId, string $reason = 'recalc', bool $notify = true): ?int
    {
        return $this->recalculate($customerId, $reason, $notify)['new_tier_id'];
    }

    /**
     * Recalculate every active customer (cron / admin bulk action).
     *
     * @return array{processed: int, changed: int}
     */
    public function recomputeAll(int $batch = 500): array
    {
        $processed = 0;
        $changed = 0;
        $after = 0;

        do {
            $ids = $this->customers->idsAfter($after, $batch);
            foreach ($ids as $id) {
                $after = $id;
                $result = $this->recalculate($id, 'cron', false);
                $processed++;
                if (!empty($result['changed'])) {
                    $changed++;
                }
            }
        } while ($ids !== []);

        return ['processed' => $processed, 'changed' => $changed];
    }

    /**
     * Safe hook for the sale / void / return flows: never throws, so a loyalty
     * failure can never roll back or block a business operation.
     */
    public static function refreshQuietly(int $customerId, string $reason = 'recalc', bool $notify = false): void
    {
        if ($customerId <= 0) {
            return;
        }
        try {
            (new self())->recalculate($customerId, $reason, $notify);
        } catch (\Throwable) {
        }
    }

    public static function tierLabel(?string $tierName): string
    {
        return $tierName ?? 'نامشخص';
    }

    private function isDowngrade(int $oldTierId, ?int $newTierId): bool
    {
        $old = $this->tiers->find($oldTierId);
        $new = $newTierId !== null ? $this->tiers->find($newTierId) : null;
        $oldMin = $old !== null ? (float) $old['min_lifetime_spend'] : 0.0;
        $newMin = $new !== null ? (float) $new['min_lifetime_spend'] : 0.0;

        return $newMin < $oldMin;
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<string, mixed>|null $oldTier
     * @param array<string, mixed>|null $newTier
     * @param array<string, mixed> $options
     */
    private function notifyTierChange(array $customer, ?array $oldTier, ?array $newTier, array $options): void
    {
        if (empty($options['enabled']) || empty($options['tier_change_sms']) || $newTier === null) {
            return;
        }
        $phone = trim((string) ($customer['phone_number'] ?? ''));
        if ($phone === '') {
            return;
        }

        try {
            $message = (new SmsTemplateRenderer())->render(
                (string) ($options['tier_change_template'] ?? ''),
                $customer,
                [
                    'tier_name' => (string) ($newTier['name'] ?? ''),
                    'old_tier_name' => (string) ($oldTier['name'] ?? ''),
                ]
            );
            if (trim($message) !== '') {
                (new SmsService())->sendRaw($phone, $message, 'club_tier_change', (int) $customer['id']);
            }
        } catch (\Throwable) {
            // A failed SMS must never break the tier recalculation.
        }
    }
}
