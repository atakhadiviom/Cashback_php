<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\SmsRepository;

final class SmsController
{
    public function logs(): void
    {
        $filters = $_GET;
        View::render('sms/logs', ['logs' => (new SmsRepository())->search($filters), 'filters' => $filters]);
    }
}
