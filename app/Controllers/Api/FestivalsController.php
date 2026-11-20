<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\FestivalRepository;
use App\Services\FestivalService;

final class FestivalsController extends ApiController
{
    public function index(array $params = []): void
    {
        $items = [];
        foreach ((new FestivalRepository())->all() as $festival) {
            $items[] = $this->resource($festival);
        }

        $this->ok([
            'items' => $items,
            'total' => count($items),
            'audiences' => FestivalRepository::AUDIENCES,
        ]);
    }

    public function show(array $params): void
    {
        $festival = (new FestivalRepository())->find($params['id']);
        if ($festival === null) {
            $this->fail('جشنواره یافت نشد.', 404);
            return;
        }

        $repository = new FestivalRepository();
        $this->ok([
            'festival' => $this->resource($festival),
            'audience_count' => $repository->audienceCount($festival),
            'recipients' => $repository->recipientsCount((int) $festival['id']),
        ]);
    }

    public function create(array $params = []): void
    {
        $body = $this->body();
        $sendNow = !empty($body['send_now']);
        $result = (new FestivalService())->store($body, $sendNow);

        if (!$result['ok']) {
            $this->fail('ایجاد جشنواره انجام نشد.', 422, ['errors' => $result['errors']]);
            return;
        }

        $festival = (new FestivalRepository())->find((int) $result['id']);
        $this->ok(['festival' => $festival !== null ? $this->resource($festival) : ['id' => $result['id']]], 201);
    }

    public function update(array $params): void
    {
        // Festivals are immutable once created except for cancellation; the API
        // keeps it explicit instead of silently replacing a campaign's message.
        $festival = (new FestivalRepository())->find($params['id']);
        if ($festival === null) {
            $this->fail('جشنواره یافت نشد.', 404);
            return;
        }

        $status = (string) ($this->body()['status'] ?? '');
        if ($status !== 'cancelled') {
            $this->fail('فقط لغو جشنواره از طریق API مجاز است (status=cancelled).', 422);
            return;
        }

        (new FestivalService())->cancel($params['id']);
        $this->ok(['cancelled' => true, 'id' => $params['id']]);
    }

    public function destroy(array $params): void
    {
        if ((new FestivalRepository())->find($params['id']) === null) {
            $this->fail('جشنواره یافت نشد.', 404);
            return;
        }

        (new FestivalService())->delete($params['id']);
        $this->ok(['deleted' => true, 'id' => $params['id']]);
    }

    public function send(array $params): void
    {
        $body = $this->body();
        $dryRun = !empty($body['dry_run']) || (string) ($body['mode'] ?? '') === 'preview';
        $limit = (int) ($body['limit'] ?? 500);

        $result = (new FestivalService())->send($params['id'], $dryRun, max(1, min(500, $limit)));
        if (!$result['ok']) {
            $this->fail('ارسال جشنواره انجام نشد.', 422, ['errors' => $result['errors']]);
            return;
        }

        $this->ok([
            'dry_run' => $result['dry_run'],
            'sent' => $result['sent'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
            'remaining' => $result['remaining'],
        ]);
    }

    /**
     * @param array<string, mixed> $festival
     * @return array<string, mixed>
     */
    private function resource(array $festival): array
    {
        return [
            'id' => (int) $festival['id'],
            'name' => $festival['name'],
            'description' => $festival['description'],
            'discount_text' => $festival['discount_text'],
            'discount_percent' => $festival['discount_percent'] !== null ? (float) $festival['discount_percent'] : null,
            'discount_code' => $festival['discount_code'],
            'starts_at' => $festival['starts_at'],
            'ends_at' => $festival['ends_at'],
            'send_at' => $festival['send_at'],
            'audience' => $festival['audience'],
            'audience_value' => $festival['audience_value'],
            'template' => $festival['template'],
            'status' => $festival['status'],
            'sent_count' => (int) ($festival['sent_count'] ?? 0),
            'recipients' => (int) ($festival['recipients'] ?? 0),
        ];
    }
}
