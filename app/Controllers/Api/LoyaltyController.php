<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CustomerTagRepository;
use App\Repositories\SegmentRepository;
use App\Repositories\TierRepository;
use App\Services\CustomerTierService;
use App\Services\SegmentService;

final class LoyaltyController extends ApiController
{
    public function tiers(array $params = []): void
    {
        $items = [];
        foreach ((new TierRepository())->all() as $tier) {
            $items[] = [
                'id' => (int) $tier['id'],
                'name' => $tier['name'],
                'min_lifetime_spend' => (float) $tier['min_lifetime_spend'],
                'max_lifetime_spend' => $tier['max_lifetime_spend'] !== null ? (float) $tier['max_lifetime_spend'] : null,
                'cashback_percent' => (float) $tier['cashback_percent'],
                'is_active' => (int) $tier['is_active'] === 1,
            ];
        }

        $this->ok(['items' => $items, 'total' => count($items)]);
    }

    public function recalculateTiers(array $params = []): void
    {
        $result = (new CustomerTierService())->recomputeAll();
        $this->ok(['processed' => $result['processed'], 'changed' => $result['changed']]);
    }

    public function segments(array $params = []): void
    {
        $repository = new SegmentRepository();
        $catalog = [];
        foreach ($repository->catalog() as $segment) {
            $catalog[] = [
                'code' => $segment['code'],
                'name' => $segment['name'],
                'color' => $segment['color'],
            ];
        }

        $distribution = [];
        foreach ($repository->distribution() as $row) {
            $distribution[] = [
                'code' => (string) $row['segment_code'],
                'members' => (int) $row['members'],
                'revenue' => (float) $row['revenue'],
                'average' => (float) $row['average'],
                'average_recency_days' => (float) $row['average_recency'],
            ];
        }

        $this->ok([
            'catalog' => $catalog,
            'distribution' => $distribution,
            'last_computed_at' => $repository->lastComputedAt(),
            'thresholds' => (new SegmentService())->thresholds(),
        ]);
    }

    public function refreshSegments(array $params = []): void
    {
        $result = (new SegmentService())->refreshAll();
        $this->ok([
            'customers' => $result['customers'],
            'saved' => $result['saved'],
            'pruned' => $result['pruned'],
            'computed_at' => $result['at'],
        ]);
    }

    public function tags(array $params = []): void
    {
        $items = [];
        foreach ((new CustomerTagRepository())->all() as $tag) {
            $items[] = [
                'id' => (int) $tag['id'],
                'name' => $tag['name'],
                'customers' => (int) ($tag['customers'] ?? 0),
            ];
        }

        $this->ok(['items' => $items, 'total' => count($items)]);
    }

    public function createTag(array $params = []): void
    {
        $name = mb_substr(trim((string) ($this->body()['name'] ?? '')), 0, 80);
        if ($name === '') {
            $this->fail('نام برچسب الزامی است.', 422);
            return;
        }

        $repository = new CustomerTagRepository();
        if ($repository->exists($name)) {
            $this->fail('این برچسب از قبل وجود دارد.', 409);
            return;
        }

        $id = $repository->create($name);
        $this->ok(['tag' => ['id' => $id, 'name' => $name, 'customers' => 0]], 201);
    }

    public function updateTag(array $params): void
    {
        $name = mb_substr(trim((string) ($this->body()['name'] ?? '')), 0, 80);
        if ($name === '') {
            $this->fail('نام برچسب الزامی است.', 422);
            return;
        }

        $repository = new CustomerTagRepository();
        if ($repository->exists($name, $params['id'])) {
            $this->fail('این نام برچسب قبلاً استفاده شده است.', 409);
            return;
        }

        $repository->rename($params['id'], $name);
        $this->ok(['tag' => ['id' => $params['id'], 'name' => $name]]);
    }

    public function deleteTag(array $params): void
    {
        (new CustomerTagRepository())->delete($params['id']);
        $this->ok(['deleted' => true, 'id' => $params['id']]);
    }
}
