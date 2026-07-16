<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\CustomerRepository;
use App\Services\PurchaseService;

final class PurchaseController
{
    public function create(): void
    {
        View::render('purchases/create', [
            'selectedCustomer' => $this->selectedCustomer(),
            'errors' => [],
            'settings' => (new CashbackSettingsRepository())->settings(),
        ]);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $result = (new PurchaseService())->create((int) ($_POST['customer_id'] ?? 0), $_POST['amount'] ?? 0, [
            'invoice_ref' => (string) ($_POST['invoice_ref'] ?? ''),
            'confirm_duplicate' => isset($_POST['confirm_duplicate']),
        ]);
        if (!$result['ok']) {
            View::render('purchases/create', [
                'selectedCustomer' => $this->selectedCustomer(),
                'errors' => $result['errors'],
                'needs_confirm' => !empty($result['needs_confirm']),
                'settings' => (new CashbackSettingsRepository())->settings(),
            ]);
            return;
        }
        Flash::set('success', 'خرید ثبت شد و مبلغ ' . \money($result['cashback']) . ' ریال کش‌بک (نرخ ' . $result['percent_applied'] . '٪) به کیف پول مشتری اضافه شد.');
        \redirect('/customers/show?id=' . (int) $_POST['customer_id']);
    }

    private function selectedCustomer(): ?array
    {
        $customerId = (int) ($_POST['customer_id'] ?? $_GET['customer_id'] ?? 0);
        return $customerId > 0 ? (new CustomerRepository())->find($customerId) : null;
    }
}
