<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\CustomerRepository;
use App\Repositories\OtpRepository;
use App\Repositories\WalletRepository;

final class PortalService
{
    public function requestOtp(string $phone): array
    {
        if (!(bool) \config_value('portal.enabled', true)) {
            return ['ok' => false, 'errors' => ['portal' => 'پرتال غیرفعال است.']];
        }

        $phone = \normalize_digits(trim($phone));
        if (!preg_match('/^09\d{9}$/', $phone)) {
            return ['ok' => false, 'errors' => ['phone' => 'شماره موبایل نامعتبر است.']];
        }

        $limit = (int) \config_value('portal.otp_rate_limit_per_hour', 5);
        if ((new OtpRepository())->countRecentByPhone($phone, 1) >= $limit) {
            return ['ok' => false, 'errors' => ['phone' => 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً بعداً تلاش کنید.']];
        }

        $customer = (new CustomerRepository())->findByPhone($phone);
        if (!$customer) {
            return ['ok' => false, 'errors' => ['phone' => 'مشتری با این شماره یافت نشد.']];
        }

        $code = (string) random_int(100000, 999999);
        $ttl = (int) \config_value('portal.otp_ttl_seconds', 300);
        $expires = date('Y-m-d H:i:s', time() + $ttl);
        (new OtpRepository())->create($phone, password_hash($code, PASSWORD_DEFAULT), (int) $customer['id'], $expires, $_SERVER['REMOTE_ADDR'] ?? null);

        (new SmsService())->sendEvent('otp', $customer, ['otp_code' => $code]);

        return ['ok' => true, 'errors' => []];
    }

    public function verifyOtp(string $phone, string $code): array
    {
        $phone = \normalize_digits(trim($phone));
        $code = \normalize_digits(trim($code));
        $otp = (new OtpRepository())->latestValid($phone);
        if (!$otp) {
            return ['ok' => false, 'errors' => ['code' => 'کد منقضی شده یا یافت نشد.']];
        }

        $maxAttempts = (int) \config_value('portal.otp_max_attempts', 5);
        if ((int) $otp['attempts'] >= $maxAttempts) {
            return ['ok' => false, 'errors' => ['code' => 'تعداد تلاش‌ها بیش از حد مجاز است.']];
        }

        if (!password_verify($code, $otp['code_hash'])) {
            (new OtpRepository())->incrementAttempts((int) $otp['id']);
            return ['ok' => false, 'errors' => ['code' => 'کد وارد شده نادرست است.']];
        }

        Auth::loginPortal((int) $otp['customer_id']);
        return ['ok' => true, 'errors' => []];
    }

    public function dashboard(): ?array
    {
        $id = Auth::portalCustomerId();
        if (!$id) {
            return null;
        }
        $customer = (new CustomerRepository())->find($id);
        if (!$customer) {
            return null;
        }
        return [
            'customer' => $customer,
            'transactions' => (new WalletRepository())->forCustomer($id, 10),
            'lifetime_earned' => (new \App\Repositories\PurchaseRepository())->lifetimeCashbackEarned($id),
        ];
    }
}
