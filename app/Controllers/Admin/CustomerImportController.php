<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Jalali;
use App\Core\View;
use App\Repositories\CustomerRepository;
use App\Services\ActivityLogger;
use App\Services\CustomerService;

final class CustomerImportController
{
    public function form(): void
    {
        View::render('admin/customers_import', ['errors' => [], 'preview' => []]);
    }

    public function import(): void
    {
        Csrf::requireValid();
        if (empty($_FILES['csv']['tmp_name'])) {
            View::render('admin/customers_import', ['errors' => ['csv' => 'فایل CSV انتخاب نشود.'], 'preview' => []]);
            return;
        }

        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$handle) {
            View::render('admin/customers_import', ['errors' => ['csv' => 'خواندن فایل ممکن نیست.'], 'preview' => []]);
            return;
        }

        $header = fgetcsv($handle);
        $preview = [];
        $errors = [];
        $rowNum = 1;
        $service = new CustomerService();
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $data = [
                'first_name' => $row[0] ?? '',
                'last_name' => $row[1] ?? '',
                'national_code' => $row[2] ?? '',
                'phone_number' => $row[3] ?? '',
                'birthday' => $row[4] ?? '',
            ];
            if (!empty($_POST['preview_only'])) {
                $preview[] = ['row' => $rowNum, 'data' => $data];
                continue;
            }
            $result = $service->create($data);
            if (!$result['ok']) {
                $errors[] = "ردیف {$rowNum}: " . implode(', ', $result['errors']);
            } else {
                $imported++;
            }
        }
        fclose($handle);

        if (!empty($_POST['preview_only'])) {
            View::render('admin/customers_import', ['errors' => [], 'preview' => $preview]);
            return;
        }

        (new ActivityLogger())->log('customer_import', "واردات CSV: {$imported} مشتری");
        Flash::set('success', "{$imported} مشتری وارد شد." . ($errors ? ' برخی ردیف‌ها خطا داشتند.' : ''));
        \redirect('/customers');
    }
}
