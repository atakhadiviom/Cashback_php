<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Pure lead scoring for the sales pipeline (customer_followups lead fields).
 * Higher is hotter; the result is always clamped to 0..100.
 */
final class LeadScorer
{
    public const SOURCES = [
        'walk_in' => 'حضوری',
        'referral' => 'معرفی مشتری',
        'site' => 'وب‌سایت / فرم',
        'instagram' => 'اینستاگرام',
        'phone' => 'تماس تلفنی',
        'campaign' => 'کمپین پیامکی',
        'other' => 'سایر',
    ];

    private const SOURCE_POINTS = [
        'referral' => 30,
        'site' => 25,
        'instagram' => 18,
        'phone' => 14,
        'walk_in' => 12,
        'campaign' => 10,
        'other' => 5,
    ];

    private const STAGE_POINTS = [
        'negotiating' => 15,
        'pre_invoice_sent' => 22,
        'waiting_customer' => 12,
        'callback' => 8,
        'won' => 30,
        'lost' => 0,
    ];

    public const STAGE_PROBABILITY = [
        'negotiating' => 40,
        'pre_invoice_sent' => 60,
        'waiting_customer' => 50,
        'callback' => 30,
        'won' => 100,
        'lost' => 0,
    ];

    /**
     * @param array<string, mixed> $lead followup_date, sales_status, lead_source,
     *                                   pre_invoice_amount, invoice_amount, expected_close_date
     */
    public function score(array $lead, int $followupCount = 1, ?string $today = null): int
    {
        $today = $today ?? date('Y-m-d');
        $source = (string) ($lead['lead_source'] ?? 'other');
        $stage = (string) ($lead['sales_status'] ?? 'negotiating');

        $score = self::SOURCE_POINTS[$source] ?? 5;
        $score += self::STAGE_POINTS[$stage] ?? 0;
        $score += $this->amountPoints((float) ($lead['invoice_amount'] ?? $lead['pre_invoice_amount'] ?? 0));
        $score += min(15, max(0, $followupCount) * 5);
        $score += $this->closeDatePoints((string) ($lead['expected_close_date'] ?? ''), $today);
        $score += $this->stalenessPenalty((string) ($lead['followup_date'] ?? ''), $today);

        return (int) max(0, min(100, round($score)));
    }

    public static function probabilityForStage(string $stage): int
    {
        return self::STAGE_PROBABILITY[$stage] ?? 30;
    }

    public static function sourceLabel(string $source): string
    {
        return self::SOURCES[$source] ?? $source;
    }

    private function amountPoints(float $amount): int
    {
        if ($amount >= 50000000) {
            return 20;
        }
        if ($amount >= 20000000) {
            return 15;
        }
        if ($amount >= 5000000) {
            return 8;
        }
        if ($amount > 0) {
            return 3;
        }

        return 0;
    }

    private function closeDatePoints(string $expectedClose, string $today): int
    {
        $close = strtotime(substr($expectedClose, 0, 10));
        $now = strtotime($today);
        if ($close === false || $now === false) {
            return 0;
        }
        $days = (int) round(($close - $now) / 86400);
        if ($days < 0) {
            return -5; // promised date already slipped
        }
        if ($days <= 7) {
            return 10;
        }
        if ($days <= 30) {
            return 5;
        }

        return 0;
    }

    private function stalenessPenalty(string $lastFollowup, string $today): int
    {
        $last = strtotime(substr($lastFollowup, 0, 10));
        $now = strtotime($today);
        if ($last === false || $now === false) {
            return 0;
        }
        $days = (int) round(($now - $last) / 86400);

        return $days > 30 ? -15 : 0;
    }
}
