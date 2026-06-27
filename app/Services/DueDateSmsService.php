<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Jalali;
use App\Repositories\DueDateSmsHistoryRepository;
use App\Repositories\SmsRepository;

final class DueDateSmsService
{
    private DueDateSmsHistoryRepository $history;
    private SmsRepository $smsRepo;

    public function __construct()
    {
        $this->history = new DueDateSmsHistoryRepository();
        $this->smsRepo = new SmsRepository();
    }

    public function sendCreated(array $dueDate): ?int
    {
        $settings = $this->smsRepo->settings();
        if (empty($settings['sms_enabled']) || empty($settings['due_date_sms_enabled'])) {
            return null;
        }

        $dueDateId = (int) $dueDate['id'];
        if ($this->history->exists($dueDateId, 'created')) {
            return null;
        }

        $customer = [
            'id' => (int) $dueDate['customer_id'],
            'first_name' => $dueDate['first_name'] ?? '',
            'last_name' => $dueDate['last_name'] ?? '',
            'phone_number' => $dueDate['phone_number'] ?? '',
        ];

        $logId = (new SmsService())->sendEvent('due_date', $customer, $this->templateVars($dueDate));
        $this->history->insert($dueDateId, 'created', $logId);

        return $logId;
    }

    /** @return array{ok: bool, messages: string[]} */
    public function runReminders(): array
    {
        $settings = $this->smsRepo->settings();
        if (empty($settings['sms_enabled']) || empty($settings['due_date_reminder_sms_enabled'])) {
            return ['ok' => true, 'messages' => ['Due date reminder SMS is disabled.']];
        }

        $messages = [];
        foreach (['before_3d', 'before_1d', 'on_day', 'after_overdue'] as $kind) {
            foreach ((new \App\Repositories\DueDateRepository())->dueForReminder($kind) as $row) {
                $dueDateId = (int) $row['id'];
                if ($this->history->exists($dueDateId, $kind)) {
                    continue;
                }

                $customer = [
                    'id' => (int) $row['customer_id'],
                    'first_name' => $row['first_name'] ?? '',
                    'last_name' => $row['last_name'] ?? '',
                    'phone_number' => $row['phone_number'] ?? '',
                ];

                $logId = (new SmsService())->sendEvent('due_date_reminder', $customer, $this->templateVars($row));
                $this->history->insert($dueDateId, $kind, $logId);
                $messages[] = "Due date reminder ({$kind}) attempted for #{$dueDateId}.";
            }
        }

        return ['ok' => true, 'messages' => $messages ?: ['No due date reminders to send today.']];
    }

    /** @return array<string, string> */
    private function templateVars(array $dueDate): array
    {
        return [
            'due_amount' => (string) ($dueDate['amount'] ?? ''),
            'due_date' => Jalali::formatDate($dueDate['due_date'] ?? null),
            'reference_number' => (string) ($dueDate['reference_number'] ?? ''),
            'due_type_label' => DueDateService::dueTypeLabel((string) ($dueDate['due_type'] ?? 'other')),
        ];
    }
}
