<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Services\CronRunnerService;

final class CronController
{
    public function run(): void
    {
        Csrf::requireValid();
        $task = trim((string) ($_POST['task'] ?? 'all'));
        $result = (new CronRunnerService())->runTask($task);
        $summary = $result['messages'] ? implode(' ', $result['messages']) : 'انجام شد.';
        Flash::set($result['ok'] ? 'success' : 'danger', $summary);
        \redirect('/dashboard');
    }
}
