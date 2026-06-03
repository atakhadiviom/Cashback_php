<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CustomerRepository;
use App\Services\PurchaseService;
use App\Services\WalletService;

final class V1Controller
{
    public function createPurchase(): void
    {
        $body = $this->jsonBody();
        $customerId = (int) ($body['customer_id'] ?? 0);
        $amount = $body['amount'] ?? 0;
        $idempotencyKey = trim((string) ($_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? $body['idempotency_key'] ?? ''));

        $result = (new PurchaseService())->create($customerId, $amount, [
            'invoice_ref' => (string) ($body['invoice_ref'] ?? ''),
            'idempotency_key' => $idempotencyKey,
            'confirm_duplicate' => !empty($body['confirm_duplicate']),
            'created_by' => \App\Services\SystemUserService::actorId(),
        ]);

        if (!$result['ok']) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'errors' => $result['errors']], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'ok' => true,
            'purchase_id' => $result['purchase_id'],
            'cashback' => $result['cashback'],
            'percent_applied' => $result['percent_applied'] ?? null,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function customerByPhone(): void
    {
        $phone = \normalize_digits((string) ($_GET['phone'] ?? ''));
        $customer = (new CustomerRepository())->findByPhone($phone);
        if (!$customer) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Not found'], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode([
            'ok' => true,
            'customer' => [
                'id' => (int) $customer['id'],
                'full_name' => $customer['first_name'] . ' ' . $customer['last_name'],
                'wallet_balance' => (float) $customer['wallet_balance'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function reduceWallet(): void
    {
        $body = $this->jsonBody();
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
            http_response_code(422);
            echo json_encode(['ok' => false, 'errors' => $result['errors']], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string, mixed> */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
