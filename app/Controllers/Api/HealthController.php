<?php

declare(strict_types=1);

namespace App\Controllers\Api;

final class HealthController extends ApiController
{
    public function show(): void
    {
        $dbOk = true;
        try {
            \App\Core\Database::pdo()->query('SELECT 1');
        } catch (\Throwable) {
            $dbOk = false;
        }

        $this->json([
            'ok' => $dbOk,
            'app' => [
                'version' => \app_version(),
                'time' => \current_datetime(),
                'jalali_date' => \App\Core\Jalali::formatDate(date('Y-m-d')),
            ],
            'database' => $dbOk ? 'ok' : 'error',
        ], $dbOk ? 200 : 503);
    }
}
