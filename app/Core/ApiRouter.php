<?php

declare(strict_types=1);

namespace App\Core;

/**
 * JSON API dispatch: key authentication, per-method read/write scopes and a
 * small route table with numeric placeholders (/customers/{id}).
 */
final class ApiRouter
{
    /**
     * method, pattern, handler
     *
     * @var list<array{0: string, 1: string, 2: array{0: class-string, 1: string}}>
     */
    private const ROUTES = [
        ['GET', '/api/v1/health', [\App\Controllers\Api\HealthController::class, 'show']],

        ['GET', '/api/v1/customers', [\App\Controllers\Api\CustomersController::class, 'index']],
        ['GET', '/api/v1/customers/by-phone', [\App\Controllers\Api\CustomersController::class, 'byPhone']],
        ['GET', '/api/v1/customers/{id}', [\App\Controllers\Api\CustomersController::class, 'show']],
        ['POST', '/api/v1/customers', [\App\Controllers\Api\CustomersController::class, 'create']],
        ['PUT', '/api/v1/customers/{id}', [\App\Controllers\Api\CustomersController::class, 'update']],
        ['PATCH', '/api/v1/customers/{id}', [\App\Controllers\Api\CustomersController::class, 'update']],
        ['DELETE', '/api/v1/customers/{id}', [\App\Controllers\Api\CustomersController::class, 'destroy']],
        ['GET', '/api/v1/customers/{id}/purchases', [\App\Controllers\Api\CustomersController::class, 'purchases']],
        ['GET', '/api/v1/customers/{id}/wallet', [\App\Controllers\Api\CustomersController::class, 'wallet']],
        ['GET', '/api/v1/customers/{id}/tags', [\App\Controllers\Api\CustomersController::class, 'tags']],
        ['PUT', '/api/v1/customers/{id}/tags', [\App\Controllers\Api\CustomersController::class, 'syncTags']],

        ['GET', '/api/v1/purchases', [\App\Controllers\Api\PurchasesController::class, 'index']],
        ['GET', '/api/v1/purchases/{id}', [\App\Controllers\Api\PurchasesController::class, 'show']],
        ['POST', '/api/v1/purchases', [\App\Controllers\Api\PurchasesController::class, 'create']],
        ['POST', '/api/v1/purchases/{id}/void', [\App\Controllers\Api\PurchasesController::class, 'void']],

        ['GET', '/api/v1/wallet/{id}', [\App\Controllers\Api\WalletController::class, 'show']],
        ['GET', '/api/v1/wallet/{id}/transactions', [\App\Controllers\Api\WalletController::class, 'transactions']],
        ['POST', '/api/v1/wallet/reduce', [\App\Controllers\Api\WalletController::class, 'reduce']],
        ['POST', '/api/v1/wallet/credit', [\App\Controllers\Api\WalletController::class, 'credit']],

        ['GET', '/api/v1/tiers', [\App\Controllers\Api\LoyaltyController::class, 'tiers']],
        ['POST', '/api/v1/tiers/recalculate', [\App\Controllers\Api\LoyaltyController::class, 'recalculateTiers']],
        ['GET', '/api/v1/segments', [\App\Controllers\Api\LoyaltyController::class, 'segments']],
        ['POST', '/api/v1/segments/refresh', [\App\Controllers\Api\LoyaltyController::class, 'refreshSegments']],
        ['GET', '/api/v1/tags', [\App\Controllers\Api\LoyaltyController::class, 'tags']],
        ['POST', '/api/v1/tags', [\App\Controllers\Api\LoyaltyController::class, 'createTag']],
        ['PUT', '/api/v1/tags/{id}', [\App\Controllers\Api\LoyaltyController::class, 'updateTag']],
        ['DELETE', '/api/v1/tags/{id}', [\App\Controllers\Api\LoyaltyController::class, 'deleteTag']],

        ['GET', '/api/v1/reports/summary', [\App\Controllers\Api\ReportsController::class, 'summary']],
        ['GET', '/api/v1/reports/customers', [\App\Controllers\Api\ReportsController::class, 'customers']],
        ['GET', '/api/v1/reports/cashback', [\App\Controllers\Api\ReportsController::class, 'cashback']],
        ['GET', '/api/v1/reports/liability', [\App\Controllers\Api\ReportsController::class, 'liability']],

        ['GET', '/api/v1/reminders/rules', [\App\Controllers\Api\RemindersController::class, 'rules']],
        ['POST', '/api/v1/reminders/rules', [\App\Controllers\Api\RemindersController::class, 'createRule']],
        ['PUT', '/api/v1/reminders/rules/{id}', [\App\Controllers\Api\RemindersController::class, 'updateRule']],
        ['PATCH', '/api/v1/reminders/rules/{id}', [\App\Controllers\Api\RemindersController::class, 'updateRule']],
        ['DELETE', '/api/v1/reminders/rules/{id}', [\App\Controllers\Api\RemindersController::class, 'deleteRule']],
        ['GET', '/api/v1/reminders', [\App\Controllers\Api\RemindersController::class, 'queue']],
        ['POST', '/api/v1/reminders/plan', [\App\Controllers\Api\RemindersController::class, 'plan']],
        ['POST', '/api/v1/reminders/dispatch', [\App\Controllers\Api\RemindersController::class, 'dispatch']],
    ];

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        // shared hosts often cannot send PUT/PATCH/DELETE: allow an override
        $override = strtoupper(trim((string) ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_REQUEST['_method'] ?? ''))));
        if ($method === 'POST' && in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
            $method = $override;
        }

        $apiKey = (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');
        $keyRow = (new \App\Services\ApiAuthService())->authenticate($apiKey);
        if ($keyRow === null) {
            $this->write(['ok' => false, 'error' => 'Unauthorized'], 401);
            return;
        }

        $needed = $method === 'GET' ? 'read' : 'write';
        if (!$this->keyAllows($keyRow, $needed)) {
            $this->write(['ok' => false, 'error' => 'این کلید دسترسی ' . $needed . ' ندارد.'], 403);
            return;
        }

        $allowedMethods = [];
        foreach (self::ROUTES as [$routeMethod, $pattern, $handler]) {
            $regex = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>\d+)', $pattern) . '$#';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            $allowedMethods[] = $routeMethod;
            if ($routeMethod !== $method) {
                continue;
            }

            $params = [];
            foreach ($matches as $name => $value) {
                if (!is_int($name)) {
                    $params[$name] = (int) $value;
                }
            }

            [$class, $action] = $handler;
            try {
                (new $class())->{$action}($params);
            } catch (\Throwable $exception) {
                error_log('[api] ' . $path . ' -> ' . $exception->getMessage());
                $this->write(['ok' => false, 'error' => 'Internal error'], 500);
            }
            return;
        }

        if ($allowedMethods !== []) {
            $this->write(['ok' => false, 'error' => 'Method not allowed'], 405);
            return;
        }

        $this->write(['ok' => false, 'error' => 'Not found'], 404);
    }

    /**
     * @param array<string, mixed> $keyRow
     */
    private function keyAllows(array $keyRow, string $scope): bool
    {
        $scopes = trim((string) ($keyRow['scopes'] ?? ''));
        if ($scopes === '') {
            // Column not migrated yet: legacy keys keep full access.
            return true;
        }
        $list = array_filter(array_map('trim', explode(',', strtolower($scopes))));

        return in_array('*', $list, true) || in_array($scope, $list, true);
    }

    /** @param array<string, mixed> $payload */
    private function write(array $payload, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
