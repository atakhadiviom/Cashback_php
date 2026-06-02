<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CustomerRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\WalletRepository;

final class PurchaseService
{
    public function create(int $customerId, mixed $amount): array
    {
        $amount = (float) str_replace(',', '', \normalize_digits((string) $amount));
        if ($amount <= 0) {
            return ['ok' => false, 'errors' => ['amount' => 'مبلغ خرید باید مثبت باشد.']];
        }

        $pdo = Database::pdo();
        $customers = new CustomerRepository();
        $customer = $customers->find($customerId);
        if (!$customer) {
            return ['ok' => false, 'errors' => ['customer_id' => 'مشتری یافت نشد.']];
        }

        $cashback = round($amount * 0.05, 2);
        $pdo->beginTransaction();
        try {
            $purchaseId = (new PurchaseRepository())->create($customerId, $amount, $cashback, (int) Auth::id());
            $customers->incrementWallet($customerId, $cashback);
            $updated = $customers->find($customerId);
            (new WalletRepository())->create($customerId, 'cashback', $cashback, (float) $updated['wallet_balance'], 'کش‌بک خرید', $purchaseId, (int) Auth::id());
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        (new ActivityLogger())->log('purchase_create', 'خرید ثبت شد؛ کش‌بک: ' . \money($cashback), $customerId);
        (new SmsService())->sendEvent('purchase', $customers->find($customerId) ?? $customer, [
            'purchase_amount' => $amount,
            'cashback_amount' => $cashback,
        ]);
        return ['ok' => true, 'cashback' => $cashback, 'purchase_id' => $purchaseId, 'errors' => []];
    }
}
