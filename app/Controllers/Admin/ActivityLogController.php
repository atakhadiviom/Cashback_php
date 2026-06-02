<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\View;
use App\Repositories\ActivityLogRepository;
use App\Repositories\UserRepository;

final class ActivityLogController
{
    public function index(): void
    {
        View::render('admin/activity_logs', [
            'logs' => (new ActivityLogRepository())->search($_GET),
            'users' => (new UserRepository())->activeOperatorsAndAdmins(),
            'filters' => $_GET,
        ]);
    }
}
