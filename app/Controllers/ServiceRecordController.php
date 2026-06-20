<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CustomerRepository;
use App\Repositories\ServiceRecordRepository;
use App\Repositories\UserRepository;
use App\Services\ServiceRecordService;

final class ServiceRecordController
{
    public function index(): void
    {
        $filters = $_GET;
        $repo = new ServiceRecordRepository();
        View::render('services/index', [
            'filters' => $filters,
            'services' => $repo->search($filters),
            'summary' => $repo->summary($filters),
            'byTechnician' => $repo->byTechnician($filters),
            'technicians' => (new UserRepository())->activeOperatorsAndAdmins(),
            'serviceTypes' => ServiceRecordService::serviceTypeOptions(),
        ]);
    }

    public function create(): void
    {
        View::render('services/create', [
            'customers' => (new CustomerRepository())->search([], 1000),
            'technicians' => (new UserRepository())->activeOperatorsAndAdmins(),
            'serviceTypes' => ServiceRecordService::serviceTypeOptions(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $result = (new ServiceRecordService())->create($_POST);
        if (!$result['ok']) {
            View::render('services/create', [
                'customers' => (new CustomerRepository())->search([], 1000),
                'technicians' => (new UserRepository())->activeOperatorsAndAdmins(),
                'serviceTypes' => ServiceRecordService::serviceTypeOptions(),
                'errors' => $result['errors'],
            ]);
            return;
        }
        Flash::set('success', 'سرویس با موفقیت ثبت شد.');
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        if ($customerId > 0) {
            \redirect('/customers/show?id=' . $customerId);
        }
        \redirect('/services');
    }
}
