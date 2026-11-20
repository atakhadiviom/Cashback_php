<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Pure RFM scoring: no database, no clocks — every input is explicit so the
 * behaviour is fully unit-tested (see tests/Unit/RfmCalculatorTest.php).
 */
final class RfmCalculator
{
    /** Ordered list of business segments; the first matching rule wins. */
    public const SEGMENT_LABELS = [
        'champions' => 'قهرمانان',
        'cant_lose' => 'نباید از دست برود',
        'loyal' => 'وفادار',
        'at_risk' => 'در خطر',
        'potential' => 'بالقوه وفادار',
        'new' => 'جدید',
        'promising' => 'امیدبخش',
        'needs_attention' => 'نیازمند توجه',
        'hibernating' => 'خواب‌رفته',
        'lost' => 'از دست رفته',
    ];

    public const SEGMENT_COLORS = [
        'champions' => 'success',
        'cant_lose' => 'danger',
        'loyal' => 'primary',
        'at_risk' => 'warning',
        'potential' => 'info',
        'new' => 'secondary',
        'promising' => 'info',
        'needs_attention' => 'warning',
        'hibernating' => 'secondary',
        'lost' => 'dark',
    ];

    /**
     * @param array{r: array<int, int|float>, f: array<int, int|float>, m: array<int, int|float>} $thresholds
     * @return array{r: int, f: int, m: int, rfm: int, segment: string}
     */
    public function score(int $recencyDays, int $frequency, float $monetary, array $thresholds): array
    {
        $r = $this->recencyScore($recencyDays, $thresholds['r'] ?? []);
        $f = $this->frequencyScore($frequency, $thresholds['f'] ?? []);
        $m = $this->monetaryScore($monetary, $thresholds['m'] ?? []);

        return [
            'r' => $r,
            'f' => $f,
            'm' => $m,
            'rfm' => $r * 100 + $f * 10 + $m,
            'segment' => $this->segmentFor($r, $f, $m),
        ];
    }

    public function segmentFor(int $r, int $f, int $m): string
    {
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'champions';
        }
        if ($r <= 2 && $f >= 4 && $m >= 4) {
            return 'cant_lose';
        }
        if ($r >= 3 && $f >= 3) {
            return 'loyal';
        }
        if ($r <= 2 && $f >= 3) {
            return 'at_risk';
        }
        if ($m >= 3 && $r >= 3) {
            return 'potential';
        }
        if ($f <= 2 && $r >= 4) {
            return 'new';
        }
        if ($r >= 4) {
            return 'promising';
        }
        if ($r === 3) {
            return 'needs_attention';
        }
        if ($r === 2) {
            return 'hibernating';
        }

        return 'lost';
    }

    public static function segmentLabel(string $code): string
    {
        return self::SEGMENT_LABELS[$code] ?? $code;
    }

    public static function segmentColor(string $code): string
    {
        return self::SEGMENT_COLORS[$code] ?? 'secondary';
    }

    /**
     * @param array<int, int|float> $thresholds ascending
     */
    private function recencyScore(int $days, array $thresholds): int
    {
        if ($days <= (int) ($thresholds[0] ?? 15)) {
            return 5;
        }
        if ($days <= (int) ($thresholds[1] ?? 45)) {
            return 4;
        }
        if ($days <= (int) ($thresholds[2] ?? 90)) {
            return 3;
        }
        if ($days <= (int) ($thresholds[3] ?? 180)) {
            return 2;
        }

        return 1;
    }

    /**
     * @param array<int, int|float> $thresholds ascending
     */
    private function frequencyScore(int $count, array $thresholds): int
    {
        $c1 = (int) ($thresholds[0] ?? 1);
        $c2 = (int) ($thresholds[1] ?? 2);
        $c3 = (int) ($thresholds[2] ?? 4);
        $c4 = (int) ($thresholds[3] ?? 8);

        if ($count >= $c4) {
            return 5;
        }
        if ($count >= $c3) {
            return 4;
        }
        if ($count >= $c2) {
            return 3;
        }
        if ($count >= $c1) {
            return 2;
        }

        return 1;
    }

    /**
     * @param array<int, int|float> $thresholds ascending
     */
    private function monetaryScore(float $amount, array $thresholds): int
    {
        $m1 = (float) ($thresholds[0] ?? 1000000);
        $m2 = (float) ($thresholds[1] ?? 5000000);
        $m3 = (float) ($thresholds[2] ?? 20000000);
        $m4 = (float) ($thresholds[3] ?? 50000000);

        if ($amount >= $m4) {
            return 5;
        }
        if ($amount >= $m3) {
            return 4;
        }
        if ($amount >= $m2) {
            return 3;
        }
        if ($amount >= $m1) {
            return 2;
        }

        return 1;
    }
}
