<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CustomerRepository;
use App\Services\PurchaseService;

final class PurchaseController
{
    public function create(): void
    {
        $filters = $this->customerSearchFilters();
        View::render('purchases/create', [
            'customers' => (new CustomerRepository())->search($filters, 1000),
            'filters' => $filters,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $result = (new PurchaseService())->create((int) ($_POST['customer_id'] ?? 0), $_POST['amount'] ?? 0);
        if (!$result['ok']) {
            $filters = $this->customerSearchFilters();
            View::render('purchases/create', [
                'customers' => (new CustomerRepository())->search($filters, 1000),
                'filters' => $filters,
                'errors' => $result['errors'],
            ]);
            return;
        }
        Flash::set('success', 'خرید ثبت شد و مبلغ ' . \money($result['cashback']) . ' ریال کش‌بک به کیف پول مشتری اضافه شد.');
        \redirect('/customers/show?id=' . (int) $_POST['customer_id']);
    }

    /** @return array<string, string> */
    private function customerSearchFilters(): array
    {
        $q = trim((string) ($_GET['q'] ?? $_POST['search_q'] ?? ''));

        return $q !== '' ? ['q' => $q] : [];
    }
}
