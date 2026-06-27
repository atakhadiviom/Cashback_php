<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Jalali;
use App\Core\View;
use App\Repositories\CustomerRepository;
use App\Repositories\DueDateRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;
use App\Services\DueDateService;

final class DueDateController
{
    public function index(): void
    {
        $filters = $_GET;
        View::render('due_dates/index', [
            'filters' => $filters,
            'dueDates' => (new DueDateRepository())->search($filters),
            'operators' => (new UserRepository())->activeOperatorsAndAdmins(),
            'dueTypes' => DueDateService::dueTypeOptions(),
            'statuses' => DueDateService::statusOptions(),
        ]);
    }

    public function create(): void
    {
        View::render('due_dates/create', $this->formData([], []));
    }

    public function store(): void
    {
        Csrf::requireValid();
        $result = (new DueDateService())->create($_POST);
        if (!$result['ok']) {
            View::render('due_dates/create', $this->formData($_POST, $result['errors']));
            return;
        }
        Flash::set('success', 'سررسید با موفقیت ثبت شد.');
        \redirect('/due-dates');
    }

    public function edit(): void
    {
        $dueDate = (new DueDateRepository())->find((int) ($_GET['id'] ?? 0));
        if (!$dueDate) {
            Flash::set('danger', 'سررسید یافت نشد.');
            \redirect('/due-dates');
        }
        View::render('due_dates/edit', $this->formData($dueDate, []));
    }

    public function update(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $result = (new DueDateService())->update($id, $_POST);
        if (!$result['ok']) {
            View::render('due_dates/edit', $this->formData(array_merge($_POST, ['id' => $id]), $result['errors']));
            return;
        }
        Flash::set('success', 'سررسید ویرایش شد.');
        \redirect('/due-dates');
    }

    public function delete(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $result = (new DueDateService())->delete($id);
        if (!$result['ok']) {
            Flash::set('danger', $result['errors']['id'] ?? 'حذف سررسید ممکن نشد.');
        } else {
            Flash::set('success', 'سررسید حذف شد.');
        }
        \redirect('/due-dates');
    }

    public function export(): void
    {
        $rows = (new DueDateRepository())->search($_GET, 10000);
        (new ActivityLogger())->log('report_export', 'خروجی CSV سررسیدها دریافت شد.');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="due-dates.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['مشتری', 'شرکت', 'نوع', 'مبلغ', 'تاریخ سررسید', 'شماره مرجع', 'وضعیت', 'اپراتور', 'تاریخ ثبت', 'توضیحات']);
        foreach ($rows as $row) {
            fputcsv($out, [
                trim($row['first_name'] . ' ' . $row['last_name']),
                $row['company'] ?? '',
                DueDateService::dueTypeLabel($row['due_type']),
                $row['amount'],
                Jalali::formatDate($row['due_date']),
                $row['reference_number'] ?? '',
                DueDateService::statusLabel($row['status']),
                $row['operator_name'],
                $row['created_at'],
                $row['description'] ?? '',
            ]);
        }
        exit;
    }

    public function print(): void
    {
        View::render('due_dates/print', [
            'dueDates' => (new DueDateRepository())->search($_GET, 10000),
            'filters' => $_GET,
        ], 'blank');
    }

    /** @return array<string, mixed> */
    private function formData(array $dueDate, array $errors): array
    {
        $customerId = (int) ($dueDate['customer_id'] ?? $_GET['customer_id'] ?? 0);
        return [
            'dueDate' => $dueDate,
            'errors' => $errors,
            'customers' => (new CustomerRepository())->search([], 1000),
            'operators' => (new UserRepository())->activeOperatorsAndAdmins(),
            'dueTypes' => DueDateService::dueTypeOptions(),
            'statuses' => DueDateService::statusOptions(),
            'invoices' => (new PurchaseRepository())->searchForInvoicePicker($customerId > 0 ? $customerId : null),
        ];
    }
}
