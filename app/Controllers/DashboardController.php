<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\ReportRepository;

final class DashboardController
{
    public function index(): void
    {
        View::render('dashboard/index', [
            'stats' => (new ReportRepository())->dashboard(),
        ]);
    }
}
