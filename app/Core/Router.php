<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, bool $auth = true, ?string $role = null): void
    {
        $this->routes['GET'][$path] = compact('handler', 'auth', 'role');
    }

    public function post(string $path, array $handler, bool $auth = true, ?string $role = null): void
    {
        $this->routes['POST'][$path] = compact('handler', 'auth', 'role');
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

        [$class, $methodName] = $route['handler'];
        (new $class())->{$methodName}();
    }
}
