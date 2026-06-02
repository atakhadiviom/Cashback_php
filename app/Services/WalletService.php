<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CustomerRepository;
use App\Repositories\WalletRepository;

final class WalletService
{
    public function reduce(int $customerId, mixed $amount, string $reason): array
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
        if ($amount > (float) $customer['wallet_balance']) {
            return ['ok' => false, 'errors' => ['amount' => 'مبلغ کسر بیشتر از موجودی کیف پول است.']];
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $customers->reduceWallet($customerId, $amount);
            $updated = $customers->find($customerId);
            (new WalletRepository())->create($customerId, 'reduction', $amount, (float) $updated['wallet_balance'], $reason, null, (int) Auth::id());
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
}
