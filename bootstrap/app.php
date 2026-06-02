<?php

declare(strict_types=1);

$GLOBALS['config'] = require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/app/helpers.php';

date_default_timezone_set((string) config_value('app.timezone', 'Asia/Tehran'));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

if (PHP_SAPI !== 'cli') {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name((string) config_value('security.session_name', 'cashback_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$router = require dirname(__DIR__) . '/routes.php';
return $router;
