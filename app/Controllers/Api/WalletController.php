<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CustomerRepository;
use App\Repositories\WalletRepository;
use App\Services\WalletService;

final class WalletController extends ApiController
{
    public function show(array $params): void
    {
        $customer = (new CustomerRepository())->find($params['id']);
        if (!$customer) {
            $this->fail('مشتری یافت نشد.', 404);
            return;
        }

        $this->ok([
            'customer_id' => (int) $customer['id'],
            'balance' => (float) $customer['wallet_balance'],
        ]);
    }

    public function transactions(array $params): void
    {
        $customer = (new CustomerRepository())->find($params['id']);
        if (!$customer) {
            $this->fail('مشتری یافت نشد.', 404);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 100);
        $rows = (new WalletRepository())->forCustomer((int) $customer['id'], max(1, min(500, $limit)));

        $this->ok([
            'customer_id' => (int) $customer['id'],
            'balance' => (float) $customer['wallet_balance'],
            'items' => $rows,
        ]);
    }

    public function reduce(array $params = []): void
    {
        $body = $this->body();
        $result = (new WalletService())->reduce(
            (int) ($body['customer_id'] ?? 0),
            $body['amount'] ?? 0,
            (string) ($body['reason'] ?? 'کسر از کیف پول'),
            [
                'purchase_id' => isset($body['purchase_id']) ? (int) $body['purchase_id'] : null,
                'related_purchase_amount' => (float) ($body['related_purchase_amount'] ?? 0),
            ]
        );

        if (!$result['ok']) {
            $this->fail('کسر از کیف پول انجام نشد.', 422, ['errors' => $result['errors']]);
            return;
        }

        $customer = (new CustomerRepository())->find((int) ($body['customer_id'] ?? 0));
        $this->ok(['balance' => $customer !== null ? (float) $customer['wallet_balance'] : null]);
    }

    public function credit(array $params = []): void
    {
        $body = $this->body();
        $result = (new WalletService())->credit(
            (int) ($body['customer_id'] ?? 0),
            $body['amount'] ?? 0,
            (string) ($body['reason'] ?? 'شارژ کیف پول')
        );

        if (!$result['ok']) {
            $this->fail('شارژ کیف پول انجام نشد.', 422, ['errors' => $result['errors']]);
            return;
        }

        $this->ok(['balance' => $result['balance'] ?? null]);
    }
}
