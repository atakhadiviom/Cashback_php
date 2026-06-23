<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\ReminderRepository;
use App\Repositories\UserRepository;
use App\Services\ReminderService;

final class ReminderController
{
    public function index(): void
    {
        $filters = $_GET;
        View::render('reminders/index', [
            'filters' => $filters,
            'reminders' => (new ReminderRepository())->search($filters),
            'operators' => (new UserRepository())->activeOperatorsAndAdmins(),
        ]);
    }

    public function markSeen(): void
    {
        Csrf::requireValid();
        $reminder = (new ReminderService())->markSeen((int) ($_POST['id'] ?? 0));
        Flash::set($reminder ? 'success' : 'danger', $reminder ? 'یادآوری به‌عنوان دیده‌شده ثبت شد.' : 'یادآوری یافت نشد.');
        \redirect($_SERVER['HTTP_REFERER'] ?? '/reminders');
    }

    public function markDone(): void
    {
        Csrf::requireValid();
        $reminder = (new ReminderService())->markDone((int) ($_POST['id'] ?? 0));
        Flash::set($reminder ? 'success' : 'danger', $reminder ? 'یادآوری انجام شد.' : 'یادآوری یافت نشد.');
        \redirect($_SERVER['HTTP_REFERER'] ?? '/reminders');
    }
}
