<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Repositories\DueDateRepository;
use App\Repositories\ReminderRepository;
use App\Repositories\ReportRepository;

final class DashboardController
{
    public function index(): void
    {
        View::render('dashboard/index', [
            'stats' => (new ReportRepository())->dashboard(),
            'reminderStats' => (new ReminderRepository())->dashboardCounts(Auth::isAdmin() ? null : Auth::id()),
            'dueDateStats' => (new DueDateRepository())->dashboardStats(),
        ]);
    }
}
