<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CustomerTagRepository;
use App\Repositories\FestivalRepository;
use App\Repositories\SegmentRepository;
use App\Repositories\TierRepository;
use App\Services\ActivityLogger;
use App\Services\FestivalService;

final class FestivalController
{
    public function index(): void
    {
        View::render('admin/festivals', $this->pageData());
    }

    public function store(): void
    {
        Csrf::requireValid();
        $sendNow = !empty($_POST['send_now']);
        $result = (new FestivalService())->store($_POST, $sendNow);

        if (!$result['ok']) {
            View::render('admin/festivals', $this->pageData($_POST, $result['errors']));
            return;
        }

        (new ActivityLogger())->log('settings_update', 'جشنواره ایجاد شد: ' . trim((string) ($_POST['name'] ?? '')));
        Flash::set('success', $sendNow ? 'جشنواره ایجاد و ارسال شروع شد.' : 'جشنواره ذخیره شد.');
        \redirect('/admin/festivals');
    }

    public function send(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $dryRun = (string) ($_POST['mode'] ?? 'preview') === 'preview';
        $result = (new FestivalService())->send($id, $dryRun, 500);

        if (!$result['ok']) {
            Flash::set('danger', implode(' ', array_values($result['errors'])));
        } elseif ($dryRun) {
            Flash::set('info', 'پیش‌نمایش: ' . $result['sent'] . ' گیرنده آماده ارسال است (بدون ارسال واقعی).');
        } else {
            Flash::set(
                'success',
                'ارسال انجام شد: ' . $result['sent'] . ' پیامک' .
                ($result['skipped'] > 0 ? '، ' . $result['skipped'] . ' رد شده' : '') .
                ($result['failed'] > 0 ? '، ' . $result['failed'] . ' ناموفق' : '') .
                ($result['remaining'] > 0 ? '، ' . $result['remaining'] . ' باقی‌مانده' : '')
            );
        }

        \redirect('/admin/festivals');
    }

    public function cancel(): void
    {
        Csrf::requireValid();
        (new FestivalService())->cancel((int) ($_POST['id'] ?? 0));
        Flash::set('success', 'جشنواره لغو شد.');
        \redirect('/admin/festivals');
    }

    public function delete(): void
    {
        Csrf::requireValid();
        (new FestivalService())->delete((int) ($_POST['id'] ?? 0));
        Flash::set('success', 'جشنواره حذف شد.');
        \redirect('/admin/festivals');
    }

    /** JSON: how many customers the chosen audience covers. */
    public function preview(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $count = (new FestivalService())->preview(
            (string) ($_GET['audience'] ?? 'club_members'),
            (string) ($_GET['audience_value'] ?? '')
        );
        echo json_encode(['ok' => true, 'count' => $count], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     * @return array<string, mixed>
     */
    private function pageData(array $input = [], array $errors = []): array
    {
        return [
            'festivals' => (new FestivalRepository())->all(),
            'audiences' => FestivalRepository::AUDIENCES,
            'segments' => (new SegmentRepository())->catalog(),
            'tiers' => (new TierRepository())->all(),
            'tags' => (new CustomerTagRepository())->all(),
            'input' => $input,
            'errors' => $errors,
        ];
    }
}
