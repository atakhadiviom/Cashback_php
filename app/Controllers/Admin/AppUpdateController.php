<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Services\AppUpdaterService;

final class AppUpdateController
{
    public function index(): void
    {
        $updater = new AppUpdaterService();
        View::render('admin/app_update', [
            'status' => $updater->status(),
            'result' => $_SESSION['app_update_result'] ?? null,
        ]);
        unset($_SESSION['app_update_result']);
    }

    public function update(): void
    {
        Csrf::requireValid();

        $updater = new AppUpdaterService();
        $result = $updater->updateFromMain(isset($_POST['run_migrations']));
        $_SESSION['app_update_result'] = $result;

        Flash::set($result['ok'] ? 'success' : 'danger', $result['ok'] ? 'برنامه با موفقیت از GitHub به‌روزرسانی شد.' : 'به‌روزرسانی ناموفق بود.');
        \redirect('/admin/app-update');
    }
}
