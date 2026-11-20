<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Jalali;
use App\Repositories\FestivalRepository;
use App\Repositories\SmsRepository;

final class FestivalService
{
    private FestivalRepository $festivals;

    public function __construct()
    {
        $this->festivals = new FestivalRepository();
    }

    public function preview(string $audience, string $value): int
    {
        return $this->festivals->countForAudience($audience, $value);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, id?: int, errors: array<string, string>}
     */
    public function store(array $input, bool $sendNow = false): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $template = trim((string) ($input['template'] ?? ''));
        $audience = (string) ($input['audience'] ?? 'club_members');
        $audienceValue = trim((string) ($input['audience_value'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'نام جشنواره الزامی است.';
        }
        if ($template === '') {
            $errors['template'] = 'متن پیامک الزامی است.';
        }
        if (!array_key_exists($audience, FestivalRepository::AUDIENCES)) {
            $errors['audience'] = 'مخاطب نامعتبر است.';
        }
        if (in_array($audience, ['segment', 'tier', 'tag'], true) && $audienceValue === '') {
            $errors['audience_value'] = 'برای این نوع مخاطب، مقدار را انتخاب کنید.';
        }

        $startsAt = $this->dateTime($input['starts_at'] ?? '', false);
        $endsAt = $this->dateTime($input['ends_at'] ?? '', true);
        if ($startsAt === '' || $endsAt === '') {
            $errors['starts_at'] = 'بازهٔ جشنواره معتبر نیست.';
        } elseif ($endsAt < $startsAt) {
            $errors['ends_at'] = 'پایان جشنواره باید بعد از شروع باشد.';
        }

        $sendAt = '';
        if ($sendNow) {
            $sendAt = \current_datetime();
        } elseif (trim((string) ($input['send_at'] ?? '')) !== '') {
            $sendAt = $this->dateTime($input['send_at'], false, (string) ($input['send_time'] ?? '10:00'));
            if ($sendAt === '') {
                $errors['send_at'] = 'زمان ارسال معتبر نیست.';
            }
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $percent = $this->nullableFloat($input['discount_percent'] ?? '');
        if ($percent !== null) {
            $percent = max(0.0, min(100.0, $percent));
        }

        $id = $this->festivals->create([
            'name' => mb_substr($name, 0, 120),
            'description' => mb_substr(trim((string) ($input['description'] ?? '')), 0, 500) ?: null,
            'discount_text' => mb_substr(trim((string) ($input['discount_text'] ?? '')), 0, 200) ?: null,
            'discount_percent' => $percent,
            'discount_code' => mb_substr(trim((string) ($input['discount_code'] ?? '')), 0, 40) ?: null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'send_at' => $sendAt !== '' ? $sendAt : null,
            'audience' => $audience,
            'audience_value' => in_array($audience, ['segment', 'tier', 'tag'], true) ? $audienceValue : null,
            'template' => mb_substr($template, 0, 1000),
            'status' => $sendAt !== '' ? 'scheduled' : 'draft',
            'created_by' => SystemUserService::actorId(),
        ]);

        if ($sendNow) {
            $this->send($id, false, 500);
        }

        return ['ok' => true, 'id' => $id, 'errors' => []];
    }

    /**
     * Send (or dry-run) one festival. Batches make it resumable on shared hosts.
     *
     * @return array{ok: bool, dry_run: bool, sent: int, skipped: int, failed: int, remaining: int, errors: array<string, string>}
     */
    public function send(int $id, bool $dryRun = false, int $limit = 500): array
    {
        $festival = $this->festivals->find($id);
        if ($festival === null) {
            return ['ok' => false, 'dry_run' => $dryRun, 'sent' => 0, 'skipped' => 0, 'failed' => 0, 'remaining' => 0, 'errors' => ['festival' => 'جشنواره یافت نشد.']];
        }
        if (!$dryRun && (string) $festival['status'] === 'sent') {
            return ['ok' => false, 'dry_run' => false, 'sent' => 0, 'skipped' => 0, 'failed' => 0, 'remaining' => 0, 'errors' => ['festival' => 'این جشنواره قبلاً ارسال شده است.']];
        }

        $smsSettings = (new SmsRepository())->settings();
        if (!$dryRun && empty($smsSettings['sms_enabled'])) {
            return ['ok' => false, 'dry_run' => false, 'sent' => 0, 'skipped' => 0, 'failed' => 0, 'remaining' => 0, 'errors' => ['sms' => 'پیامک در تنظیمات غیرفعال است.']];
        }

        $audience = $this->festivals->audience($festival, max(1, $limit));
        $sms = new SmsService();
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($audience as $customer) {
            $customerId = (int) $customer['id'];
            if ($this->festivals->wasSent($id, $customerId)) {
                $skipped++;
                continue;
            }
            $phone = trim((string) ($customer['phone_number'] ?? ''));
            if ($phone === '') {
                $skipped++;
                continue;
            }

            $message = $this->renderMessage($festival, $customer);
            if ($dryRun) {
                $sent++;
                continue;
            }

            try {
                $logId = $sms->sendRaw($phone, $message, 'club_festival', $customerId);
                if ($logId === null) {
                    $failed++;
                    continue;
                }
                $this->festivals->recordRecipient($id, $customerId, $logId);
                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        if (!$dryRun) {
            $sentTotal = $this->festivals->recipientsCount($id);
            $audienceTotal = $this->festivals->audienceCount($festival);
            $status = $sentTotal >= $audienceTotal ? 'sent' : 'sending';
            $this->festivals->setStatus($id, $status, $sentTotal);
        }

        $remaining = max(0, $this->festivals->audienceCount($festival) - $this->festivals->recipientsCount($id));

        return ['ok' => true, 'dry_run' => $dryRun, 'sent' => $sent, 'skipped' => $skipped, 'failed' => $failed, 'remaining' => $remaining, 'errors' => []];
    }

    /** @return array{sent: int, festivals: int} */
    public function dispatchDue(): array
    {
        $sent = 0;
        $count = 0;
        foreach ($this->festivals->due() as $festival) {
            $result = $this->send((int) $festival['id'], false, 500);
            if ($result['ok']) {
                $sent += $result['sent'];
                $count++;
            }
        }

        return ['sent' => $sent, 'festivals' => $count];
    }

    public function cancel(int $id): void
    {
        $this->festivals->setStatus($id, 'cancelled');
    }

    public function delete(int $id): void
    {
        $this->festivals->delete($id);
    }

    /**
     * @param array<string, mixed> $festival
     * @param array<string, mixed> $customer
     */
    private function renderMessage(array $festival, array $customer): string
    {
        $percent = $festival['discount_percent'] !== null ? (float) $festival['discount_percent'] : null;

        return trim((new SmsTemplateRenderer())->render((string) $festival['template'], $customer, [
            'festival_name' => (string) $festival['name'],
            'discount_text' => (string) ($festival['discount_text'] ?? ''),
            'discount_percent' => $percent !== null ? rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') : '',
            'discount_code' => (string) ($festival['discount_code'] ?? ''),
            'starts_at' => Jalali::formatDate(substr((string) $festival['starts_at'], 0, 10)),
            'ends_at' => Jalali::formatDate(substr((string) $festival['ends_at'], 0, 10)),
        ]));
    }

    /** Accepts Jalali or Gregorian date, optionally with a time. */
    private function dateTime(string $date, bool $endOfDay, string $time = ''): string
    {
        $date = trim(\normalize_digits($date));
        if ($date === '') {
            return '';
        }

        $datePart = substr($date, 0, 10);
        $converted = Jalali::parseInputToGregorian($datePart);
        if ($converted === null || $converted === '') {
            return '';
        }

        if (str_contains($date, ' ')) {
            $time = trim(substr($date, 10));
        }
        $time = trim(\normalize_digits($time));
        if ($time === '') {
            return $converted . ($endOfDay ? ' 23:59:59' : ' 00:00:00');
        }
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $matches)) {
            return '';
        }

        return sprintf('%s %02d:%02d:%02d', $converted, (int) $matches[1], (int) $matches[2], (int) ($matches[3] ?? 0));
    }

    private function nullableFloat(mixed $value): ?float
    {
        $raw = trim(str_replace(',', '', \normalize_digits((string) $value)));
        if ($raw === '') {
            return null;
        }

        return (float) $raw;
    }
}
