<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\WalletRepository;

final class PurchaseService
{
    /**
     * @param array{invoice_ref?: string, confirm_duplicate?: bool, idempotency_key?: string, created_by?: int} $options
     */
    public function create(int $customerId, $amount, array $options = []): array
    {
        $amount = (float) str_replace(',', '', \normalize_digits((string) $amount));
        if ($amount <= 0) {
            return ['ok' => false, 'errors' => ['amount' => 'مبلغ خرید باید مثبت باشد.']];
        }

        $idempotencyKey = trim((string) ($options['idempotency_key'] ?? ''));
        if ($idempotencyKey !== '') {
            $existing = (new PurchaseRepository())->findByIdempotencyKey($idempotencyKey);
            if ($existing) {
                return [
                    'ok' => true,
                    'cashback' => (float) $existing['cashback_amount'],
                    'purchase_id' => (int) $existing['id'],
                    'errors' => [],
                    'duplicate' => true,
                ];
            }
        }

        $customers = new CustomerRepository();
        $customer = $customers->find($customerId);
        if (!$customer || !empty($customer['deleted_at'])) {
            return ['ok' => false, 'errors' => ['customer_id' => 'مشتری یافت نشد.']];
        }

        $invoiceRef = trim((string) ($options['invoice_ref'] ?? ''));
        if ($invoiceRef !== '' && (new PurchaseRepository())->existsActiveByInvoiceRef($invoiceRef)) {
            return ['ok' => false, 'errors' => ['invoice_ref' => 'شماره فاکتور قبلاً ثبت شده است.']];
        }

        $settings = (new CashbackSettingsRepository())->settings();
        $window = (int) ($settings['duplicate_purchase_window_minutes'] ?? 5);
        $dup = (new PurchaseRepository())->recentDuplicate($customerId, $amount, $window);
        if ($dup && empty($options['confirm_duplicate']) && !Auth::isAdmin()) {
            return [
                'ok' => false,
                'errors' => ['duplicate' => 'خرید مشابهی اخیراً ثبت شده است. در صورت اطمینان، گزینه تأیید را علامت بزنید.'],
                'needs_confirm' => true,
            ];
        }
        if ($dup && empty($options['confirm_duplicate']) && Auth::isAdmin()) {
            return [
                'ok' => false,
                'errors' => ['duplicate' => 'خرید مشابهی اخیراً ثبت شده است. گزینه «تأیید ثبت مجدد» را فعال کنید.'],
                'needs_confirm' => true,
            ];
        }

        $calc = (new CashbackCalculator())->calculate($amount, $customer);
        if ($calc['errors']) {
            return ['ok' => false, 'errors' => $calc['errors']];
        }

        $cashback = $calc['cashback'];
        $createdBy = (int) ($options['created_by'] ?? SystemUserService::actorId());

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $purchaseId = (new PurchaseRepository())->create([
                'customer_id' => $customerId,
                'amount' => $amount,
                'cashback_amount' => $cashback,
                'cashback_percent_applied' => $calc['percent_applied'],
                'status' => 'active',
                'invoice_ref' => $invoiceRef !== '' ? $invoiceRef : null,
                'promotion_id' => $calc['promotion_id'],
                'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                'created_by' => $createdBy,
                'created_at' => \current_datetime(),
            ]);
            if ($cashback > 0) {
                $customers->incrementWallet($customerId, $cashback);
                $updated = $customers->find($customerId);
                (new WalletRepository())->create(
                    $customerId,
                    'cashback',
                    $cashback,
                    (float) $updated['wallet_balance'],
                    'کش‌بک خرید',
                    $purchaseId,
                    $createdBy
                );
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        (new ReferralService())->onPurchase($customerId, $purchaseId);
        (new ActivityLogger())->log('purchase_create', 'خرید ثبت شد؛ کش‌بک: ' . \money($cashback), $customerId);
        (new SmsService())->sendEvent('purchase', $customers->find($customerId) ?? $customer, [
            'purchase_amount' => $amount,
            'cashback_amount' => $cashback,
        ]);

        return [
            'ok' => true,
            'cashback' => $cashback,
            'percent_applied' => $calc['percent_applied'],
            'purchase_id' => $purchaseId,
            'errors' => [],
        ];
    }
}
