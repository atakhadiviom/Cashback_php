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
        $data = array_merge($_POST, ['_file' => $_FILES['attachment_file'] ?? null]);
        $result = (new FollowupService())->create($data);
        if (!$result['ok']) {
            View::render('followups/create', $this->formData($data, $result['errors']));
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
        $data = array_merge($_POST, ['_file' => $_FILES['attachment_file'] ?? null]);
        $result = (new FollowupService())->update($id, $data);
        if (!$result['ok']) {
            View::render('followups/edit', $this->formData(array_merge($data, ['id' => $id]), $result['errors']));
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

    public function delete(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $result = (new FollowupService())->delete($id);
        if (!$result['ok']) {
            Flash::set('danger', 'پیگیری یافت نشد.');
            \redirect('/followups');
        }
        Flash::set('success', 'پیگیری فروش حذف شد.');
        if (($_POST['return_to'] ?? '') === 'customer') {
            \redirect('/customers/show?id=' . $result['customer_id']);
        }
        \redirect('/followups');
    }

    public function attachment(): void
    {
        $followup = (new FollowupRepository())->find((int) ($_GET['id'] ?? 0));
        $relative = (string) ($followup['attachment_path'] ?? '');
        $base = realpath(dirname(__DIR__, 2) . '/storage/followups');
        $path = $relative !== '' ? realpath(dirname(__DIR__, 2) . '/' . $relative) : false;
        if (!$base || !$path || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: application/octet-stream');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode(basename($path)));
        readfile($path);
    }

    private function formData(array $followup, array $errors): array
    {
        $customerId = (int) ($followup['customer_id'] ?? $_GET['customer_id'] ?? 0);
        return [
            'followup' => $followup,
            'errors' => $errors,
            'selectedCustomer' => $customerId > 0 ? (new CustomerRepository())->find($customerId) : null,
            'operators' => (new UserRepository())->activeOperatorsAndAdmins(),
            'statuses' => FollowupService::salesStatusOptions(),
        ];
    }
}
