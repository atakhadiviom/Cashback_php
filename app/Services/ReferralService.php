<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\WalletRepository;

final class ReferralService
{
    public function onPurchase(int $customerId, int $purchaseId): void
    {
        $purchases = new PurchaseRepository();
        $firstId = $purchases->firstActivePurchaseId($customerId);
        if ($firstId === null || $firstId !== $purchaseId) {
            return;
        }

        $customer = (new CustomerRepository())->find($customerId);
        if (!$customer || empty($customer['referred_by_customer_id'])) {
            return;
        }

        $referrerId = (int) $customer['referred_by_customer_id'];
        $bonus = (float) ((new CashbackSettingsRepository())->settings()['referral_bonus_amount'] ?? 0);
        if ($bonus <= 0) {
            return;
        }

        $customers = new CustomerRepository();
        $referrer = $customers->find($referrerId);
        if (!$referrer) {
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $customers->incrementWallet($referrerId, $bonus);
            $updated = $customers->find($referrerId);
            $createdBy = SystemUserService::actorId();
            (new WalletRepository())->create(
                $referrerId,
                'cashback',
                $bonus,
                (float) $updated['wallet_balance'],
                'پاداش معرفی مشتری #' . $customerId,
                null,
                $createdBy
            );
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        (new SmsService())->sendEvent('referral_bonus', $updated ?? $referrer, ['cashback_amount' => $bonus]);
    }
}
