<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\ClubReportRepository;
use App\Repositories\ReportRepository;
use App\Repositories\SegmentRepository;

final class ClubDashboardController
{
    public function index(): void
    {
        $segments = new SegmentRepository();
        $reports = new ClubReportRepository();

        View::render('club/dashboard', [
            'club' => (new CashbackSettingsRepository())->clubOptions(),
            'kpi' => $reports->dashboard(),
            'tierDistribution' => $reports->tierDistribution(),
            'tierless' => $reports->tierlessCustomers(),
            'segmentCatalog' => $segments->catalog(),
            'segmentDistribution' => $segments->distribution(),
            'topBuyers' => $reports->topBuyers(10),
            'atRisk' => $reports->atRisk(10),
            'expiring' => $reports->expiringSoon(30, 10),
            'birthdays' => (new ReportRepository())->birthdays('month'),
            'recentReminders' => $reports->recentReminders(8),
            'lastSegmentAt' => $segments->lastComputedAt(),
        ]);
    }
}
