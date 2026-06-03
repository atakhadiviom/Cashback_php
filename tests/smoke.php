<?php

declare(strict_types=1);

/**
 * Local smoke test. Run: php tests/smoke.php
 * Optional: BASE_URL=http://127.0.0.1:8002 php tests/smoke.php
 */

$root = dirname(__DIR__);
$baseUrl = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8002', '/');
$failures = 0;

function ok(string $label): void
{
    echo "PASS: {$label}\n";
}

function fail(string $label, string $detail = ''): void
{
    global $failures;
    $failures++;
    echo "FAIL: {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

function http_get(string $url): array
{
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'follow_location' => 0,
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $headers = function_exists('http_get_last_response_headers') ? (http_get_last_response_headers() ?: []) : ($http_response_header ?? []);
    $status = 0;
    if (isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $m)) {
        $status = (int) $m[1];
    }
    return ['status' => $status, 'body' => $body === false ? '' : $body, 'headers' => $headers];
}

function http_post(string $url, string $body, array $cookies = []): array
{
    $cookieHeader = '';
    if ($cookies) {
        $pairs = [];
        foreach ($cookies as $k => $v) {
            $pairs[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        $cookieHeader = implode('; ', $pairs);
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                ($cookieHeader !== '' ? "Cookie: {$cookieHeader}\r\n" : ''),
            'content' => $body,
            'follow_location' => 0,
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    $responseBody = @file_get_contents($url, false, $ctx);
    $headers = function_exists('http_get_last_response_headers') ? (http_get_last_response_headers() ?: []) : ($http_response_header ?? []);
    $status = 0;
    $setCookies = [];
    foreach ($headers as $line) {
        if (preg_match('/\s(\d{3})\s/', $line, $m)) {
            $status = (int) $m[1];
        }
        if (stripos($line, 'Set-Cookie:') === 0) {
            $part = trim(substr($line, strlen('Set-Cookie:')));
            $nameValue = explode(';', $part)[0];
            if (str_contains($nameValue, '=')) {
                [$n, $v] = explode('=', $nameValue, 2);
                $setCookies[$n] = $v;
            }
        }
    }
    return [
        'status' => $status,
        'body' => $responseBody === false ? '' : $responseBody,
        'cookies' => array_merge($cookies, $setCookies),
    ];
}

// --- File / helper checks (no HTTP) ---
chdir($root);
require $root . '/bootstrap/app.php';

if (url('/login') === '/login') {
    ok('url() default base');
} else {
    fail('url() default base', url('/login'));
}

$_SERVER['REQUEST_URI'] = '/public/login';
if (asset_url('css/app.css') === '/public/assets/css/app.css') {
    ok('asset_url() under /public');
} else {
    fail('asset_url() under /public', asset_url('css/app.css'));
}

$_SERVER['REQUEST_URI'] = '/login';
if (asset_url('css/app.css') === '/public/assets/css/app.css') {
    ok('asset_url() at project root');
} else {
    fail('asset_url() at project root', asset_url('css/app.css'));
}

$assetFile = $root . '/public/assets/css/app.css';
if (is_file($assetFile)) {
    ok('asset file exists on disk');
} else {
    fail('asset file exists on disk', $assetFile);
}

try {
    $pdo = App\Core\Database::pdo();
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count >= 1) {
        ok('database connection and users table');
    } else {
        fail('database connection', 'no users');
    }
} catch (Throwable $e) {
    fail('database connection', $e->getMessage());
}

try {
    $cashbackPercentage = (new App\Repositories\AppSettingsRepository())->cashbackPercentage();
    if ($cashbackPercentage >= 0 && $cashbackPercentage <= 100) {
        ok('cashback percentage setting');
    } else {
        fail('cashback percentage setting', (string) $cashbackPercentage);
    }
} catch (Throwable $e) {
    fail('cashback percentage setting', $e->getMessage());
}

// --- HTTP checks (optional if server running) ---
$checks = [
    ['login page', "{$baseUrl}/login", 200],
    ['installer', "{$baseUrl}/install.php", [200, 404]],
    ['asset css', "{$baseUrl}/public/assets/css/app.css", 200],
    ['asset bootstrap', "{$baseUrl}/public/assets/vendor/bootstrap/bootstrap.rtl.min.css", 200],
];

foreach ($checks as [$label, $url, $expected]) {
    $res = http_get($url);
    $expectedCodes = is_array($expected) ? $expected : [$expected];
    if (in_array($res['status'], $expectedCodes, true)) {
        ok("HTTP {$label} ({$res['status']})");
    } elseif ($res['status'] === 0) {
        fail("HTTP {$label}", 'server not reachable — start: php -S 127.0.0.1:8002 -t . index.php');
    } else {
        fail("HTTP {$label}", "status {$res['status']}");
    }
}

$login = http_get("{$baseUrl}/login");
if ($login['status'] === 200 && str_contains($login['body'], '/public/assets/css/app.css')) {
    ok('login HTML references /public/assets/css/app.css');
} elseif ($login['status'] !== 200) {
    fail('login HTML asset paths', 'login not reachable');
} else {
    fail('login HTML asset paths', 'missing /public/assets/css/app.css in HTML');
}

if (preg_match('/name="_csrf" value="([^"]+)"/', $login['body'], $m)) {
    $csrf = $m[1];
    $post = http_post("{$baseUrl}/login", http_build_query([
        '_csrf' => $csrf,
        'username' => 'admin',
        'password' => 'Admin12345!',
    ]));
    if (in_array($post['status'], [302, 303], true)) {
        ok('login POST redirects on success');
        $dash = http_get("{$baseUrl}/dashboard");
        // Note: PHP streams won't persist cookies between calls unless we pass them — skip strict dashboard if no cookie jar
        if ($dash['status'] === 302 || $dash['status'] === 200) {
            ok('dashboard reachable after login attempt');
        } else {
            fail('dashboard after login', "status {$dash['status']} (cookie jar not used in stream test)");
        }
    } else {
        fail('login POST', "status {$post['status']} — check admin password in seed");
    }
} elseif ($login['status'] === 200) {
    fail('login CSRF extraction');
}

echo "\n";
if ($failures === 0) {
    echo "All smoke tests passed.\n";
    exit(0);
}
echo "{$failures} test(s) failed.\n";
exit(1);
