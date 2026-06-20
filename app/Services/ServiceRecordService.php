<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Jalali;
use App\Repositories\CustomerRepository;
use App\Repositories\ServiceRecordRepository;
use App\Repositories\UserRepository;

final class ServiceRecordService
{
    private const SERVICE_TYPES = ['periodic', 'repair', 'inspection', 'other'];

    private const SERVICE_TYPE_LABELS = [
        'periodic' => 'دوره‌ای',
        'repair' => 'تعمیر',
        'inspection' => 'بازرسی',
        'other' => 'سایر',
    ];

    private ServiceRecordRepository $records;
    private CustomerRepository $customers;
    private UserRepository $users;

    public function __construct()
    {
        $this->records = new ServiceRecordRepository();
        $this->customers = new CustomerRepository();
        $this->users = new UserRepository();
    }

    /** @return array<string, string> */
    public static function validate(array $data, ?array $customer = null, ?array $technician = null): array
    {
        $errors = [];
        $customerId = (int) ($data['customer_id'] ?? 0);
        $technicianId = (int) ($data['technician_id'] ?? 0);
        $serviceType = (string) ($data['service_type'] ?? '');
        $serviceDate = (string) ($data['service_date'] ?? '');
        $paidAmount = (float) str_replace(',', '', \normalize_digits((string) ($data['paid_amount'] ?? '0')));

        if ($customerId <= 0 || $customer === null || !empty($customer['deleted_at'])) {
            $errors['customer_id'] = 'مشتری یافت نشد.';
        }
        if ($technicianId <= 0 || $technician === null || empty($technician['is_active'])) {
            $errors['technician_id'] = 'تکنسین معتبر انتخاب نشده است.';
        }
        if (!in_array($serviceType, self::SERVICE_TYPES, true)) {
            $errors['service_type'] = 'نوع سرویس نامعتبر است.';
        }
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', \normalize_digits($serviceDate))) {
            $errors['service_date'] = 'تاریخ سرویس معتبر نیست.';
        }
        if ($paidAmount < 0) {
            $errors['paid_amount'] = 'مبلغ پرداختی نمی‌تواند منفی باشد.';
        }

        return $errors;
    }

    public function create(array $data): array
    {
        $data = $this->clean($data);
        $customer = $this->customers->find($data['customer_id']);
        $technician = $this->users->find($data['technician_id']);
        $errors = self::validate($data, $customer, $technician);
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $paymentStatus = $data['paid_amount'] > 0 ? 'paid' : 'unpaid';
        $now = \current_datetime();
        $recordId = $this->records->create([
            'customer_id' => $data['customer_id'],
            'technician_id' => $data['technician_id'],
            'service_date' => $data['service_date'],
            'service_type' => $data['service_type'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'paid_amount' => $data['paid_amount'],
            'payment_status' => $paymentStatus,
            'sms_log_id' => null,
            'created_by' => SystemUserService::actorId(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $customer = $this->customers->find($data['customer_id']) ?? $customer;
        $smsLogId = (new SmsService())->sendEvent('service_confirmation', $customer, [
            'service_type' => self::serviceTypeLabel($data['service_type']),
            'service_date' => Jalali::formatDate($data['service_date']),
            'paid_amount' => $data['paid_amount'],
        ]);
        if ($smsLogId) {
            $this->records->updateSmsLogId($recordId, $smsLogId);
        }

        (new ActivityLogger())->log(
            'service_create',
            'ثبت سرویس ' . self::serviceTypeLabel($data['service_type']) . ' برای مشتری #' . $data['customer_id'],
            $data['customer_id']
        );

        return ['ok' => true, 'id' => $recordId, 'errors' => []];
    }

    public static function serviceTypeLabel(string $type): string
    {
        return self::SERVICE_TYPE_LABELS[$type] ?? $type;
    }

    /** @return array<string, string> */
    public static function serviceTypeOptions(): array
    {
        return self::SERVICE_TYPE_LABELS;
    }

    private function clean(array $data): array
    {
        $serviceDate = trim(\normalize_digits((string) ($data['service_date'] ?? '')));
        if ($serviceDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            $gregorian = Jalali::parseInputToGregorian($serviceDate);
            if ($gregorian) {
                $serviceDate = $gregorian;
            }
        }

        return [
            'customer_id' => (int) ($data['customer_id'] ?? 0),
            'technician_id' => (int) ($data['technician_id'] ?? 0),
            'service_date' => $serviceDate,
            'service_type' => (string) ($data['service_type'] ?? ''),
            'description' => trim((string) ($data['description'] ?? '')),
            'paid_amount' => (float) str_replace(',', '', \normalize_digits((string) ($data['paid_amount'] ?? '0'))),
        ];
    }
}
