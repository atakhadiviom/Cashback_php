<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Jalali;
use App\Core\View;
use App\Repositories\ReportRepository;
use App\Repositories\ServiceRecordRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;
use App\Services\ServiceRecordService;

final class ReportController
{
    public function index(): void
    {
        $repo = new ReportRepository();
        $serviceRepo = new ServiceRecordRepository();
        $filters = $_GET;
        $serviceFilters = [
            'date_from' => $filters['service_date_from'] ?? $filters['date_from'] ?? '',
            'date_to' => $filters['service_date_to'] ?? $filters['date_to'] ?? '',
            'technician_id' => $filters['service_technician_id'] ?? $filters['technician_id'] ?? '',
            'payment_status' => $filters['service_payment_status'] ?? $filters['payment_status'] ?? '',
            'service_type' => $filters['service_type'] ?? '',
            'q' => $filters['service_q'] ?? $filters['q'] ?? '',
            'contract_number' => $filters['service_contract_number'] ?? $filters['contract_number'] ?? '',
        ];
        $from = (string) ($filters['liability_from'] ?? date('Y-m-01'));
        $to = (string) ($filters['liability_to'] ?? date('Y-m-d'));
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
            'liability' => $repo->cashbackIssuedVsRedeemed($from, $to),
            'outstandingLiability' => $repo->outstandingLiability(),
            'inactiveCustomers' => $repo->inactiveCustomers((int) ($filters['inactive_days'] ?? 90)),
            'serviceFilters' => $serviceFilters,
            'serviceSummary' => $serviceRepo->summary($serviceFilters),
            'services' => $serviceRepo->search($serviceFilters, 100),
            'servicesByTechnician' => $serviceRepo->byTechnician($serviceFilters),
            'serviceTypes' => ServiceRecordService::serviceTypeOptions(),
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
        fputcsv($out, ['نام', 'کد ملی', 'موبایل', 'تاریخ تولد', 'مبلغ خرید', 'کش‌بک', 'وضعیت', 'اپراتور', 'تاریخ']);
        foreach ($rows as $row) {
            fputcsv($out, [
                trim($row['first_name'] . ' ' . $row['last_name']),
                $row['national_code'],
                $row['phone_number'],
                Jalali::formatDate($row['birthday']),
                $row['amount'],
                $row['cashback_amount'],
                $row['status'] ?? 'active',
                $row['created_by_name'],
                $row['created_at'],
            ]);
        }
        exit;
    }

    public function exportServices(): void
    {
        $filters = [
            'date_from' => $_GET['service_date_from'] ?? $_GET['date_from'] ?? '',
            'date_to' => $_GET['service_date_to'] ?? $_GET['date_to'] ?? '',
            'technician_id' => $_GET['service_technician_id'] ?? $_GET['technician_id'] ?? '',
            'payment_status' => $_GET['service_payment_status'] ?? $_GET['payment_status'] ?? '',
            'service_type' => $_GET['service_type'] ?? '',
            'q' => $_GET['service_q'] ?? $_GET['q'] ?? '',
            'contract_number' => $_GET['service_contract_number'] ?? $_GET['contract_number'] ?? '',
        ];
        $rows = (new ServiceRecordRepository())->search($filters, 10000);
        (new ActivityLogger())->log('report_export', 'خروجی CSV گزارش سرویس‌ها دریافت شد.');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="services-report.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['مشتری', 'شماره قرارداد', 'تکنسین', 'تاریخ سرویس', 'نوع سرویس', 'مبلغ پرداختی', 'وضعیت پرداخت', 'وضعیت پیامک']);
        foreach ($rows as $row) {
            fputcsv($out, [
                trim($row['first_name'] . ' ' . $row['last_name']),
                $row['contract_number'] ?? '',
                $row['technician_name'],
                Jalali::formatDate($row['service_date']),
                ServiceRecordService::serviceTypeLabel($row['service_type']),
                $row['paid_amount'],
                $row['payment_status'] === 'paid' ? 'paid' : 'unpaid',
                $row['sms_status'] ?? '',
            ]);
        }
        exit;
    }
}
