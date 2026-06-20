<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\ReportRepository;
use App\Services\CronRunnerService;

final class DashboardController
{
    public function index(): void
    {
        $cronMessages = [];
        if (Auth::isAdmin()) {
            $cronMessages = (new CronRunnerService())->maybeRunFromDashboard()['messages'];
        }

        $cron = new CronRunnerService();
        $webToken = trim((string) \config_value('cron.web_token', ''));
        View::render('dashboard/index', [
            'stats' => (new ReportRepository())->dashboard(),
            'cronMessages' => $cronMessages,
            'cronState' => $cron->state()->all(),
            'cronWebUrl' => $webToken !== '' ? \url('/internal/cron?task=all&token=' . rawurlencode($webToken)) : '',
        ]);
    }
}
