<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CustomerTagRepository;
use App\Services\ActivityLogger;

final class CustomerTagController
{
    public function index(): void
    {
        View::render('customers/tags', [
            'tags' => (new CustomerTagRepository())->all(),
        ]);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
        $repository = new CustomerTagRepository();

        if ($name === '') {
            Flash::set('danger', 'نام برچسب الزامی است.');
        } elseif ($repository->exists($name)) {
            Flash::set('danger', 'این برچسب از قبل وجود دارد.');
        } else {
            $repository->create($name);
            (new ActivityLogger())->log('settings_update', 'برچسب مشتری اضافه شد: ' . $name);
            Flash::set('success', 'برچسب اضافه شد.');
        }

        \redirect('/customers/tags');
    }

    public function update(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
        $repository = new CustomerTagRepository();

        if ($id <= 0 || $name === '') {
            Flash::set('danger', 'داده‌های برچسب معتبر نیست.');
        } elseif ($repository->exists($name, $id)) {
            Flash::set('danger', 'این نام برچسب قبلاً استفاده شده است.');
        } else {
            $repository->rename($id, $name);
            Flash::set('success', 'برچسب ویرایش شد.');
        }

        \redirect('/customers/tags');
    }

    public function delete(): void
    {
        Csrf::requireValid();
        (new CustomerTagRepository())->delete((int) ($_POST['id'] ?? 0));
        Flash::set('success', 'برچسب حذف شد.');
        \redirect('/customers/tags');
    }
}
