<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CashbackSettingsRepository;
use App\Repositories\SegmentRepository;

final class SegmentService
{
    private SegmentRepository $segments;
    private CashbackSettingsRepository $settings;
    private RfmCalculator $calculator;

    public function __construct()
    {
        $this->segments = new SegmentRepository();
        $this->settings = new CashbackSettingsRepository();
        $this->calculator = new RfmCalculator();
    }

    /**
     * @return array{r: array<int, float>, f: array<int, float>, m: array<int, float>}
     */
    public function thresholds(): array
    {
        $options = $this->settings->clubOptions();
        $rfm = $options['rfm'] ?? [];
        $defaults = CashbackSettingsRepository::clubDefaults()['rfm'];

        return [
            'r' => $this->band($rfm['r'] ?? null, $defaults['r']),
            'f' => $this->band($rfm['f'] ?? null, $defaults['f']),
            'm' => $this->band($rfm['m'] ?? null, $defaults['m']),
        ];
    }

    /**
     * Recompute the RFM segment of every purchasing customer.
     *
     * @return array{customers: int, saved: int, pruned: int, at: ?string}
     */
    public function refreshAll(int $limit = 0): array
    {
        $thresholds = $this->thresholds();
        $aggregates = $this->segments->purchaseAggregates($limit);
        $now = time();
        $rows = [];

        foreach ($aggregates as $row) {
            $lastAt = (string) ($row['last_at'] ?? '');
            $lastStamp = $lastAt !== '' ? (int) strtotime($lastAt) : 0;
            $recency = $lastStamp > 0 ? (int) max(0, floor(($now - $lastStamp) / 86400)) : 99999;

            $score = $this->calculator->score(
                $recency,
                (int) ($row['frequency'] ?? 0),
                (float) ($row['monetary'] ?? 0),
                $thresholds
            );

            $rows[] = [
                'customer_id' => (int) $row['customer_id'],
                'segment_code' => $score['segment'],
                'r_score' => $score['r'],
                'f_score' => $score['f'],
                'm_score' => $score['m'],
                'rfm_score' => $score['rfm'],
                'recency_days' => $recency,
                'frequency' => (int) ($row['frequency'] ?? 0),
                'monetary' => (float) ($row['monetary'] ?? 0),
            ];
        }

        $saved = $this->segments->upsertMembers($rows);
        $pruned = $this->segments->prune();

        return [
            'customers' => count($rows),
            'saved' => $saved,
            'pruned' => $pruned,
            'at' => $this->segments->lastComputedAt(),
        ];
    }

    /**
     * Cron helper: refresh only when the configured interval has elapsed.
     *
     * @return array{customers: int, saved: int, pruned: int, at: ?string}|null
     */
    public function refreshIfStale(): ?array
    {
        $options = $this->settings->clubOptions();
        $hours = max(1, (int) ($options['rfm']['refresh_hours'] ?? 24));
        $last = $this->segments->lastComputedAt();
        if ($last !== null && strtotime($last) > strtotime('-' . $hours . ' hours')) {
            return null;
        }

        return $this->refreshAll();
    }

    /**
     * Suggest RFM thresholds from the actual data (20/40/60/80 percentiles).
     *
     * @return array{r: list<float>, f: list<float>, m: list<float>}
     */
    public function suggestThresholds(): array
    {
        $aggregates = $this->segments->purchaseAggregates(20000);
        $now = time();
        $recency = [];
        $frequency = [];
        $monetary = [];

        foreach ($aggregates as $row) {
            $lastAt = (string) ($row['last_at'] ?? '');
            $stamp = $lastAt !== '' ? (int) strtotime($lastAt) : 0;
            $recency[] = $stamp > 0 ? (float) max(0, floor(($now - $stamp) / 86400)) : 99999.0;
            $frequency[] = (float) ($row['frequency'] ?? 0);
            $monetary[] = (float) ($row['monetary'] ?? 0);
        }

        $fallback = CashbackSettingsRepository::clubDefaults()['rfm'];

        return [
            'r' => $this->percentiles($recency) ?? array_map('floatval', $fallback['r']),
            'f' => $this->percentiles($frequency) ?? array_map('floatval', $fallback['f']),
            'm' => $this->percentiles($monetary) ?? array_map('floatval', $fallback['m']),
        ];
    }

    /**
     * @param list<float> $values
     * @return list<float>|null
     */
    private function percentiles(array $values): ?array
    {
        $values = array_values($values);
        sort($values);
        $count = count($values);
        if ($count < 5) {
            return null;
        }

        $cuts = [];
        foreach ([0.2, 0.4, 0.6, 0.8] as $p) {
            $index = (int) floor($p * ($count - 1));
            $cuts[] = round($values[$index], 2);
        }

        return $cuts;
    }

    /**
     * @param mixed $value
     * @param array<int, int|float> $default
     * @return array<int, float>
     */
    private function band(mixed $value, array $default): array
    {
        if (!is_array($value) || count($value) !== 4) {
            return array_map(static fn ($v): float => (float) $v, $default);
        }
        $numbers = [];
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $numbers[] = (float) $item;
            }
        }
        if (count($numbers) !== 4) {
            return array_map(static fn ($v): float => (float) $v, $default);
        }
        sort($numbers);

        return $numbers;
    }
}
