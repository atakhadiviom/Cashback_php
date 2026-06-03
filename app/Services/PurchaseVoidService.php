<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CustomerRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\WalletRepository;

final class PurchaseVoidService
{
    public function void(int $purchaseId, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            return ['ok' => false, 'errors' => ['reason' => 'دلیل ابطال الزامی است.']];
        }

        $purchases = new PurchaseRepository();
        $purchase = $purchases->find($purchaseId);
        if (!$purchase) {
            return ['ok' => false, 'errors' => ['purchase' => 'خرید یافت نشد.']];
        }
        if ($purchase['status'] === 'voided') {
            return ['ok' => false, 'errors' => ['purchase' => 'این خرید قبلاً ابطال شده است.']];
        }

        $customerId = (int) $purchase['customer_id'];
        $cashback = (float) $purchase['cashback_amount'];
        $customers = new CustomerRepository();
        $customer = $customers->find($customerId);
        if (!$customer) {
            return ['ok' => false, 'errors' => ['customer' => 'مشتری یافت نشد.']];
        }
        if ($cashback > (float) $customer['wallet_balance']) {
            return ['ok' => false, 'errors' => ['wallet' => 'موجودی کیف پول برای برگشت کش‌بک کافی نیست.']];
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $purchases->voidPurchase($purchaseId, $reason);
            if ($cashback > 0) {
                $customers->reduceWallet($customerId, $cashback);
                $updated = $customers->find($customerId);
                (new WalletRepository())->create(
                    $customerId,
                    'reversal',
                    $cashback,
                    (float) $updated['wallet_balance'],
                    'ابطال خرید: ' . $reason,
                    $purchaseId,
                    SystemUserService::actorId()
                );
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        (new ActivityLogger())->log('purchase_void', 'خرید #' . $purchaseId . ' ابطال شد.', $customerId);
        $updatedCustomer = $customers->find($customerId) ?? $customer;
        (new SmsService())->sendEvent('purchase_void', $updatedCustomer, [
            'purchase_amount' => (float) $purchase['amount'],
            'cashback_amount' => $cashback,
        ]);

        return ['ok' => true, 'errors' => []];
    }
}
