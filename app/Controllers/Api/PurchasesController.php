<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CustomerRepository;
use App\Repositories\PurchaseRepository;
use App\Services\PurchaseService;
use App\Services\PurchaseVoidService;

final class PurchasesController extends ApiController
{
    public function index(array $params = []): void
    {
        $filters = [
            'customer_id' => (int) ($_GET['customer_id'] ?? 0),
            'status' => (string) ($_GET['status'] ?? ''),
            'invoice_ref' => trim((string) ($_GET['invoice_ref'] ?? '')),
            'date_from' => $this->date((string) ($_GET['date_from'] ?? '')),
            'date_to' => $this->date((string) ($_GET['date_to'] ?? '')),
        ];
        [$limit, $offset] = $this->paging();
        $result = (new PurchaseRepository())->apiSearch($filters, $limit, $offset);

        $items = [];
        foreach ($result['items'] as $row) {
            $resource = $this->purchaseResource($row);
            $resource['customer_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $resource['customer_phone'] = $row['phone_number'] ?? null;
            $items[] = $resource;
        }

        $this->ok([
            'items' => $items,
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function show(array $params): void
    {
        $purchase = (new PurchaseRepository())->find($params['id']);
        if (!$purchase) {
            $this->fail('خرید یافت نشد.', 404);
            return;
        }

        $this->ok(['purchase' => $this->purchaseResource($purchase)]);
    }

    public function create(array $params = []): void
    {
        $body = $this->body();
        $customerId = (int) ($body['customer_id'] ?? 0);
        if ($customerId <= 0 && !empty($body['phone_number'])) {
            $customer = (new CustomerRepository())->findByPhone(\normalize_digits((string) $body['phone_number']));
            $customerId = $customer !== null ? (int) $customer['id'] : 0;
        }

        $result = (new PurchaseService())->create($customerId, $body['amount'] ?? 0, [
            'invoice_ref' => (string) ($body['invoice_ref'] ?? ''),
            'idempotency_key' => trim((string) ($_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? ($body['idempotency_key'] ?? ''))),
            'confirm_duplicate' => !empty($body['confirm_duplicate']),
            'created_by' => \App\Services\SystemUserService::actorId(),
        ]);

        if (!$result['ok']) {
            $status = !empty($result['needs_confirm']) ? 409 : 422;
            $this->fail('ثبت خرید انجام نشد.', $status, ['errors' => $result['errors']]);
            return;
        }

        $this->ok([
            'purchase_id' => (int) $result['purchase_id'],
            'cashback' => (float) $result['cashback'],
            'percent_applied' => $result['percent_applied'] ?? null,
            'duplicate' => !empty($result['duplicate']),
        ], 201);
    }

    public function void(array $params): void
    {
        $reason = trim((string) ($this->body()['reason'] ?? ''));
        $result = (new PurchaseVoidService())->void($params['id'], $reason);
        if (!$result['ok']) {
            $this->fail('ابطال خرید انجام نشد.', 422, ['errors' => $result['errors']]);
            return;
        }

        $this->ok(['voided' => true, 'purchase_id' => $params['id']]);
    }
}
