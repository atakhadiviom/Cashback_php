<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basename = basename($requestPath);
if ($basename === 'app.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/assets/css/app.css');
    exit;
}
if ($basename === 'app.js') {
    header('Content-Type: application/javascript; charset=UTF-8');
    readfile(__DIR__ . '/assets/js/app.js');
    exit;
}

$router = require dirname(__DIR__) . '/bootstrap/app.php';
$router->dispatch();
