<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Jalali;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\FollowupRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\UserRepository;
use App\Services\CustomerTierService;

final class FollowupService
{
    private const SALES_STATUS_LABELS = [
        'negotiating' => 'در حال مذاکره',
        'pre_invoice_sent' => 'ارسال پیش فاکتور',
        'waiting_customer' => 'در انتظار پاسخ مشتری',
        'callback' => 'تماس مجدد',
        'won' => 'فروش نهایی',
        'lost' => 'لغو فروش',
    ];

    private FollowupRepository $followups;
    private CustomerRepository $customers;
    private UserRepository $users;
    private PurchaseRepository $purchases;
    private CashbackSettingsRepository $settings;

    public function __construct()
    {
        $this->followups = new FollowupRepository();
        $this->customers = new CustomerRepository();
        $this->users = new UserRepository();
        $this->purchases = new PurchaseRepository();
        $this->settings = new CashbackSettingsRepository();
    }

    public function create(array $data): array
    {
        $data = $this->clean($data);
        $errors = $this->validate($data);
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $now = \current_datetime();
        $id = $this->followups->create(array_merge($this->persistenceData($data), [
            'created_at' => $now,
            'updated_at' => $now,
        ]));
        (new ReminderService())->syncForFollowup($id, $data);

        (new ActivityLogger())->log('followup_create', 'پیگیری فروش برای مشتری #' . $data['customer_id'] . ' ثبت شد.', $data['customer_id']);

        return ['ok' => true, 'id' => $id, 'customer_id' => $data['customer_id'], 'errors' => []];
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->followups->find($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['id' => 'پیگیری یافت نشد.']];
        }

        $data = $this->clean($data);
        $errors = $this->validate($data);
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $purchaseId = $this->handleFinalization($existing, $data);
        $persisted = $this->persistenceData($data);
        if ($purchaseId) {
            $persisted['purchase_id'] = $purchaseId;
        }

        $this->followups->update($id, array_merge($persisted, [
            'updated_at' => \current_datetime(),
        ]));
        (new ReminderService())->syncForFollowup($id, $data);

        (new ActivityLogger())->log('followup_update', 'پیگیری فروش مشتری #' . $data['customer_id'] . ' ویرایش شد.', $data['customer_id']);

        if ($data['sales_status'] === 'lost') {
            (new ActivityLogger())->log('followup_lost', 'فروش لغو شد. دلیل: ' . ($data['lost_reason'] ?? 'نامشخص'), $data['customer_id']);
        }

        if ($purchaseId) {
            (new CustomerTierService())->recalculateForCustomer($data['customer_id']);
        }

        return ['ok' => true, 'id' => $id, 'customer_id' => $data['customer_id'], 'errors' => []];
    }

    private function handleFinalization(array $existing, array $data): ?int
    {
        if ($data['sales_status'] !== 'won') {
            return null;
        }
        if (!empty($existing['purchase_id'])) {
            return (int) $existing['purchase_id'];
        }

        $amount = (float) ($data['invoice_amount'] ?? $data['pre_invoice_amount'] ?? 0);
        if ($amount <= 0) {
            return null;
        }

        $settings = $this->settings->get();
        $cashbackPercent = (float) ($settings['cashback_percent'] ?? 5.00);
        $cashbackAmount = round($amount * $cashbackPercent / 100, 2);

        $now = \current_datetime();
        $purchaseId = $this->purchases->create([
            'customer_id' => $data['customer_id'],
            'amount' => $amount,
            'cashback_amount' => $cashbackAmount,
            'cashback_percent_applied' => $cashbackPercent,
            'status' => 'active',
            'invoice_ref' => 'CRM-' . $data['customer_id'] . '-' . time(),
            'promotion_id' => null,
            'idempotency_key' => 'crm-followup-' . $data['customer_id'] . '-' . time(),
            'created_by' => SystemUserService::actorId(),
            'created_at' => $now,
        ]);

        (new ActivityLogger())->log('followup_won', 'فروش نهایی از پیگیری ثبت شد. #' . $purchaseId, $data['customer_id']);

        return $purchaseId;
    }

    public static function salesStatusLabel(string $status): string
    {
        return self::SALES_STATUS_LABELS[$status] ?? $status;
    }

    /** @return array<string, string> */
    public static function salesStatusOptions(): array
    {
        return self::SALES_STATUS_LABELS;
    }

    /** @return array<string, mixed> */
    private function clean(array $data): array
    {
        $followupDate = $this->normalizeDateTime((string) ($data['followup_date'] ?? ''), true);
        $nextContactDate = $this->normalizeDate((string) ($data['next_contact_date'] ?? ''));
        $reminderTime = trim(\normalize_digits((string) ($data['reminder_time'] ?? '')));
        if ($reminderTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $reminderTime)) {
            [$hour, $minute] = array_map('intval', explode(':', $reminderTime));
            $reminderTime = sprintf('%02d:%02d:00', $hour, $minute);
        }

        return [
            'customer_id' => (int) ($data['customer_id'] ?? 0),
            'operator_id' => (int) ($data['operator_id'] ?? SystemUserService::actorId()),
            'followup_date' => $followupDate,
            'pre_invoice_amount' => $this->nullableAmount($data['pre_invoice_amount'] ?? ''),
            'invoice_amount' => $this->nullableAmount($data['invoice_amount'] ?? ''),
            'sales_status' => (string) ($data['sales_status'] ?? 'negotiating'),
            'conversation_notes' => trim((string) ($data['conversation_notes'] ?? '')),
            'next_contact_date' => $nextContactDate,
            'reminder_time' => $reminderTime,
            'attachment_path' => trim((string) ($data['attachment_path'] ?? '')),
            'lost_reason' => trim((string) ($data['lost_reason'] ?? '')),
        ];
    }

    /** @return array<string, string> */
    private function validate(array $data): array
    {
        $errors = [];
        $customer = $this->customers->find((int) $data['customer_id']);
        $operator = $this->users->find((int) $data['operator_id']);

        if (!$customer || !empty($customer['deleted_at'])) {
            $errors['customer_id'] = 'مشتری معتبر انتخاب نشده است.';
        }
        if (!$operator || empty($operator['is_active'])) {
            $errors['operator_id'] = 'اپراتور معتبر انتخاب نشده است.';
        }
        if ($data['followup_date'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $data['followup_date'])) {
            $errors['followup_date'] = 'تاریخ پیگیری معتبر نیست.';
        }
        if (!array_key_exists($data['sales_status'], self::SALES_STATUS_LABELS)) {
            $errors['sales_status'] = 'وضعیت فروش نامعتبر است.';
        }
        if ($data['conversation_notes'] === '') {
            $errors['conversation_notes'] = 'توضیحات مکالمه الزامی است.';
        }
        if ($data['next_contact_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['next_contact_date'])) {
            $errors['next_contact_date'] = 'تاریخ تماس بعدی معتبر نیست.';
        }
        if ($data['reminder_time'] !== '' && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['reminder_time'])) {
            $errors['reminder_time'] = 'ساعت یادآوری معتبر نیست.';
        }
        if (in_array($data['sales_status'], ['won', 'lost'], true) && $data['lost_reason'] === '' && $data['sales_status'] === 'lost') {
            $errors['lost_reason'] = 'دلیل لغو فروش الزامی است.';
        }

        return $errors;
    }

    /** @return array<string, mixed> */
    private function persistenceData(array $data): array
    {
        $finalResult = in_array($data['sales_status'], ['won', 'lost'], true) ? $data['sales_status'] : null;
        $finalizedAmount = $data['sales_status'] === 'won' ? ($data['invoice_amount'] ?? $data['pre_invoice_amount']) : null;

        return [
            'customer_id' => $data['customer_id'],
            'operator_id' => $data['operator_id'],
            'followup_date' => $data['followup_date'],
            'pre_invoice_amount' => $data['pre_invoice_amount'],
            'invoice_amount' => $data['invoice_amount'],
            'sales_status' => $data['sales_status'],
            'conversation_notes' => $data['conversation_notes'],
            'next_contact_date' => $data['next_contact_date'] !== '' ? $data['next_contact_date'] : null,
            'reminder_time' => $data['reminder_time'] !== '' ? $data['reminder_time'] : null,
            'attachment_path' => $data['attachment_path'] !== '' ? $data['attachment_path'] : null,
            'final_result' => $finalResult,
            'finalized_sale_amount' => $finalizedAmount,
            'finalized_at' => $finalResult !== null ? \current_datetime() : null,
            'lost_reason' => $data['sales_status'] === 'lost' ? $data['lost_reason'] : null,
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

    private function normalizeDateTime(string $raw, bool $defaultNow = false): string
    {
        $raw = trim(\normalize_digits($raw));
        if ($raw === '' && $defaultNow) {
            return \current_datetime();
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $raw)) {
            return str_replace('T', ' ', $raw) . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw)) {
            return $raw;
        }
        $gregorian = Jalali::parseInputToGregorian($raw);
        return $gregorian ? $gregorian . ' 00:00:00' : $raw;
    }

    private function nullableAmount($amount): ?float
    {
        $value = trim(str_replace(',', '', \normalize_digits((string) $amount)));
        return $value === '' ? null : max(0, (float) $value);
    }
}
