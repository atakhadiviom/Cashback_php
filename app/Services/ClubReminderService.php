<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CashbackSettingsRepository;
use App\Repositories\ClubReminderRepository;
use App\Repositories\ClubReminderRuleRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PurchaseRepository;

final class ClubReminderService
{
    private ClubReminderRuleRepository $rules;
    private ClubReminderRepository $reminders;
    private CashbackSettingsRepository $settings;

    public function __construct()
    {
        $this->rules = new ClubReminderRuleRepository();
        $this->reminders = new ClubReminderRepository();
        $this->settings = new CashbackSettingsRepository();
    }

    /**
     * Plan due reminders for every enabled rule. Runs daily (cron "club_plan").
     *
     * @return array{ok: bool, planned: int, evaluated: int, messages: list<string>}
     */
    public function plan(): array
    {
        $options = $this->settings->clubOptions();
        if (empty($options['enabled'])) {
            return ['ok' => true, 'planned' => 0, 'evaluated' => 0, 'messages' => ['باشگاه مشتریان غیرفعال است.']];
        }
        if (!$this->tableReady()) {
            return ['ok' => false, 'planned' => 0, 'evaluated' => 0, 'messages' => ['جدول یادآوری‌ها ساخته نشده است (مهاجرت ۰۷۴).']];
        }

        $planned = 0;
        $evaluated = 0;
        $messages = [];
        $now = time();

        foreach ($this->rules->active() as $rule) {
            $kind = (string) $rule['kind'];
            $offset = max(0, (int) $rule['offset_days']);
            $time = (string) ($rule['time_of_day'] ?? '10:00:00');
            $direction = (string) ($rule['direction'] ?? 'after');
            $rulePlanned = 0;

            foreach ($this->reminders->candidates($rule) as $row) {
                $anchor = (string) ($row['anchor_at'] ?? '');
                if ($anchor === '') {
                    continue;
                }
                $evaluated++;

                if ($kind === 'birthday') {
                    $due = ReminderSchedule::birthdayDueAt($anchor, $offset, $time);
                    $period = substr($due, 0, 4);
                } else {
                    $due = ReminderSchedule::dueAt($anchor, $direction, $offset, $time);
                    $period = substr($anchor, 0, 10);
                }
                if ($due === '') {
                    continue;
                }

                $dueAt = strtotime($due);
                if ($dueAt === false || $dueAt > $now || $dueAt < $now - 7 * 86400) {
                    continue; // not due yet, or a stale backlog from before the rule was enabled
                }
                if (!$this->passesConditions($row, $rule)) {
                    continue;
                }

                $saved = $this->reminders->planInsert([
                    'customer_id' => (int) $row['customer_id'],
                    'rule_id' => (int) $rule['id'],
                    'kind' => $kind,
                    'due_at' => $due,
                    'dedupe_key' => $kind . ':' . (int) $rule['id'] . ':' . (int) $row['customer_id'] . ':' . $period,
                    'meta' => [
                        'anchor_at' => $anchor,
                        'segment' => $row['segment_code'] ?? null,
                        'wallet_balance' => (float) ($row['wallet_balance'] ?? 0),
                        'tier_id' => $row['tier_id'] ?? null,
                    ],
                ]);
                if ($saved) {
                    $planned++;
                    $rulePlanned++;
                }
            }

            if ($rulePlanned > 0) {
                $messages[] = ReminderSchedule::kindLabel($kind) . ': ' . $rulePlanned . ' یادآوری زمان‌بندی شد.';
            }
        }

        return ['ok' => true, 'planned' => $planned, 'evaluated' => $evaluated, 'messages' => $messages];
    }

    /**
     * Send every reminder whose time has come (cron "club_reminders", every 15 min).
     *
     * @return array{ok: bool, sent: int, skipped: int, failed: int, messages: list<string>}
     */
    public function dispatch(int $limit = 100): array
    {
        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $messages = [];

        $sms = new SmsService();
        $smsSettings = (new \App\Repositories\SmsRepository())->settings();
        if (empty($smsSettings['sms_enabled'])) {
            return ['ok' => true, 'sent' => 0, 'skipped' => 0, 'failed' => 0, 'messages' => ['پیامک غیرفعال است.']];
        }

        $options = $this->settings->clubOptions();
        if (empty($options['enabled'])) {
            return ['ok' => true, 'sent' => 0, 'skipped' => 0, 'failed' => 0, 'messages' => ['باشگاه مشتریان غیرفعال است.']];
        }
        $dailyLimit = (int) ($options['campaign_daily_limit'] ?? 0);
        if ($dailyLimit > 0 && $this->reminders->countSentToday() >= $dailyLimit) {
            return ['ok' => true, 'sent' => 0, 'skipped' => 0, 'failed' => 0, 'messages' => ['سقف ارسال روزانه پر شده است.']];
        }

        foreach ($this->reminders->dueNow($limit) as $reminder) {
            $skipReason = $this->skipReason($reminder);
            if ($skipReason !== '') {
                $this->reminders->markSkipped((int) $reminder['id'], $skipReason);
                $skipped++;
                continue;
            }

            $rule = $this->rules->find((int) $reminder['rule_id']);
            $message = $this->renderMessage($rule !== null ? (string) $rule['template'] : '', $reminder);
            if ($message === '') {
                $this->reminders->markSkipped((int) $reminder['id'], 'قالب پیام خالی است.');
                $skipped++;
                continue;
            }

            try {
                $logId = $sms->sendRaw(
                    (string) $reminder['phone_number'],
                    $message,
                    'club_reminder',
                    (int) $reminder['customer_id']
                );
                if ($logId === null) {
                    $this->reminders->markFailed((int) $reminder['id'], 'ارسال پیامک ممکن نشد.');
                    $failed++;
                    continue;
                }
                $this->reminders->markSent((int) $reminder['id'], $logId, $message);
                $sent++;
            } catch (\Throwable $exception) {
                $this->reminders->markFailed((int) $reminder['id'], $exception->getMessage());
                $failed++;
            }
        }

        if ($sent > 0) {
            $messages[] = $sent . ' یادآوری ارسال شد.';
        }
        if ($skipped > 0) {
            $messages[] = $skipped . ' یادآوری رد شد.';
        }
        if ($failed > 0) {
            $messages[] = $failed . ' یادآوری ناموفق بود.';
        }

        return ['ok' => true, 'sent' => $sent, 'skipped' => $skipped, 'failed' => $failed, 'messages' => $messages];
    }

    /**
     * Group SMS to a filtered set of customers.
     *
     * @param array<string, mixed> $filters
     * @return array{ok: bool, recipients: int, sent: int, skipped: int, errors: list<string>}
     */
    public function campaign(array $filters, string $message, bool $dryRun = true, int $limit = 500): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['ok' => false, 'recipients' => 0, 'sent' => 0, 'skipped' => 0, 'errors' => ['متن پیام خالی است.']];
        }
        if (!$this->settings->clubEnabled()) {
            return ['ok' => false, 'recipients' => 0, 'sent' => 0, 'skipped' => 0, 'errors' => ['باشگاه مشتریان غیرفعال است.']];
        }

        $customers = (new CustomerRepository())->search($filters, $limit);
        $recipients = count($customers);
        if ($dryRun) {
            return ['ok' => true, 'recipients' => $recipients, 'sent' => 0, 'skipped' => 0, 'errors' => []];
        }

        $options = $this->settings->clubOptions();
        $dailyLimit = (int) ($options['campaign_daily_limit'] ?? 0);
        $intervalDays = (int) ($options['campaign_min_interval_days'] ?? 0);
        $since = date('Y-m-d H:i:s', strtotime('-' . $intervalDays . ' days'));

        $sms = new SmsService();
        $sent = 0;
        $skipped = 0;
        $errors = [];

        foreach ($customers as $customer) {
            if ($dailyLimit > 0 && $this->reminders->countSentToday() >= $dailyLimit) {
                $errors[] = 'سقف ارسال روزانه رسید؛ ادامه کمپین متوقف شد.';
                break;
            }
            $phone = trim((string) ($customer['phone_number'] ?? ''));
            if ($phone === '') {
                $skipped++;
                continue;
            }
            if ($intervalDays > 0 && $this->reminders->countSentSince((int) $customer['id'], $since) > 0) {
                $skipped++;
                continue;
            }

            try {
                $logId = $sms->sendRaw($phone, $message, 'club_campaign', (int) $customer['id']);
                if ($logId === null) {
                    $skipped++;
                    continue;
                }
                $this->reminders->recordSent(
                    (int) $customer['id'],
                    'custom',
                    $message,
                    $logId,
                    'campaign:' . (int) $customer['id'] . ':' . date('Y-m-d') . ':' . substr(md5($message), 0, 8)
                );
                $sent++;
            } catch (\Throwable $exception) {
                $skipped++;
            }
        }

        return ['ok' => true, 'recipients' => $recipients, 'sent' => $sent, 'skipped' => $skipped, 'errors' => $errors];
    }

    /** @return array<string, int> */
    public function stats(): array
    {
        return $this->reminders->stats();
    }

    /** @return array<int, array<string, mixed>> */
    public function upcomingForCustomer(int $customerId, int $limit = 5): array
    {
        return $this->reminders->upcomingForCustomer($customerId, $limit);
    }

    /**
     * @param array<string, mixed> $reminder
     */
    private function skipReason(array $reminder): string
    {
        if (trim((string) ($reminder['phone_number'] ?? '')) === '') {
            return 'شماره تماس ندارد.';
        }

        $meta = json_decode((string) ($reminder['meta'] ?? ''), true);
        $anchor = is_array($meta) ? (string) ($meta['anchor_at'] ?? '') : '';
        if ($anchor === '') {
            return '';
        }

        // The customer came back (or renewed) after the reminder was planned.
        $customerId = (int) $reminder['customer_id'];
        if ((string) $reminder['kind'] === 'winback') {
            $last = (new PurchaseRepository())->lastActivePurchaseAt($customerId);
            if ($last !== null && strtotime($last) > strtotime($anchor)) {
                return 'مشتری پس از زمان‌بندی خرید کرده است.';
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $reminder
     */
    private function renderMessage(string $template, array $reminder): string
    {
        if (trim($template) === '') {
            return '';
        }
        $meta = json_decode((string) ($reminder['meta'] ?? ''), true);
        $meta = is_array($meta) ? $meta : [];

        $customer = [
            'id' => (int) $reminder['customer_id'],
            'first_name' => (string) ($reminder['first_name'] ?? ''),
            'last_name' => (string) ($reminder['last_name'] ?? ''),
            'phone_number' => (string) ($reminder['phone_number'] ?? ''),
            'wallet_balance' => (float) ($reminder['wallet_balance'] ?? 0),
            'contract_number' => (string) ($reminder['contract_number'] ?? ''),
        ];

        $vars = [
            'expiring_amount' => \money((float) ($meta['wallet_balance'] ?? $customer['wallet_balance'])),
            'expiry_date' => $this->jalaliDate((string) ($meta['anchor_at'] ?? '')),
            'contract_ends_at' => $this->jalaliDate((string) ($meta['anchor_at'] ?? '')),
            'contract_number' => (string) ($reminder['contract_number'] ?? ''),
            'expiring_amount_raw' => (float) ($meta['wallet_balance'] ?? 0),
        ];

        return trim((new SmsTemplateRenderer())->render($template, $customer, $vars));
    }

    private function jalaliDate(string $date): string
    {
        $timestamp = strtotime(substr($date, 0, 10));
        if ($timestamp === false) {
            return '';
        }

        return \App\Core\Jalali::formatDate(date('Y-m-d', $timestamp));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $rule
     */
    private function passesConditions(array $row, array $rule): bool
    {
        $conditions = json_decode((string) ($rule['conditions'] ?? ''), true);
        if (!is_array($conditions) || $conditions === []) {
            return true;
        }

        if (isset($conditions['min_purchase']) && (float) $conditions['min_purchase'] > 0) {
            if ((float) ($row['spend'] ?? 0) < (float) $conditions['min_purchase']) {
                return false;
            }
        }
        if (!empty($conditions['tier_ids']) && is_array($conditions['tier_ids'])) {
            $tiers = array_map('intval', $conditions['tier_ids']);
            if (!in_array((int) ($row['tier_id'] ?? 0), $tiers, true)) {
                return false;
            }
        }
        if (!empty($conditions['segment'])) {
            if ((string) ($row['segment_code'] ?? '') !== (string) $conditions['segment']) {
                return false;
            }
        }

        return true;
    }

    private function tableReady(): bool
    {
        try {
            \App\Core\Database::pdo()->query('SELECT 1 FROM club_reminders LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
