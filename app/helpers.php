<?php

declare(strict_types=1);

function config_value(string $key, mixed $default = null): mixed
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

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) config_value('app.base_url', ''), '/');
    return $base . '/' . ltrim($path, '/');
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

function money(mixed $amount): string
{
    return number_format((float) $amount, 0, '.', ',');
}

function current_datetime(): string
{
    return date('Y-m-d H:i:s');
}

function request_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 45);
}

function post_value(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function query_value(string $key, mixed $default = ''): mixed
{
    return $_GET[$key] ?? $default;
}
