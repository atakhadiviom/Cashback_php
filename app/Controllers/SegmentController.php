<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\SegmentRepository;
use App\Services\ActivityLogger;
use App\Services\SegmentService;

final class SegmentController
{
    public function index(): void
    {
        $segments = new SegmentRepository();
        $service = new SegmentService();

        View::render('club/segments', [
            'catalog' => $segments->catalog(),
            'distribution' => $segments->distribution(),
            'thresholds' => $service->thresholds(),
            'lastComputedAt' => $segments->lastComputedAt(),
        ]);
    }

    public function refresh(): void
    {
        Csrf::requireValid();
        $result = (new SegmentService())->refreshAll();
        (new ActivityLogger())->log(
            'settings_update',
            'بخش‌بندی مشتریان بازمحاسبه شد: ' . $result['saved'] . ' مشتری.'
        );
        Flash::set(
            'success',
            'بخش‌بندی انجام شد: ' . $result['saved'] . ' مشتری در ' . count((new SegmentRepository())->catalog()) . ' بخش طبقه‌بندی شدند.'
        );
        \redirect('/club/segments');
    }

    /** JSON thresholds for the settings form "suggest from data" button. */
    public function suggest(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $suggested = (new SegmentService())->suggestThresholds();
        echo json_encode($suggested, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
