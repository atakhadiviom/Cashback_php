<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CustomerRepository;
use App\Repositories\CustomerTagRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\SegmentRepository;
use App\Repositories\TierRepository;
use App\Repositories\WalletRepository;
use App\Services\CustomerService;

final class CustomersController extends ApiController
{
    public function index(array $params = []): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'first_name' => trim((string) ($_GET['first_name'] ?? '')),
            'last_name' => trim((string) ($_GET['last_name'] ?? '')),
            'company' => trim((string) ($_GET['company'] ?? '')),
            'phone_number' => trim((string) (\normalize_digits((string) ($_GET['phone_number'] ?? '')))),
            'national_code' => trim((string) ($_GET['national_code'] ?? '')),
            'tier_id' => (string) ($_GET['tier_id'] ?? ''),
            'segment' => (string) ($_GET['segment'] ?? ''),
            'tag_id' => (string) ($_GET['tag_id'] ?? ''),
        ];
        [$limit, $offset] = $this->paging();
        $result = (new CustomerRepository())->apiList($filters, $limit, $offset);

        $this->ok([
            'items' => array_map(fn (array $customer): array => $this->customerResource($customer), $result['items']),
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function show(array $params): void
    {
        $customer = (new CustomerRepository())->find($params['id']);
        if (!$customer || !empty($customer['deleted_at'])) {
            $this->fail('مشتری یافت نشد.', 404);
            return;
        }

        $purchases = new PurchaseRepository();
        $tier = $customer['tier_id'] !== null ? (new TierRepository())->find((int) $customer['tier_id']) : null;
        $segment = (new SegmentRepository())->forCustomer((int) $customer['id']);
        $tags = (new CustomerTagRepository())->forCustomer((int) $customer['id']);

        $this->ok([
            'customer' => $this->customerResource($customer),
            'tier' => $tier !== null ? ['id' => (int) $tier['id'], 'name' => $tier['name'], 'cashback_percent' => (float) $tier['cashback_percent']] : null,
            'segment' => $segment !== null ? [
                'code' => $segment['segment_code'],
                'r' => (int) $segment['r_score'],
                'f' => (int) $segment['f_score'],
                'm' => (int) $segment['m_score'],
            ] : null,
            'tags' => array_map(static fn (array $tag): array => ['id' => (int) $tag['id'], 'name' => $tag['name']], $tags),
            'stats' => [
                'lifetime_spend' => $purchases->lifetimeSpend((int) $customer['id']),
                'lifetime_cashback' => $purchases->lifetimeCashbackEarned((int) $customer['id']),
                'purchase_count' => $purchases->activePurchaseCount((int) $customer['id']),
            ],
        ]);
    }

    public function byPhone(array $params = []): void
    {
        $phone = \normalize_digits((string) ($_GET['phone'] ?? ''));
        $customer = (new CustomerRepository())->findByPhone($phone);
        if (!$customer) {
            $this->fail('Not found', 404);
            return;
        }

        // Legacy keys are kept so existing integrations keep working.
        $this->ok([
            'customer' => [
                'id' => (int) $customer['id'],
                'full_name' => $customer['first_name'] . ' ' . $customer['last_name'],
                'wallet_balance' => (float) $customer['wallet_balance'],
                'phone_number' => $customer['phone_number'],
                'tier_id' => $customer['tier_id'] !== null ? (int) $customer['tier_id'] : null,
                'national_code' => $customer['national_code'] ?? null,
            ],
        ]);
    }

    public function create(array $params = []): void
    {
        $result = (new CustomerService())->create($this->customerPayload($this->body()));
        if (!$result['ok']) {
            $this->fail('داده‌های مشتری معتبر نیست.', 422, ['errors' => $result['errors']]);
            return;
        }

        $customer = (new CustomerRepository())->find((int) $result['id']);
        $this->ok(['customer' => $customer !== null ? $this->customerResource($customer) : ['id' => (int) $result['id']]], 201);
    }

    public function update(array $params): void
    {
        $existing = (new CustomerRepository())->find($params['id']);
        if (!$existing || !empty($existing['deleted_at'])) {
            $this->fail('مشتری یافت نشد.', 404);
            return;
        }

        // CustomerService::update() expects the whole record, so merge the payload
        // over the current values: PATCH with one field must not blank the rest.
        $payload = array_merge([
            'first_name' => $existing['first_name'] ?? '',
            'last_name' => $existing['last_name'] ?? '',
            'company' => $existing['company'] ?? '',
            'national_code' => $existing['national_code'] ?? '',
            'phone_number' => $existing['phone_number'] ?? '',
            'email' => $existing['email'] ?? '',
            'address' => $existing['address'] ?? '',
            'description' => $existing['description'] ?? '',
            'birthday' => $existing['birthday'] ?? '',
            'referred_by_customer_id' => $existing['referred_by_customer_id'] ?? '',
            'contract_number' => $existing['contract_number'] ?? '',
            'contract_starts_at' => $existing['contract_starts_at'] ?? '',
            'contract_ends_at' => $existing['contract_ends_at'] ?? '',
        ], $this->customerPayload($this->body()));

        $result = (new CustomerService())->update($params['id'], $payload);
        if (!$result['ok']) {
            $this->fail('داده‌های مشتری معتبر نیست.', 422, ['errors' => $result['errors']]);
            return;
        }

        $customer = (new CustomerRepository())->find($params['id']);
        $this->ok(['customer' => $customer !== null ? $this->customerResource($customer) : null]);
    }

    public function destroy(array $params): void
    {
        $existing = (new CustomerRepository())->find($params['id']);
        if (!$existing || !empty($existing['deleted_at'])) {
            $this->fail('مشتری یافت نشد.', 404);
            return;
        }

        (new CustomerRepository())->softDelete($params['id']);
        $this->ok(['deleted' => true, 'id' => $params['id']]);
    }

    public function purchases(array $params): void
    {
        $rows = (new PurchaseRepository())->forCustomer($params['id']);
        $this->ok([
            'items' => array_map(fn (array $purchase): array => $this->purchaseResource($purchase), $rows),
            'total' => count($rows),
        ]);
    }

    public function wallet(array $params): void
    {
        $customer = (new CustomerRepository())->find($params['id']);
        if (!$customer) {
            $this->fail('مشتری یافت نشد.', 404);
            return;
        }

        $this->ok([
            'customer_id' => (int) $customer['id'],
            'balance' => (float) $customer['wallet_balance'],
            'transactions' => (new WalletRepository())->forCustomer((int) $customer['id'], 100),
        ]);
    }

    public function tags(array $params): void
    {
        $tags = (new CustomerTagRepository())->forCustomer($params['id']);
        $this->ok([
            'items' => array_map(static fn (array $tag): array => ['id' => (int) $tag['id'], 'name' => $tag['name']], $tags),
        ]);
    }

    public function syncTags(array $params): void
    {
        $body = $this->body();
        $tagIds = $body['tag_ids'] ?? [];
        if (!is_array($tagIds)) {
            $this->fail('tag_ids باید آرایه‌ای از شناسه‌ها باشد.', 422);
            return;
        }

        $repository = new CustomerTagRepository();
        $repository->sync($params['id'], array_map('intval', $tagIds));
        $tags = $repository->forCustomer($params['id']);

        $this->ok([
            'items' => array_map(static fn (array $tag): array => ['id' => (int) $tag['id'], 'name' => $tag['name']], $tags),
        ]);
    }
}
