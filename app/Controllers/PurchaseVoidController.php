<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Services\PurchaseVoidService;

final class PurchaseVoidController
{
    public function store(): void
    {
        Csrf::requireValid();
        $result = (new PurchaseVoidService())->void(
            (int) ($_POST['purchase_id'] ?? 0),
            (string) ($_POST['reason'] ?? '')
        );
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        if (!$result['ok']) {
            Flash::set('danger', implode(' ', $result['errors']));
            \redirect('/customers/show?id=' . $customerId);
        }
        Flash::set('success', 'خرید با موفقیت ابطال شد.');
        \redirect('/customers/show?id=' . $customerId);
    }
}
