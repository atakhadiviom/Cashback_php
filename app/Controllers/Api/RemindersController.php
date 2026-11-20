<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ClubReminderRepository;
use App\Repositories\ClubReminderRuleRepository;
use App\Services\ClubReminderService;
use App\Services\ReminderSchedule;

final class RemindersController extends ApiController
{
    public function rules(array $params = []): void
    {
        $items = [];
        foreach ((new ClubReminderRuleRepository())->all() as $rule) {
            $items[] = $this->ruleResource($rule);
        }

        $this->ok(['items' => $items, 'total' => count($items)]);
    }

    public function createRule(array $params = []): void
    {
        $payload = $this->rulePayload($this->body(), null);
        if (is_string($payload)) {
            $this->fail($payload, 422);
            return;
        }

        $id = (new ClubReminderRuleRepository())->create($payload);
        $this->ok(['id' => $id], 201);
    }

    public function updateRule(array $params): void
    {
        $repository = new ClubReminderRuleRepository();
        $existing = $repository->find($params['id']);
        if ($existing === null) {
            $this->fail('قاعده یافت نشد.', 404);
            return;
        }

        $payload = $this->rulePayload($this->body(), $existing);
        if (is_string($payload)) {
            $this->fail($payload, 422);
            return;
        }

        $repository->update($params['id'], $payload);
        $this->ok(['rule' => $this->ruleResource($repository->find($params['id']) ?? $existing)]);
    }

    public function deleteRule(array $params): void
    {
        (new ClubReminderRuleRepository())->delete($params['id']);
        $this->ok(['deleted' => true, 'id' => $params['id']]);
    }

    public function queue(array $params = []): void
    {
        $filters = [
            'status' => (string) ($_GET['status'] ?? ''),
            'kind' => (string) ($_GET['kind'] ?? ''),
            'customer_id' => (string) ($_GET['customer_id'] ?? ''),
            'date_from' => $this->date((string) ($_GET['date_from'] ?? '')),
            'date_to' => $this->date((string) ($_GET['date_to'] ?? '')),
        ];
        [$limit] = $this->paging();
        $repository = new ClubReminderRepository();
        $rows = $repository->search($filters, max(1, min(200, $limit)));

        $this->ok([
            'items' => $rows,
            'total' => count($rows),
            'stats' => $repository->stats(),
        ]);
    }

    public function plan(array $params = []): void
    {
        $result = (new ClubReminderService())->plan();
        $this->ok([
            'planned' => $result['planned'],
            'evaluated' => $result['evaluated'],
            'messages' => $result['messages'],
        ]);
    }

    public function dispatch(array $params = []): void
    {
        $limit = (int) $this->input('limit', 100);
        $result = (new ClubReminderService())->dispatch(max(1, min(500, $limit)));
        $this->ok([
            'sent' => $result['sent'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
            'messages' => $result['messages'],
        ]);
    }

    /**
     * @param array<string, mixed> $rule
     * @return array<string, mixed>
     */
    private function ruleResource(array $rule): array
    {
        return [
            'id' => (int) $rule['id'],
            'kind' => $rule['kind'],
            'kind_label' => ReminderSchedule::kindLabel((string) $rule['kind']),
            'label' => $rule['label'],
            'enabled' => (int) $rule['enabled'] === 1,
            'direction' => $rule['direction'],
            'offset_days' => (int) $rule['offset_days'],
            'time_of_day' => substr((string) $rule['time_of_day'], 0, 5),
            'template' => $rule['template'],
            'conditions' => json_decode((string) ($rule['conditions'] ?? ''), true),
            'sort_order' => (int) $rule['sort_order'],
        ];
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed>|null $existing
     * @return array<string, mixed>|string payload or an error message
     */
    private function rulePayload(array $body, ?array $existing): array|string
    {
        $kind = (string) ($existing['kind'] ?? ($body['kind'] ?? ''));
        if ($existing === null && !array_key_exists($kind, ReminderSchedule::KINDS)) {
            return 'نوع یادآوری نامعتبر است.';
        }

        $template = trim((string) ($body['template'] ?? ($existing['template'] ?? '')));
        if ($template === '') {
            return 'متن پیام الزامی است.';
        }

        $conditions = $body['conditions'] ?? null;
        if (is_array($conditions)) {
            $conditions = $conditions === [] ? null : json_encode($conditions, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($conditions)) {
            $conditions = trim($conditions);
            if ($conditions === '') {
                $conditions = null;
            } elseif (json_decode($conditions, true) === null) {
                return 'conditions باید JSON معتبر باشد.';
            }
        } else {
            $conditions = null;
        }

        $direction = (string) ($body['direction'] ?? ($existing['direction'] ?? 'after'));

        return [
            'kind' => $kind,
            'label' => mb_substr(trim((string) ($body['label'] ?? ($existing['label'] ?? ReminderSchedule::kindLabel($kind)))), 0, 80),
            'enabled' => !empty($body['enabled']) ? 1 : 0,
            'direction' => $direction === 'before' ? 'before' : 'after',
            'offset_days' => max(0, min(3650, (int) (\normalize_digits((string) ($body['offset_days'] ?? ($existing['offset_days'] ?? 0)))))),
            'time_of_day' => ReminderSchedule::normalizeTime((string) ($body['time_of_day'] ?? ($existing['time_of_day'] ?? '10:00'))),
            'template' => mb_substr($template, 0, 1000),
            'conditions' => $conditions,
            'sort_order' => (int) ($body['sort_order'] ?? ($existing['sort_order'] ?? 0)),
        ];
    }
}
