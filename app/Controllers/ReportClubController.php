<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Jalali;
use App\Core\View;
use App\Repositories\ClubReminderRepository;
use App\Repositories\ClubReportRepository;
use App\Repositories\SegmentRepository;
use App\Repositories\TierHistoryRepository;
use App\Repositories\TierRepository;
use App\Services\FollowupService;
use App\Services\LeadScorer;
use App\Services\ReminderSchedule;

final class ReportClubController
{
    public function purchases(): void
    {
        [$from, $to] = $this->dateRange();
        $filters = $this->filters($from, $to);
        $repo = new ClubReportRepository();

        View::render('reports/club_purchases', [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'rows' => $repo->purchaseReport($filters),
            'summary' => $repo->purchaseSummary($filters),
            'tiers' => (new TierRepository())->all(),
            'segments' => (new SegmentRepository())->catalog(),
        ]);
    }

    public function tiers(): void
    {
        [$from, $to] = $this->dateRange();
        $repo = new ClubReportRepository();

        View::render('reports/club_tiers', [
            'from' => $from,
            'to' => $to,
            'distribution' => $repo->tierDistribution(),
            'tierless' => $repo->tierlessCustomers(),
            'movements' => (new TierHistoryRepository())->search([
                'date_from' => $from,
                'date_to' => $to,
            ], 300),
        ]);
    }

    public function liability(): void
    {
        $filters = [
            'segment' => (string) ($_GET['segment'] ?? ''),
            'tier_id' => (string) ($_GET['tier_id'] ?? ''),
        ];
        $repo = new ClubReportRepository();

        View::render('reports/club_liability', [
            'filters' => $filters,
            'rows' => $repo->liability($filters),
            'totals' => $repo->liabilityTotals(),
            'tiers' => (new TierRepository())->all(),
            'segments' => (new SegmentRepository())->catalog(),
        ]);
    }

    public function earnRedeem(): void
    {
        [$from, $to] = $this->dateRange();
        $groupBy = (string) ($_GET['group'] ?? 'day') === 'month' ? 'month' : 'day';
        $repo = new ClubReportRepository();
        $rows = $repo->earnRedeem($from, $to, $groupBy);

        $totals = ['earned' => 0.0, 'redeemed' => 0.0, 'expired' => 0.0, 'reversed' => 0.0];
        foreach ($rows as $row) {
            foreach ($totals as $key => $value) {
                $totals[$key] = $value + (float) $row[$key];
            }
        }

        View::render('reports/club_earn_redeem', [
            'from' => $from,
            'to' => $to,
            'groupBy' => $groupBy,
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    public function leads(): void
    {
        [$from, $to] = $this->dateRange();
        $repo = new ClubReportRepository();

        $funnel = [];
        foreach ($repo->leadFunnel($from, $to) as $row) {
            $funnel[] = [
                'status' => (string) $row['sales_status'],
                'label' => FollowupService::salesStatusLabel((string) $row['sales_status']),
                'leads' => (int) $row['leads'],
                'amount' => (float) $row['amount'],
                'average_score' => (float) $row['average_score'],
            ];
        }

        $sources = [];
        foreach ($repo->leadBySource($from, $to) as $row) {
            $won = (int) $row['won'];
            $leads = (int) $row['leads'];
            $sources[] = [
                'source' => (string) $row['lead_source'],
                'label' => LeadScorer::sourceLabel((string) $row['lead_source']),
                'leads' => $leads,
                'won' => $won,
                'lost' => (int) $row['lost'],
                'won_amount' => (float) $row['won_amount'],
                'average_score' => (float) $row['average_score'],
                'conversion' => $leads > 0 ? round($won / $leads * 100, 1) : 0.0,
            ];
        }

        View::render('reports/club_leads', [
            'from' => $from,
            'to' => $to,
            'funnel' => $funnel,
            'sources' => $sources,
            'cycle' => $repo->leadCycle(),
        ]);
    }

    public function reminders(): void
    {
        [$from, $to] = $this->dateRange();
        $repo = new ClubReportRepository();

        $rows = [];
        foreach ($repo->reminderEffectiveness($from, $to) as $row) {
            $sent = (int) $row['sent'];
            $returned = (int) $row['returned'];
            $rows[] = [
                'kind' => (string) $row['kind'],
                'label' => ReminderSchedule::kindLabel((string) $row['kind']),
                'sent' => $sent,
                'returned' => $returned,
                'rate' => $sent > 0 ? round($returned / $sent * 100, 1) : 0.0,
            ];
        }

        View::render('reports/club_reminders', [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'stats' => (new ClubReminderRepository())->stats(),
            'recent' => $repo->recentReminders(20),
        ]);
    }

    public function export(): void
    {
        $type = (string) ($_GET['type'] ?? 'purchases');
        [$from, $to] = $this->dateRange();
        $filters = $this->filters($from, $to);
        $repo = new ClubReportRepository();
        $filename = 'club-' . $type . '-' . $from . '-to-' . $to;

        $headers = [];
        $rows = [];
        switch ($type) {
            case 'tiers':
                $headers = ['سطح', 'درصد کش‌بک', 'تعداد مشتری', 'موجودی کیف پول'];
                foreach ($repo->tierDistribution() as $row) {
                    $rows[] = [$row['name'], $row['cashback_percent'], $row['customers'], $row['wallet']];
                }
                break;

            case 'liability':
                $headers = ['مشتری', 'موبایل', 'سطح', 'بخش', 'موجودی', 'تا ۳۰ روز', 'تا ۹۰ روز', 'بیشتر', 'بدون انقضا'];
                foreach ($repo->liability($filters) as $row) {
                    $rows[] = [
                        trim($row['first_name'] . ' ' . $row['last_name']),
                        $row['phone_number'],
                        $row['tier_name'] ?? '',
                        $row['segment_code'] ?? '',
                        $row['wallet_balance'],
                        $row['bucket_30'],
                        $row['bucket_90'],
                        $row['bucket_later'],
                        $row['bucket_never'],
                    ];
                }
                break;

            case 'earn-redeem':
                $headers = ['تاریخ', 'صادرشده', 'مصرف‌شده', 'منقضی', 'برگشتی'];
                foreach ($repo->earnRedeem($from, $to, 'day') as $row) {
                    $rows[] = [$row['bucket'], $row['earned'], $row['redeemed'], $row['expired'], $row['reversed']];
                }
                break;

            case 'leads':
                $headers = ['منبع', 'تعداد سرنخ', 'موفق', 'ناموفق', 'مبلغ موفق', 'میانگین امتیاز'];
                foreach ($repo->leadBySource($from, $to) as $row) {
                    $rows[] = [
                        LeadScorer::sourceLabel((string) $row['lead_source']),
                        $row['leads'],
                        $row['won'],
                        $row['lost'],
                        $row['won_amount'],
                        $row['average_score'],
                    ];
                }
                break;

            case 'reminders':
                $headers = ['نوع', 'ارسال‌شده', 'بازگشت مشتری'];
                foreach ($repo->reminderEffectiveness($from, $to) as $row) {
                    $rows[] = [
                        ReminderSchedule::kindLabel((string) $row['kind']),
                        $row['sent'],
                        $row['returned'],
                    ];
                }
                break;

            case 'purchases':
            default:
                $headers = ['مشتری', 'موبایل', 'سطح', 'بخش', 'تعداد خرید', 'مبلغ خرید', 'کش‌بک', 'میانگین', 'آخرین خرید'];
                foreach ($repo->purchaseReport($filters) as $row) {
                    $rows[] = [
                        trim($row['first_name'] . ' ' . $row['last_name']),
                        $row['phone_number'],
                        $row['tier_name'] ?? '',
                        $row['segment_code'] ?? '',
                        $row['purchases'],
                        $row['total'],
                        $row['cashback'],
                        $row['average'],
                        $row['last_purchase'] !== null ? Jalali::formatDate(substr((string) $row['last_purchase'], 0, 10)) : '',
                    ];
                }
                break;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_map(static fn ($value): string => (string) $value, $row));
        }
        fclose($out);
        exit;
    }

    /** @return array<string, mixed> */
    private function filters(string $from, string $to): array
    {
        return [
            'date_from' => $from,
            'date_to' => $to,
            'tier_id' => (string) ($_GET['tier_id'] ?? ''),
            'segment' => (string) ($_GET['segment'] ?? ''),
            'tag_id' => (string) ($_GET['tag_id'] ?? ''),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'min_total' => (string) ($_GET['min_total'] ?? ''),
        ];
    }

    /** @return array{0: string, 1: string} */
    private function dateRange(): array
    {
        $from = trim((string) ($_GET['date_from'] ?? ''));
        $to = trim((string) ($_GET['date_to'] ?? ''));

        if ($from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', \normalize_digits($from))) {
            $from = Jalali::parseInputToGregorian(\normalize_digits($from)) ?? '';
        }
        if ($to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', \normalize_digits($to))) {
            $to = Jalali::parseInputToGregorian(\normalize_digits($to)) ?? '';
        }
        if ($from === '') {
            $from = date('Y-m-01');
        }
        if ($to === '') {
            $to = date('Y-m-d');
        }

        return [$from, $to];
    }
}
