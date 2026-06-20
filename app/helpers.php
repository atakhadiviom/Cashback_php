<?php

declare(strict_types=1);

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

function config_value(string $key, $default = null)
{
    $value = $GLOBALS['config'] ?? [];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalize_base_url(string $base): string
{
    $base = trim($base);
    if ($base === '' || $base === '/') {
        return '';
    }
    if ($base[0] !== '/') {
        $base = '/' . $base;
    }
    return rtrim($base, '/');
}

function infer_base_url(): string
{
    if (PHP_SAPI === 'cli') {
        return '';
    }

    $requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    // If the web server docroot is the public folder, we want clean URLs.
    if ($docRoot !== '' && str_ends_with($docRoot, '/public')) {
        return '';
    }

    // If the app is being accessed through /public/... (rewrite disabled or docroot forced),
    // keep /public in generated links to avoid broken routing and asset 404s.
    if ($requestPath === '/public' || str_starts_with($requestPath, '/public/')) {
        return '/public';
    }

    // Default: project root with rewrite enabled (clean URLs).
    return '';
}

function url(string $path = ''): string
{
    $configuredBase = normalize_base_url((string) config_value('app.base_url', ''));
    $base = $configuredBase !== '' ? $configuredBase : infer_base_url();
    $path = ltrim($path, '/');
    return $base . '/' . $path;
}

function asset_url(string $logicalPath): string
{
    $logicalPath = ltrim($logicalPath, '/');
    $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    // If docroot is /public, assets are directly available under /assets.
    if ($docRoot !== '' && str_ends_with($docRoot, '/public')) {
        return url('/assets/' . $logicalPath);
    }

    // If we are being accessed under /public (rewrite disabled), point directly to real files.
    $requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if ($requestPath === '/public' || str_starts_with($requestPath, '/public/')) {
        return url('/public/assets/' . $logicalPath);
    }

    // Otherwise prefer clean /assets/... (served by .htaccess when rewrite is enabled).
    return url('/assets/' . $logicalPath);
}

function app_version(): string
{
    static $version = null;
    if (is_string($version)) {
        return $version;
    }

    $versionFile = dirname(__DIR__) . '/VERSION';
    $version = is_file($versionFile) ? trim((string) file_get_contents($versionFile)) : 'dev';
    return $version !== '' ? $version : 'dev';
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function normalize_digits(string $value): string
{
    return strtr($value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function normalize_persian_text(string $value): string
{
    $value = normalize_digits($value);
    $value = str_replace("\u{200C}", '', $value);

    return strtr($value, [
        'ي' => 'ی',
        'ى' => 'ی',
        'ئ' => 'ی',
        'ك' => 'ک',
        'ة' => 'ه',
        'أ' => 'ا',
        'إ' => 'ا',
        'ٱ' => 'ا',
        'آ' => 'ا',
        'ؤ' => 'و',
    ]);
}

function normalize_search_text(string $value): string
{
    return normalize_persian_text($value);
}

function search_like_term(string $value): string
{
    return '%' . normalize_search_text($value) . '%';
}

function sql_normalize_persian(string $column): string
{
    static $pairs = [
        ['ي', 'ی'],
        ['ى', 'ی'],
        ['ئ', 'ی'],
        ['ك', 'ک'],
        ['ة', 'ه'],
        ['أ', 'ا'],
        ['إ', 'ا'],
        ['ٱ', 'ا'],
        ['آ', 'ا'],
        ['ؤ', 'و'],
    ];

    $expression = $column;
    foreach ($pairs as [$from, $to]) {
        $expression = "REPLACE({$expression}, '{$from}', '{$to}')";
    }

    return $expression;
}

function money($amount): string
{
    return number_format((float) $amount, 0, '.', ',');
}

function parse_money_input(mixed $value, float $default = 0.0): float
{
    $normalized = str_replace(',', '', normalize_digits(trim((string) $value)));
    if ($normalized === '') {
        return $default;
    }

    return (float) $normalized;
}

function money_input_value(mixed $amount, string $default = '0'): string
{
    if ($amount === null || $amount === '') {
        return $default;
    }

    $value = (float) $amount;
    if ($value == 0.0) {
        return '0';
    }

    return number_format($value, 0, '.', ',');
}

function current_datetime(): string
{
    return date('Y-m-d H:i:s');
}

function request_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 45);
}

function post_value(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function query_value(string $key, $default = '')
{
    return $_GET[$key] ?? $default;
}
