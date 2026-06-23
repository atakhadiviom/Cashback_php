<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReminderRepository;

final class ReminderService
{
    private ReminderRepository $reminders;

    public function __construct()
    {
        $this->reminders = new ReminderRepository();
    }

    public function syncForFollowup(int $followupId, array $followupData): void
    {
        $nextContactDate = (string) ($followupData['next_contact_date'] ?? '');
        $reminderTime = (string) ($followupData['reminder_time'] ?? '');
        $salesStatus = (string) ($followupData['sales_status'] ?? '');

        if ($nextContactDate === '' || $reminderTime === '' || in_array($salesStatus, ['won', 'lost'], true)) {
            $this->reminders->deleteForFollowup($followupId);
            return;
        }

        $now = \current_datetime();
        $this->reminders->upsertForFollowup([
            'followup_id' => $followupId,
            'customer_id' => (int) $followupData['customer_id'],
            'operator_id' => (int) $followupData['operator_id'],
            'remind_at' => $nextContactDate . ' ' . $reminderTime,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function markSeen(int $id): ?array
    {
        $reminder = $this->reminders->find($id);
        if (!$reminder) {
            return null;
        }
        $this->reminders->markSeen($id);
        (new ActivityLogger())->log('reminder_seen', 'یادآوری پیگیری مشاهده شد.', (int) $reminder['customer_id']);
        return $reminder;
    }

    public function markDone(int $id): ?array
    {
        $reminder = $this->reminders->find($id);
        if (!$reminder) {
            return null;
        }
        $this->reminders->markDone($id);
        (new ActivityLogger())->log('reminder_done', 'یادآوری پیگیری انجام شد.', (int) $reminder['customer_id']);
        return $reminder;
    }

    public static function statusLabel(string $status): string
    {
        return [
            'pending' => 'در انتظار',
            'seen' => 'دیده شده',
            'done' => 'انجام شده',
            'missed' => 'معوق',
        ][$status] ?? $status;
    }
}
