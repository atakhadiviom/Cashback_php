<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function customer(array $data, bool $birthdayRequired, bool $nationalCodeExists): array
    {
        $errors = [];
        if (trim((string) ($data['first_name'] ?? '')) === '') {
            $errors['first_name'] = 'نام الزامی است.';
        }
        if (trim((string) ($data['last_name'] ?? '')) === '') {
            $errors['last_name'] = 'نام خانوادگی الزامی است.';
        }
        $nationalCode = \normalize_digits((string) ($data['national_code'] ?? ''));
        if (!preg_match('/^\d{10}$/', $nationalCode)) {
            $errors['national_code'] = 'کد ملی باید دقیقاً ۱۰ رقم باشد.';
        } elseif (!self::isValidIranianNationalCode($nationalCode)) {
            $errors['national_code'] = 'کد ملی نامعتبر است.';
        } elseif ($nationalCodeExists) {
            $errors['national_code'] = 'این کد ملی قبلاً ثبت شده است.';
        }
        $phone = \normalize_digits((string) ($data['phone_number'] ?? ''));
        if (!preg_match('/^09\d{9}$/', $phone)) {
            $errors['phone_number'] = 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.';
        }
        $birthday = trim((string) ($data['birthday'] ?? ''));
        if ($birthdayRequired && $birthday === '') {
            $errors['birthday'] = 'تاریخ تولد الزامی است.';
        } elseif ($birthday !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', \normalize_digits($birthday))) {
            $errors['birthday'] = 'تاریخ تولد شمسی نامعتبر است. مثال: 1403/06/15';
        }
        return $errors;
    }

    public static function positiveAmount(mixed $amount, string $field = 'amount'): array
    {
        $amount = (float) str_replace(',', '', \normalize_digits((string) $amount));
        return $amount > 0 ? [] : [$field => 'مبلغ باید مثبت باشد.'];
    }

    public static function isValidIranianNationalCode(string $code): bool
    {
        if (!preg_match('/^\d{10}$/', $code) || preg_match('/^(\d)\1{9}$/', $code)) {
            return false;
        }
        $check = (int) $code[9];
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        return ($remainder < 2 && $check === $remainder) || ($remainder >= 2 && $check === 11 - $remainder);
    }
}
