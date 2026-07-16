<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Repositories\DueDateRepository;
use App\Repositories\ReminderRepository;
use App\Repositories\ReportRepository;
use Throwable;

final class DashboardController
{
    public function index(): void
    {
        View::render('dashboard/index', [
            'stats' => (new ReportRepository())->dashboard(),
            'reminderStats' => (new ReminderRepository())->dashboardCounts(Auth::isAdmin() ? null : Auth::id()),
            'dueDateStats' => $this->dueDateStats(),
        ]);
    }

    /** @return array<string, int|float> */
    private function dueDateStats(): array
    {
        try {
            return (new DueDateRepository())->dashboardStats();
        } catch (Throwable) {
            return [
                'today_count' => 0,
                'tomorrow_count' => 0,
                'overdue_count' => 0,
                'today_amount' => 0.0,
                'pending_checks' => 0,
                'overdue_installments' => 0,
            ];
        }
    }
}
