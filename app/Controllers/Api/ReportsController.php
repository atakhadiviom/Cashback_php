<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ClubReportRepository;
use App\Repositories\ReportRepository;
use App\Repositories\SegmentRepository;

final class ReportsController extends ApiController
{
    public function summary(array $params = []): void
    {
        $reports = new ReportRepository();
        $club = new ClubReportRepository();

        $this->ok([
            'dashboard' => $reports->dashboard(),
            'outstanding_liability' => $reports->outstandingLiability(),
            'club' => $club->dashboard(),
        ]);
    }

    public function customers(array $params = []): void
    {
        [$from, $to] = $this->range();
        $filters = [
            'date_from' => $from,
            'date_to' => $to,
            'tier_id' => (string) ($_GET['tier_id'] ?? ''),
            'segment' => (string) ($_GET['segment'] ?? ''),
            'tag_id' => (string) ($_GET['tag_id'] ?? ''),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'min_total' => (string) ($_GET['min_total'] ?? ''),
        ];
        [$limit] = $this->paging();
        $repository = new ClubReportRepository();

        $items = [];
        foreach ($repository->purchaseReport($filters, $limit) as $row) {
            $items[] = [
                'customer_id' => (int) $row['id'],
                'customer_name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'phone_number' => $row['phone_number'],
                'tier' => $row['tier_name'] ?? null,
                'segment' => $row['segment_code'] ?? null,
                'purchases' => (int) $row['purchases'],
                'total' => (float) $row['total'],
                'cashback' => (float) $row['cashback'],
                'average' => (float) $row['average'],
                'last_purchase_at' => $row['last_purchase'],
            ];
        }

        $this->ok([
            'from' => $from,
            'to' => $to,
            'summary' => $repository->purchaseSummary($filters),
            'items' => $items,
            'total' => count($items),
        ]);
    }

    public function cashback(array $params = []): void
    {
        [$from, $to] = $this->range();
        $reports = new ReportRepository();
        $club = new ClubReportRepository();

        $this->ok([
            'from' => $from,
            'to' => $to,
            'issued_vs_redeemed' => $reports->cashbackIssuedVsRedeemed($from, $to),
            'liability' => $club->liabilityTotals(),
            'daily' => $club->earnRedeem($from, $to, (($_GET['group'] ?? 'day') === 'month') ? 'month' : 'day'),
        ]);
    }

    public function liability(array $params = []): void
    {
        $filters = [
            'segment' => (string) ($_GET['segment'] ?? ''),
            'tier_id' => (string) ($_GET['tier_id'] ?? ''),
        ];
        [$limit] = $this->paging();
        $repository = new ClubReportRepository();

        $items = [];
        foreach ($repository->liability($filters, $limit) as $row) {
            $items[] = [
                'customer_id' => (int) $row['id'],
                'customer_name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'wallet_balance' => (float) $row['wallet_balance'],
                'next_expiry' => $row['next_expiry'],
                'expiring_30d' => (float) $row['bucket_30'],
                'expiring_90d' => (float) $row['bucket_90'],
                'later' => (float) $row['bucket_later'],
                'never' => (float) $row['bucket_never'],
            ];
        }

        $this->ok([
            'totals' => $repository->liabilityTotals(),
            'items' => $items,
            'total' => count($items),
            'segments' => (new SegmentRepository())->catalog(),
        ]);
    }

    /** @return array{0: string, 1: string} */
    private function range(): array
    {
        $from = $this->date((string) ($_GET['date_from'] ?? ''));
        $to = $this->date((string) ($_GET['date_to'] ?? ''));

        return [$from !== '' ? $from : date('Y-m-01'), $to !== '' ? $to : date('Y-m-d')];
    }
}
