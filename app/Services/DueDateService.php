<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Jalali;
use App\Repositories\CustomerRepository;
use App\Repositories\DueDateRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\UserRepository;

final class DueDateService
{
    private const DUE_TYPE_LABELS = [
        'check' => 'چک',
        'installment' => 'قسط',
        'invoice' => 'فاکتور',
        'other' => 'سایر',
    ];

    private const STATUS_LABELS = [
        'pending' => 'در انتظار',
        'paid' => 'پرداخت شد',
        'overdue' => 'معوق',
        'cancelled' => 'لغو شد',
    ];

    private DueDateRepository $dueDates;
    private CustomerRepository $customers;
    private UserRepository $users;
    private PurchaseRepository $purchases;

    public function __construct()
    {
        $this->dueDates = new DueDateRepository();
        $this->customers = new CustomerRepository();
        $this->users = new UserRepository();
        $this->purchases = new PurchaseRepository();
    }

    public function create(array $data): array
    {
        $data = $this->clean($data);
        $errors = self::validateRecord(
            $data,
            $this->customers->find((int) $data['customer_id']),
            !empty($data['purchase_id']) ? $this->purchases->find((int) $data['purchase_id']) : null,
            $this->users->find((int) $data['operator_id'])
        );
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $now = \current_datetime();
        $id = $this->dueDates->create(array_merge($this->persistenceData($data), [
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        (new ActivityLogger())->log('due_date_create', 'سررسید #' . $id . ' برای مشتری #' . $data['customer_id'] . ' ثبت شد.', $data['customer_id']);

        $record = $this->dueDates->find($id);
        if ($record) {
            (new DueDateSmsService())->sendCreated($record);
        }

        return ['ok' => true, 'id' => $id, 'customer_id' => $data['customer_id'], 'errors' => []];
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->dueDates->find($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['id' => 'سررسید یافت نشد.']];
        }

        $data = $this->clean($data);
        $errors = self::validateRecord(
            $data,
            $this->customers->find((int) $data['customer_id']),
            !empty($data['purchase_id']) ? $this->purchases->find((int) $data['purchase_id']) : null,
            $this->users->find((int) $data['operator_id'])
        );
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $this->dueDates->update($id, array_merge($this->persistenceData($data), [
            'updated_at' => \current_datetime(),
        ]));

        (new ActivityLogger())->log('due_date_update', 'سررسید #' . $id . ' ویرایش شد.', $data['customer_id']);

        return ['ok' => true, 'id' => $id, 'customer_id' => $data['customer_id'], 'errors' => []];
    }

    public function delete(int $id): array
    {
        $existing = $this->dueDates->find($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['id' => 'سررسید یافت نشد.']];
        }

        $this->dueDates->delete($id);
        (new ActivityLogger())->log('due_date_delete', 'سررسید #' . $id . ' حذف شد.', (int) $existing['customer_id']);

        return ['ok' => true, 'errors' => []];
    }

    public static function dueTypeLabel(string $type): string
    {
        return self::DUE_TYPE_LABELS[$type] ?? $type;
    }

    /** @return array<string, string> */
    public static function dueTypeOptions(): array
    {
        return self::DUE_TYPE_LABELS;
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return self::STATUS_LABELS;
    }

    /** @return array<string, string> */
    public static function validate(array $data, ?array $customer = null, ?array $purchase = null, ?array $operator = null): array
    {
        $customerId = (int) ($data['customer_id'] ?? 0);
        $operatorId = (int) ($data['operator_id'] ?? 0);
        $purchaseId = (int) ($data['purchase_id'] ?? 0);

        if ($customer === null && $customerId > 0) {
            $customer = (new CustomerRepository())->find($customerId);
        }
        if ($operator === null && $operatorId > 0) {
            $operator = (new UserRepository())->find($operatorId);
        }
        if ($purchase === null && $purchaseId > 0) {
            $purchase = (new PurchaseRepository())->find($purchaseId);
        }

        return self::validateRecord($data, $customer, $purchase, $operator);
    }

    /** @return array<string, string> */
    private static function validateRecord(
        array $data,
        ?array $customer = null,
        ?array $purchase = null,
        ?array $operator = null
    ): array {
        $errors = [];

        if (!$customer || !empty($customer['deleted_at'])) {
            $errors['customer_id'] = 'مشتری معتبر انتخاب نشده است.';
        }
        if (!$operator || empty($operator['is_active'])) {
            $errors['operator_id'] = 'اپراتور معتبر انتخاب نشده است.';
        }
        if (($data['due_date'] ?? '') === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['due_date'])) {
            $errors['due_date'] = 'تاریخ سررسید معتبر نیست.';
        }
        if ((float) ($data['amount'] ?? 0) <= 0) {
            $errors['amount'] = 'مبلغ باید بزرگ‌تر از صفر باشد.';
        }
        if (!array_key_exists((string) ($data['due_type'] ?? ''), self::DUE_TYPE_LABELS)) {
            $errors['due_type'] = 'نوع سررسید نامعتبر است.';
        }
        if (!array_key_exists((string) ($data['status'] ?? ''), self::STATUS_LABELS)) {
            $errors['status'] = 'وضعیت نامعتبر است.';
        }

        $purchaseId = (int) ($data['purchase_id'] ?? 0);
        if ($purchaseId > 0) {
            if (!$purchase || ($purchase['status'] ?? '') !== 'active') {
                $errors['purchase_id'] = 'فاکتور انتخاب‌شده معتبر نیست.';
            } elseif ((int) $purchase['customer_id'] !== (int) ($data['customer_id'] ?? 0)) {
                $errors['purchase_id'] = 'فاکتور با مشتری انتخاب‌شده هم‌خوانی ندارد.';
            }
        }

        return $errors;
    }

    /** @return array<string, mixed> */
    private function clean(array $data): array
    {
        $dueDate = $this->normalizeDate((string) ($data['due_date'] ?? ''));
        $purchaseId = (int) ($data['purchase_id'] ?? 0);
        $referenceNumber = trim((string) ($data['reference_number'] ?? ''));

        if ($purchaseId > 0) {
            $purchase = $this->purchases->find($purchaseId);
            if ($purchase && !empty($purchase['invoice_ref'])) {
                $referenceNumber = (string) $purchase['invoice_ref'];
            }
        }

        return [
            'customer_id' => (int) ($data['customer_id'] ?? 0),
            'purchase_id' => $purchaseId > 0 ? $purchaseId : null,
            'operator_id' => (int) ($data['operator_id'] ?? Auth::id() ?? SystemUserService::actorId()),
            'due_date' => $dueDate,
            'amount' => $this->parseAmount($data['amount'] ?? ''),
            'due_type' => (string) ($data['due_type'] ?? 'other'),
            'reference_number' => $referenceNumber !== '' ? $referenceNumber : null,
            'description' => trim((string) ($data['description'] ?? '')),
            'status' => (string) ($data['status'] ?? 'pending'),
        ];
    }

    /** @return array<string, mixed> */
    private function persistenceData(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'],
            'purchase_id' => $data['purchase_id'],
            'operator_id' => $data['operator_id'],
            'due_date' => $data['due_date'],
            'amount' => $data['amount'],
            'due_type' => $data['due_type'],
            'reference_number' => $data['reference_number'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'status' => $data['status'],
        ];
    }

    private function normalizeDate(string $raw): string
    {
        $raw = trim(\normalize_digits($raw));
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        return Jalali::parseInputToGregorian($raw) ?? $raw;
    }

    private function parseAmount($amount): float
    {
        $value = trim(str_replace(',', '', \normalize_digits((string) $amount)));
        return $value === '' ? 0.0 : max(0, (float) $value);
    }
}
