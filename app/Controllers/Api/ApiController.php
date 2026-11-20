<?php

declare(strict_types=1);

namespace App\Controllers\Api;

/**
 * Shared helpers for the JSON API: consistent output, body parsing,
 * pagination and Jalali/Gregorian date normalisation.
 */
abstract class ApiController
{
    /** @var array<string, mixed>|null */
    private ?array $bodyCache = null;

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function ok(array $payload = [], int $status = 200): void
    {
        $this->json(['ok' => true] + $payload, $status);
    }

    protected function fail(string $error, int $status = 422, array $extra = []): void
    {
        $this->json(['ok' => false, 'error' => $error] + $extra, $status);
    }

    /** @return array<string, mixed> */
    protected function body(): array
    {
        if ($this->bodyCache !== null) {
            return $this->bodyCache;
        }
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = $_POST;
        }
        $this->bodyCache = is_array($decoded) ? $decoded : [];

        return $this->bodyCache;
    }

    /**
     * Read a value from the JSON body first, then the query string.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        $body = $this->body();
        if (array_key_exists($key, $body)) {
            return $body[$key];
        }

        return $_GET[$key] ?? $default;
    }

    /** @return array{0: int, 1: int} [limit, offset] */
    protected function paging(): array
    {
        $limit = (int) ($_GET['limit'] ?? 50);
        $offset = (int) ($_GET['offset'] ?? 0);

        return [max(1, min(200, $limit)), max(0, $offset)];
    }

    /**
     * Accepts Y-m-d or a Jalali date (1403/06/15) and returns Y-m-d, or '' when empty.
     */
    protected function date(string $value): string
    {
        $value = trim(\normalize_digits($value));
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return \App\Core\Jalali::parseInputToGregorian($value) ?? '';
    }

    /**
     * Normalise a customer payload coming from JSON (arrays/objects are rejected).
     *
     * @param array<string, mixed> $body
     * @return array<string, string>
     */
    protected function customerPayload(array $body): array
    {
        $fields = [
            'first_name', 'last_name', 'company', 'national_code', 'phone_number', 'email',
            'address', 'description', 'birthday', 'referred_by_customer_id', 'contract_number',
            'contract_starts_at', 'contract_ends_at',
        ];
        $out = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $body)) {
                continue;
            }
            $value = $body[$field];
            $out[$field] = is_array($value) ? '' : trim((string) $value);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $customer
     * @return array<string, mixed>
     */
    protected function customerResource(array $customer): array
    {
        return [
            'id' => (int) $customer['id'],
            'first_name' => (string) $customer['first_name'],
            'last_name' => (string) ($customer['last_name'] ?? ''),
            'full_name' => trim($customer['first_name'] . ' ' . ($customer['last_name'] ?? '')),
            'company' => $customer['company'] ?? null,
            'national_code' => $customer['national_code'] ?? null,
            'phone_number' => (string) $customer['phone_number'],
            'email' => $customer['email'] ?? null,
            'birthday' => $customer['birthday'] ?? null,
            'wallet_balance' => (float) ($customer['wallet_balance'] ?? 0),
            'tier_id' => $customer['tier_id'] !== null ? (int) $customer['tier_id'] : null,
            'referred_by_customer_id' => $customer['referred_by_customer_id'] !== null ? (int) $customer['referred_by_customer_id'] : null,
            'contract_number' => $customer['contract_number'] ?? null,
            'contract_starts_at' => $customer['contract_starts_at'] ?? null,
            'contract_ends_at' => $customer['contract_ends_at'] ?? null,
            'created_at' => $customer['created_at'] ?? null,
            'deleted_at' => $customer['deleted_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $purchase
     * @return array<string, mixed>
     */
    protected function purchaseResource(array $purchase): array
    {
        return [
            'id' => (int) $purchase['id'],
            'customer_id' => (int) $purchase['customer_id'],
            'amount' => (float) $purchase['amount'],
            'cashback_amount' => (float) $purchase['cashback_amount'],
            'cashback_percent_applied' => (float) ($purchase['cashback_percent_applied'] ?? 0),
            'status' => (string) ($purchase['status'] ?? 'active'),
            'invoice_ref' => $purchase['invoice_ref'] ?? null,
            'void_reason' => $purchase['void_reason'] ?? null,
            'created_by' => isset($purchase['created_by']) ? (int) $purchase['created_by'] : null,
            'created_at' => $purchase['created_at'] ?? null,
        ];
    }
}
