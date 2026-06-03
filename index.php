<?php

declare(strict_types=1);

// WordPress-style docroot: serve legacy asset aliases and /public/assets without relying on .htaccess.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basename = basename($requestPath);
$publicDir = __DIR__ . '/public';

$assetAliases = [
    'app.css' => ['assets/css/app.css', 'text/css; charset=UTF-8'],
    'bootstrap.rtl.min.css' => ['assets/vendor/bootstrap/bootstrap.rtl.min.css', 'text/css; charset=UTF-8'],
    'bootstrap-icons.min.css' => ['assets/vendor/bootstrap-icons/bootstrap-icons.min.css', 'text/css; charset=UTF-8'],
    'app.js' => ['assets/js/app.js', 'application/javascript; charset=UTF-8'],
    'bootstrap.bundle.min.js' => ['assets/vendor/bootstrap/bootstrap.bundle.min.js', 'application/javascript; charset=UTF-8'],
    'bootstrap-icons.woff' => ['assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff', 'font/woff'],
    'bootstrap-icons.woff2' => ['assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2', 'font/woff2'],
];

if (isset($assetAliases[$basename])) {
    [$relativeFile, $contentType] = $assetAliases[$basename];
    $file = $publicDir . '/' . $relativeFile;
    if (is_file($file)) {
        header('Content-Type: ' . $contentType);
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

if (str_starts_with($requestPath, '/public/assets/')) {
    $file = $publicDir . substr($requestPath, strlen('/public')) ;
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = [
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

require $publicDir . '/index.php';
