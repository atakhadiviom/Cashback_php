<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Jalali;
use App\Core\View;
use App\Repositories\ReportRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;

final class ReportController
{
    public function index(): void
    {
        $repo = new ReportRepository();
        $filters = $_GET;
        View::render('reports/index', [
            'filters' => $filters,
            'summary' => $repo->summary($filters),
            'purchases' => $repo->purchases($filters),
            'topAmount' => $repo->topByAmount(),
            'topCashback' => $repo->topByCashback(),
            'birthdaysToday' => $repo->birthdays('today'),
            'birthdaysWeek' => $repo->birthdays('week'),
            'birthdaysMonth' => $repo->birthdays('month'),
            'users' => (new UserRepository())->activeOperatorsAndAdmins(),
        ]);
    }

    public function export(): void
    {
        $rows = (new ReportRepository())->purchases($_GET, 10000);
        (new ActivityLogger())->log('report_export', 'خروجی CSV گزارش‌ها دریافت شد.');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="report.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['نام', 'کد ملی', 'موبایل', 'تاریخ تولد', 'مبلغ خرید', 'کش‌بک', 'اپراتور', 'تاریخ']);
        foreach ($rows as $row) {
            fputcsv($out, [trim($row['first_name'] . ' ' . $row['last_name']), $row['national_code'], $row['phone_number'], Jalali::formatDate($row['birthday']), $row['amount'], $row['cashback_amount'], $row['created_by_name'], $row['created_at']]);
        }
        exit;
    }
}
