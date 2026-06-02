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
$assetAliases = [
    'app.css' => ['assets/css/app.css', 'text/css; charset=UTF-8'],
    'bootstrap.rtl.min.css' => ['assets/vendor/bootstrap/bootstrap.rtl.min.css', 'text/css; charset=UTF-8'],
    'bootstrap-icons.min.css' => ['assets/vendor/bootstrap-icons/bootstrap-icons.min.css', 'text/css; charset=UTF-8'],
    'app.js' => ['assets/js/app.js', 'application/javascript; charset=UTF-8'],
    'bootstrap.bundle.min.js' => ['assets/vendor/bootstrap/bootstrap.bundle.min.js', 'application/javascript; charset=UTF-8'],
];
if (isset($assetAliases[$basename])) {
    [$relativeFile, $contentType] = $assetAliases[$basename];
    $file = __DIR__ . '/' . $relativeFile;
    if (is_file($file)) {
        header('Content-Type: ' . $contentType);
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

$router = require dirname(__DIR__) . '/bootstrap/app.php';
$router->dispatch();
