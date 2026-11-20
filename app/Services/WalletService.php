<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\WalletRepository;

final class WalletService
{
    /**
     * @param array{purchase_id?: int, related_purchase_amount?: float} $options
     */
    public function reduce(int $customerId, $amount, string $reason, array $options = []): array
    {
        $amount = (float) str_replace(',', '', \normalize_digits((string) $amount));
        if ($amount <= 0) {
            return ['ok' => false, 'errors' => ['amount' => 'مبلغ باید مثبت باشد.']];
        }
        if (trim($reason) === '') {
            return ['ok' => false, 'errors' => ['reason' => 'دلیل کسر الزامی است.']];
        }

        $settings = (new CashbackSettingsRepository())->settings();
        $minRedemption = isset($settings['min_redemption_amount']) ? (float) $settings['min_redemption_amount'] : null;
        if ($minRedemption !== null && $minRedemption > 0 && $amount < $minRedemption) {
            return ['ok' => false, 'errors' => ['amount' => 'حداقل مبلغ قابل استفاده از کیف پول ' . \money($minRedemption) . ' ریال است.']];
        }

        $largeThreshold = isset($settings['large_redemption_threshold']) ? (float) $settings['large_redemption_threshold'] : null;
        if ($largeThreshold !== null && $largeThreshold > 0 && $amount >= $largeThreshold && !Auth::isAdmin()) {
            return ['ok' => false, 'errors' => ['amount' => 'کسر مبالغ بالا فقط توسط مدیر مجاز است.']];
        }

        $relatedAmount = (float) ($options['related_purchase_amount'] ?? 0);
        $maxPercent = isset($settings['max_redemption_percent_of_purchase']) ? (float) $settings['max_redemption_percent_of_purchase'] : null;
        if ($maxPercent !== null && $maxPercent > 0 && $relatedAmount > 0) {
            $maxAllowed = round($relatedAmount * ($maxPercent / 100), 2);
            if ($amount > $maxAllowed) {
                return ['ok' => false, 'errors' => ['amount' => 'حداکثر قابل استفاده از کیف پول برای این خرید ' . \money($maxAllowed) . ' ریال است.']];
            }
        }

        $customers = new CustomerRepository();
        $customer = $customers->find($customerId);
        if (!$customer || !empty($customer['deleted_at'])) {
            return ['ok' => false, 'errors' => ['customer_id' => 'مشتری یافت نشد.']];
        }
        if ($amount > (float) $customer['wallet_balance']) {
            return ['ok' => false, 'errors' => ['amount' => 'مبلغ کسر بیشتر از موجودی کیف پول است.']];
        }

        $purchaseId = isset($options['purchase_id']) ? (int) $options['purchase_id'] : null;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $customers->reduceWallet($customerId, $amount);
            $updated = $customers->find($customerId);
            (new WalletRepository())->create(
                $customerId,
                'reduction',
                $amount,
                (float) $updated['wallet_balance'],
                $reason,
                $purchaseId,
                SystemUserService::actorId()
            );
            // Draw the redeemed amount from the soonest-to-expire cashback lots first.
            (new CashbackExpiryService())->consume($customerId, $amount);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        (new ActivityLogger())->log('wallet_reduction', 'کسر از کیف پول: ' . \money($amount), $customerId);
        (new SmsService())->sendEvent('wallet_reduction', $customers->find($customerId) ?? $customer, [
            'purchase_amount' => $amount,
        ]);
        return ['ok' => true, 'errors' => []];
    }

    /**
     * Manual top-up (panel "شارژ کیف پول" and POST /api/v1/wallet/credit).
     *
     * @param array{reason?: string} $options
     * @return array{ok: bool, errors: array<string, string>, balance?: float}
     */
    public function credit(int $customerId, $amount, string $reason = 'شارژ کیف پول', array $options = []): array
    {
        $amount = (float) str_replace(',', '', \normalize_digits((string) $amount));
        if ($amount <= 0) {
            return ['ok' => false, 'errors' => ['amount' => 'مبلغ باید مثبت باشد.']];
        }

        $customers = new CustomerRepository();
        $customer = $customers->find($customerId);
        if (!$customer) {
            return ['ok' => false, 'errors' => ['customer_id' => 'مشتری یافت نشد.']];
        }

        $reason = trim($reason) !== '' ? trim($reason) : 'شارژ کیف پول';
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $customers->incrementWallet($customerId, $amount);
            $updated = $customers->find($customerId);
            $walletTxId = (new WalletRepository())->create(
                $customerId,
                'cashback',
                $amount,
                (float) $updated['wallet_balance'],
                'شارژ کیف پول: ' . $reason,
                null,
                SystemUserService::actorId()
            );
            (new CashbackExpiryService())->credit($customerId, $amount, 'manual', $walletTxId);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        try {
            (new SmsService())->sendEvent('wallet_credit', $customers->find($customerId) ?? $customer, [
                'wallet_balance' => (float) ($updated['wallet_balance'] ?? 0),
            ]);
        } catch (\Throwable) {
            // A failed SMS must never undo a top-up.
        }

        return ['ok' => true, 'errors' => [], 'balance' => (float) ($updated['wallet_balance'] ?? 0)];
    }
}
