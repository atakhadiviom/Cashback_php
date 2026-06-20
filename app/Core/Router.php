<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, mixed>> */
    private $routes = [];

    public function get(string $path, array $handler, bool $auth = true, ?string $role = null, ?string $permission = null): void
    {
        $this->routes['GET'][$path] = compact('handler', 'auth', 'role', 'permission');
    }

    public function post(string $path, array $handler, bool $auth = true, ?string $role = null, ?string $permission = null): void
    {
        $this->routes['POST'][$path] = compact('handler', 'auth', 'role', 'permission');
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = parse_url((string) \config_value('app.base_url', ''), PHP_URL_PATH);
        if ($base && str_starts_with($path, rtrim($base, '/'))) {
            $path = substr($path, strlen(rtrim($base, '/'))) ?: '/';
        }
        $path = '/' . trim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        if (str_starts_with($path, '/api/v1')) {
            $this->dispatchApi($method, $path);
            return;
        }

        $route = $this->routes[$method][$path] ?? null;
        if (!$route) {
            http_response_code(404);
            echo '404';
            return;
        }

        if ($route['auth'] && !Auth::check()) {
            \redirect('/login');
        }
        if ($route['role'] && Auth::role() !== $route['role']) {
            Flash::set('danger', 'شما به این بخش دسترسی ندارید.');
            \redirect('/dashboard');
        }
        if ($route['permission'] && !Auth::can($route['permission'])) {
            Flash::set('danger', 'شما به این بخش دسترسی ندارید.');
            \redirect('/dashboard');
        }

        [$class, $methodName] = $route['handler'];
        (new $class())->{$methodName}();
    }

    private function dispatchApi(string $method, string $path): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $auth = new \App\Services\ApiAuthService();
        $keyRow = $auth->authenticate($apiKey);
        if (!$keyRow) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $controller = new \App\Controllers\Api\V1Controller();
        if ($method === 'POST' && $path === '/api/v1/purchases') {
            $controller->createPurchase();
            return;
        }
        if ($method === 'GET' && $path === '/api/v1/customers/by-phone') {
            $controller->customerByPhone();
            return;
        }
        if ($method === 'POST' && $path === '/api/v1/wallet/reduce') {
            $controller->reduceWallet();
            return;
        }

        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Not found'], JSON_UNESCAPED_UNICODE);
    }
}
