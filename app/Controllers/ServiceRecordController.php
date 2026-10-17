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

    public function show(): void
    {
        $service = (new ServiceRecordRepository())->find((int) ($_GET['id'] ?? 0));
        if (!$service) {
            Flash::set('danger', 'سرویس یافت نشد.');
            \redirect('/services');
        }
        View::render('services/show', [
            'service' => $service,
        ]);
    }

    public function create(): void
    {
        View::render('services/create', [
            'service' => [],
            'selectedCustomer' => $this->selectedCustomer(),
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
                'service' => $_POST,
                'selectedCustomer' => $this->selectedCustomer(),
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

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $service = (new ServiceRecordRepository())->find($id);
        if (!$service) {
            Flash::set('danger', 'سرویس یافت نشد.');
            \redirect('/services');
        }
        View::render('services/edit', [
            'service' => $service,
            'selectedCustomer' => (new CustomerRepository())->find((int) $service['customer_id']),
            'technicians' => (new UserRepository())->activeOperatorsAndAdmins(),
            'serviceTypes' => ServiceRecordService::serviceTypeOptions(),
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $result = (new ServiceRecordService())->update($id, $_POST);
        if (!$result['ok']) {
            $service = (new ServiceRecordRepository())->find($id) ?? ['id' => $id];
            View::render('services/edit', [
                'service' => array_merge($service, $_POST),
                'selectedCustomer' => (new CustomerRepository())->find((int) ($_POST['customer_id'] ?? 0)),
                'technicians' => (new UserRepository())->activeOperatorsAndAdmins(),
                'serviceTypes' => ServiceRecordService::serviceTypeOptions(),
                'errors' => $result['errors'],
            ]);
            return;
        }
        Flash::set('success', 'سرویس با موفقیت ویرایش شد.');
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        if ($customerId > 0) {
            \redirect('/customers/show?id=' . $customerId);
        }
        \redirect('/services');
    }

    private function selectedCustomer(): ?array
    {
        $customerId = (int) ($_POST['customer_id'] ?? $_GET['customer_id'] ?? 0);
        return $customerId > 0 ? (new CustomerRepository())->find($customerId) : null;
    }
}
