<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Jalali;
use App\Core\Validator;
use App\Repositories\CustomerRepository;

final class CustomerService
{
    private CustomerRepository $customers;

    public function __construct()
    {
        $this->customers = new CustomerRepository();
    }

    public function create(array $data): array
    {
        $data = $this->clean($data);
        $errors = Validator::customer($data, (bool) \config_value('app.birthday_required'), $this->customers->nationalCodeExists($data['national_code']));
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }
        $now = \current_datetime();
        $referredBy = (int) ($data['referred_by_customer_id'] ?? 0);
        $id = $this->customers->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'national_code' => $data['national_code'],
            'phone_number' => $data['phone_number'],
            'birthday' => $data['birthday'] ?: null,
            'created_by' => SystemUserService::actorId(),
            'referred_by_customer_id' => $referredBy > 0 ? $referredBy : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $customer = $this->customers->find($id) ?? [];
        (new ActivityLogger())->log('customer_create', 'مشتری جدید ثبت شد: ' . $data['first_name'] . ' ' . $data['last_name'], $id);
        (new SmsService())->sendEvent('welcome', $customer);
        return ['ok' => true, 'id' => $id, 'errors' => []];
    }

    public function update(int $id, array $data): array
    {
        $data = $this->clean($data);
        $errors = Validator::customer($data, (bool) \config_value('app.birthday_required'), $this->customers->nationalCodeExists($data['national_code'], $id));
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }
        $this->customers->update($id, [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'national_code' => $data['national_code'],
            'phone_number' => $data['phone_number'],
            'birthday' => $data['birthday'] ?: null,
            'updated_at' => \current_datetime(),
        ]);
        (new ActivityLogger())->log('customer_edit', 'اطلاعات مشتری ویرایش شد.', $id);
        return ['ok' => true, 'errors' => []];
    }

    private function clean(array $data): array
    {
        return [
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'national_code' => \normalize_digits(trim((string) ($data['national_code'] ?? ''))),
            'phone_number' => \normalize_digits(trim((string) ($data['phone_number'] ?? ''))),
            'birthday' => $this->normalizeBirthday((string) ($data['birthday'] ?? '')),
            'referred_by_customer_id' => (int) ($data['referred_by_customer_id'] ?? 0),
        ];
    }

    private function normalizeBirthday(string $raw): string
    {
        $raw = trim(\normalize_digits($raw));
        if ($raw === '') {
            return '';
        }

        $gregorian = Jalali::parseInputToGregorian($raw);

        return $gregorian ?? $raw;
    }
}
