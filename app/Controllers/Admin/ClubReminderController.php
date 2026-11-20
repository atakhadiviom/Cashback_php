<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\ClubReminderRepository;
use App\Repositories\ClubReminderRuleRepository;
use App\Repositories\SegmentRepository;
use App\Repositories\TierRepository;
use App\Services\ActivityLogger;
use App\Services\ClubReminderService;
use App\Services\ReminderSchedule;
use App\Services\SmsService;

final class ClubReminderController
{
    public function index(): void
    {
        $queue = [
            'status' => (string) ($_GET['status'] ?? 'pending'),
            'kind' => (string) ($_GET['kind'] ?? ''),
            'date_from' => (string) ($_GET['date_from'] ?? ''),
            'date_to' => (string) ($_GET['date_to'] ?? ''),
        ];

        View::render('admin/club_reminders', [
            'rules' => (new ClubReminderRuleRepository())->all(),
            'kinds' => ReminderSchedule::KINDS,
            'queue' => (new ClubReminderRepository())->search($queue, 200),
            'filters' => $queue,
            'stats' => (new ClubReminderService())->stats(),
            'segments' => (new SegmentRepository())->catalog(),
            'tiers' => (new TierRepository())->all(),
            'placeholders' => [
                '{full_name}', '{wallet_balance}', '{tier_name}', '{expiring_amount}',
                '{expiry_date}', '{contract_number}', '{contract_ends_at}', '{date}',
            ],
        ]);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $data = $this->ruleFromPost(true);
        if ($data === null) {
            Flash::set('danger', 'نوع یادآوری نامعتبر است.');
            \redirect('/admin/club-reminders');
            return;
        }

        (new ClubReminderRuleRepository())->create($data);
        (new ActivityLogger())->log('settings_update', 'قاعده یادآوری باشگاه اضافه شد: ' . $data['label']);
        Flash::set('success', 'قاعده یادآوری اضافه شد.');
        \redirect('/admin/club-reminders');
    }

    public function update(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $repository = new ClubReminderRuleRepository();
        if ($id <= 0 || $repository->find($id) === null) {
            Flash::set('danger', 'قاعده یادآوری یافت نشد.');
            \redirect('/admin/club-reminders');
            return;
        }

        $data = $this->ruleFromPost(false);
        if ($data === null) {
            Flash::set('danger', 'داده‌های قاعده معتبر نیست.');
            \redirect('/admin/club-reminders');
            return;
        }

        $repository->update($id, $data);
        (new ActivityLogger())->log('settings_update', 'قاعده یادآوری ویرایش شد: ' . $data['label']);
        Flash::set('success', 'قاعده یادآوری ذخیره شد.');
        \redirect('/admin/club-reminders');
    }

    public function toggle(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        (new ClubReminderRuleRepository())->toggle($id, (string) ($_POST['enabled'] ?? '0') === '1');
        Flash::set('success', 'وضعیت قاعده تغییر کرد.');
        \redirect('/admin/club-reminders');
    }

    public function delete(): void
    {
        Csrf::requireValid();
        (new ClubReminderRuleRepository())->delete((int) ($_POST['id'] ?? 0));
        Flash::set('success', 'قاعده یادآوری حذف شد.');
        \redirect('/admin/club-reminders');
    }

    public function plan(): void
    {
        Csrf::requireValid();
        $result = (new ClubReminderService())->plan();
        $message = implode(' ', $result['messages']);
        Flash::set($result['ok'] ? 'success' : 'danger', $message !== '' ? $message : 'برنامه‌ریزی انجام شد؛ یادآوری تازه‌ای لازم نبود.');
        \redirect('/admin/club-reminders');
    }

    public function dispatch(): void
    {
        Csrf::requireValid();
        $result = (new ClubReminderService())->dispatch(100);
        $message = implode(' ', $result['messages']);
        Flash::set('success', $message !== '' ? $message : 'یادآوری سررسیده‌ای برای ارسال نبود.');
        \redirect('/admin/club-reminders');
    }

    public function campaign(): void
    {
        Csrf::requireValid();
        $filters = [
            'segment' => (string) ($_POST['segment'] ?? ''),
            'tier_id' => (string) ($_POST['tier_id'] ?? ''),
            'q' => trim((string) ($_POST['q'] ?? '')),
            'birthday_month' => (string) ($_POST['birthday_month'] ?? ''),
        ];
        $message = trim((string) ($_POST['message'] ?? ''));
        $dryRun = (string) ($_POST['mode'] ?? 'preview') !== 'send';

        $result = (new ClubReminderService())->campaign($filters, $message, $dryRun, 500);
        if (!$result['ok']) {
            Flash::set('danger', implode(' ', $result['errors']));
            \redirect('/admin/club-reminders');
            return;
        }

        if ($dryRun) {
            Flash::set('info', 'پیش‌نمایش: ' . $result['recipients'] . ' گیرنده با این فیلتر پیدا شد. برای ارسال، گزینه «ارسال واقعی» را انتخاب کنید.');
        } else {
            (new ActivityLogger())->log('sms_sent', 'کمپین باشگاه: ' . $result['sent'] . ' پیامک ارسال شد.');
            Flash::set('success', 'کمپین اجرا شد: ' . $result['sent'] . ' ارسال، ' . $result['skipped'] . ' رد شده.');
        }
        \redirect('/admin/club-reminders');
    }

    public function test(): void
    {
        Csrf::requireValid();
        $phone = trim(\normalize_digits((string) ($_POST['phone'] ?? '')));
        $message = trim((string) ($_POST['message'] ?? 'پیام آزمایشی باشگاه مشتریان'));
        if (!preg_match('/^09\d{9}$/', $phone)) {
            Flash::set('danger', 'شماره موبایل معتبر نیست.');
            \redirect('/admin/club-reminders');
            return;
        }

        $logId = (new SmsService())->sendRaw($phone, $message, 'club_reminder_test', null);
        Flash::set($logId !== null ? 'success' : 'danger', $logId !== null ? 'پیام آزمایشی ارسال شد.' : 'ارسال پیام ممکن نشد؛ تنظیمات پیامک را بررسی کنید.');
        \redirect('/admin/club-reminders');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function ruleFromPost(bool $withKind): ?array
    {
        $kind = (string) ($_POST['kind'] ?? 'custom');
        if ($withKind && !array_key_exists($kind, ReminderSchedule::KINDS)) {
            return null;
        }

        $direction = (string) ($_POST['direction'] ?? 'after') === 'before' ? 'before' : 'after';
        $label = trim((string) ($_POST['label'] ?? ''));
        if ($label === '') {
            $label = ReminderSchedule::kindLabel($kind);
        }
        $template = trim((string) ($_POST['template'] ?? ''));
        if ($template === '') {
            return null;
        }

        $conditions = [];
        $minPurchase = (float) \parse_money_input((string) ($_POST['min_purchase'] ?? '0'));
        if ($minPurchase > 0) {
            $conditions['min_purchase'] = $minPurchase;
        }
        $segment = trim((string) ($_POST['segment'] ?? ''));
        if ($segment !== '') {
            $conditions['segment'] = $segment;
        }
        $tierIds = array_values(array_filter(array_map('intval', (array) ($_POST['tier_ids'] ?? [])), static fn (int $id): bool => $id > 0));
        if ($tierIds !== []) {
            $conditions['tier_ids'] = $tierIds;
        }

        $data = [
            'label' => mb_substr($label, 0, 80),
            'enabled' => isset($_POST['enabled']) ? 1 : 0,
            'direction' => $direction,
            'offset_days' => max(0, min(3650, (int) \normalize_digits((string) ($_POST['offset_days'] ?? 0)))),
            'time_of_day' => ReminderSchedule::normalizeTime((string) ($_POST['time_of_day'] ?? '10:00')),
            'template' => mb_substr($template, 0, 1000),
            'conditions' => $conditions === [] ? null : json_encode($conditions, JSON_UNESCAPED_UNICODE),
            'sort_order' => (int) \normalize_digits((string) ($_POST['sort_order'] ?? 0)),
            'kind' => $kind,
        ];

        return $data;
    }
}
