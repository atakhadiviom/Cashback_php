<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Jalali;
use App\Core\View;
use App\Repositories\CustomerRepository;
use App\Repositories\FollowupRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\ServiceRecordRepository;
use App\Repositories\TierRepository;
use App\Repositories\WalletRepository;
use App\Services\ActivityLogger;
use App\Services\CustomerService;
use App\Services\DataAccessControl;

final class CustomerController
{
    public function index(): void
    {
        $filters = $_GET;
        $customers = (new CustomerRepository())->search($filters);
        View::render('customers/index', compact('customers', 'filters'));
    }

    public function create(): void
    {
        View::render('customers/create', ['customer' => [], 'errors' => []]);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $result = (new CustomerService())->create($_POST);
        if (!$result['ok']) {
            View::render('customers/create', ['customer' => $_POST, 'errors' => $result['errors']]);
            return;
        }
        Flash::set('success', 'مشتری با موفقیت ثبت شد.');
        \redirect('/customers/show?id=' . $result['id']);
    }

    public function edit(): void
    {
        $customer = (new CustomerRepository())->find((int) ($_GET['id'] ?? 0));
        if (!$customer) {
            Flash::set('danger', 'مشتری یافت نشد.');
            \redirect('/customers');
        }
        View::render('customers/edit', ['customer' => $customer, 'errors' => []]);
    }

    public function update(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $existing = (new CustomerRepository())->find($id);
        if (!$existing || !DataAccessControl::canModifyOwner((int) $existing['created_by'])) {
            Flash::set('danger', 'شما مجوز ویرایش این رکورد را ندارید.');
            \redirect('/customers');
        }
        $result = (new CustomerService())->update($id, $_POST);
        if (!$result['ok']) {
            View::render('customers/edit', ['customer' => array_merge($_POST, ['id' => $id]), 'errors' => $result['errors']]);
            return;
        }
        Flash::set('success', 'اطلاعات مشتری ویرایش شد.');
        \redirect('/customers/show?id=' . $id);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $customers = new CustomerRepository();
        $purchases = new PurchaseRepository();
        $customer = $customers->find($id);
        if (!$customer) {
            Flash::set('danger', 'مشتری یافت نشد.');
            \redirect('/customers');
        }
        $tier = $customer['tier_id'] ? (new TierRepository())->find((int) $customer['tier_id']) : null;
        $customer['tier_name'] = $tier['name'] ?? null;

        View::render('customers/show', [
            'customer' => $customer,
            'purchases' => $purchases->forCustomer($id),
            'followups' => (new FollowupRepository())->forCustomer($id),
            'services' => (new ServiceRecordRepository())->forCustomer($id),
            'walletTransactions' => (new WalletRepository())->forCustomer($id),
            'lifetimeEarned' => $purchases->lifetimeCashbackEarned($id),
            'canVoid' => Auth::can('void_purchase'),
        ]);
    }

    public function export(): void
    {
        $customers = (new CustomerRepository())->search($_GET, 10000);
        (new ActivityLogger())->log('report_export', 'خروجی CSV مشتریان دریافت شد.');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="customers.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['نام', 'نام خانوادگی', 'کد ملی', 'شماره قرارداد', 'موبایل', 'تولد', 'شروع قرارداد', 'پایان قرارداد', 'موجودی کیف پول', 'تاریخ ایجاد']);
        foreach ($customers as $customer) {
            fputcsv($out, [
                $customer['first_name'],
                $customer['last_name'],
                $customer['national_code'],
                $customer['contract_number'] ?? '',
                $customer['phone_number'],
                Jalali::formatDate($customer['birthday']),
                Jalali::formatDate($customer['contract_starts_at'] ?? null),
                Jalali::formatDate($customer['contract_ends_at'] ?? null),
                $customer['wallet_balance'],
                $customer['created_at'],
            ]);
        }
        exit;
    }

    public function delete(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $existing = (new CustomerRepository())->find($id);
        if (!$existing || !DataAccessControl::canModifyOwner((int) $existing['created_by'])) {
            Flash::set('danger', 'شما مجوز حذف این رکورد را ندارید.');
            \redirect('/customers');
        }
        (new CustomerRepository())->softDelete($id);
        (new ActivityLogger())->log('customer_delete', 'مشتری حذف نرم شد.', $id);
        Flash::set('success', 'مشتری حذف شد.');
        \redirect('/customers');
    }

    public function anonymize(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $existing = (new CustomerRepository())->find($id);
        if (!$existing || !DataAccessControl::canModifyOwner((int) $existing['created_by'])) {
            Flash::set('danger', 'شما مجوز حذف این رکورد را ندارید.');
            \redirect('/customers');
        }
        (new CustomerRepository())->anonymize($id);
        (new ActivityLogger())->log('customer_anonymize', 'مشتری ناشناس‌سازی شد.', $id);
        Flash::set('success', 'اطلاعات شخصی مشتری حذف شد.');
        \redirect('/customers');
    }
}
