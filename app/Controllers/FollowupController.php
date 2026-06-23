<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CustomerRepository;
use App\Repositories\FollowupRepository;
use App\Repositories\UserRepository;
use App\Services\FollowupService;

final class FollowupController
{
    public function index(): void
    {
        $filters = $_GET;
        View::render('followups/index', [
            'filters' => $filters,
            'followups' => (new FollowupRepository())->search($filters),
            'operators' => (new UserRepository())->activeOperatorsAndAdmins(),
            'statuses' => FollowupService::salesStatusOptions(),
        ]);
    }

    public function create(): void
    {
        View::render('followups/create', $this->formData([], []));
    }

    public function store(): void
    {
        Csrf::requireValid();
        $result = (new FollowupService())->create($_POST);
        if (!$result['ok']) {
            View::render('followups/create', $this->formData($_POST, $result['errors']));
            return;
        }
        Flash::set('success', 'پیگیری فروش با موفقیت ثبت شد.');
        \redirect('/customers/show?id=' . $result['customer_id']);
    }

    public function edit(): void
    {
        $followup = (new FollowupRepository())->find((int) ($_GET['id'] ?? 0));
        if (!$followup) {
            Flash::set('danger', 'پیگیری یافت نشد.');
            \redirect('/followups');
        }
        View::render('followups/edit', $this->formData($followup, []));
    }

    public function update(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $result = (new FollowupService())->update($id, $_POST);
        if (!$result['ok']) {
            View::render('followups/edit', $this->formData(array_merge($_POST, ['id' => $id]), $result['errors']));
            return;
        }
        Flash::set('success', 'پیگیری فروش ویرایش شد.');
        \redirect('/customers/show?id=' . $result['customer_id']);
    }

    public function show(): void
    {
        $followup = (new FollowupRepository())->find((int) ($_GET['id'] ?? 0));
        if (!$followup) {
            Flash::set('danger', 'پیگیری یافت نشد.');
            \redirect('/followups');
        }
        View::render('followups/show', [
            'followup' => $followup,
            'statuses' => FollowupService::salesStatusOptions(),
        ]);
    }

    private function formData(array $followup, array $errors): array
    {
        return [
            'followup' => $followup,
            'errors' => $errors,
            'customers' => (new CustomerRepository())->search([], 1000),
            'operators' => (new UserRepository())->activeOperatorsAndAdmins(),
            'statuses' => FollowupService::salesStatusOptions(),
        ];
    }
}
