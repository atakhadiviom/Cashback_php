<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CustomerRepository;
use App\Services\WalletService;

final class WalletController
{
    public function reduce(): void
    {
        $customer = (new CustomerRepository())->find((int) ($_GET['customer_id'] ?? 0));
        if (!$customer) {
            Flash::set('danger', 'مشتری یافت نشد.');
            \redirect('/customers');
        }
        View::render('wallet/reduce', ['customer' => $customer, 'errors' => []]);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $result = (new WalletService())->reduce($customerId, $_POST['amount'] ?? 0, trim((string) ($_POST['reason'] ?? '')));
        if (!$result['ok']) {
            View::render('wallet/reduce', [
                'customer' => (new CustomerRepository())->find($customerId),
                'errors' => $result['errors'],
            ]);
            return;
        }
        Flash::set('success', 'کسر از کیف پول با موفقیت ثبت شد.');
        \redirect('/customers/show?id=' . $customerId);
    }
}
