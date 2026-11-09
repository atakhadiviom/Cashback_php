<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\ContractRenewalRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\UserRepository;
use App\Services\ContractRenewalService;

final class ContractRenewalController
{
    public function index(): void
    {
        $filters = $_GET;
        View::render('contract_renewals/index', [
            'filters' => $filters,
            'renewals' => (new ContractRenewalRepository())->search($filters),
            'operators' => (new UserRepository())->activeOperatorsAndAdmins(),
        ]);
    }

    public function create(): void
    {
        View::render('contract_renewals/create', $this->formData([], []));
    }

    public function store(): void
    {
        Csrf::requireValid();
        $result = (new ContractRenewalService())->create($_POST);
        if (!$result['ok']) {
            View::render('contract_renewals/create', $this->formData($_POST, $result['errors']));
            return;
        }
        Flash::set('success', 'تمدید قرارداد ثبت شد. یادآوری پیامکی ۵ روز قبل از تاریخ پایان جدید ارسال می‌شود.');
        \redirect('/contract-renewals');
    }

    private function formData(array $renewal, array $errors): array
    {
        $customerId = (int) ($renewal['customer_id'] ?? $_GET['customer_id'] ?? 0);
        return [
            'renewal' => $renewal,
            'errors' => $errors,
            'selectedCustomer' => $customerId > 0 ? (new CustomerRepository())->find($customerId) : null,
        ];
    }
}
