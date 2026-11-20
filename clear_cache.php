<?php

declare(strict_types=1);

use App\Core\Auth;

/**
 * One-off cache reset for shared hosting.
 *
 * OPcache can keep serving the previous version of a changed PHP file (this is
 * what makes a finished update still show old warnings). Open this URL once
 * while logged in as admin after an update:
 *
 *     https://your-domain/clear_cache.php
 */

require __DIR__ . '/bootstrap/app.php';

header('Content-Type: text/plain; charset=utf-8');

if (!Auth::check() || !Auth::isAdmin()) {
    http_response_code(403);
    echo "Forbidden: log in to the panel as an admin first, then open this URL again.\n";
    exit;
}

$lines = [];

if (function_exists('opcache_reset')) {
    $lines[] = @opcache_reset() ? 'opcache: reset' : 'opcache: reset failed';
} else {
    $lines[] = 'opcache: not enabled';
}

if (function_exists('opcache_invalidate')) {
    foreach (['resources/views/layouts/app.php', 'routes.php', 'app/helpers.php'] as $file) {
        @opcache_invalidate(__DIR__ . '/' . $file, true);
    }
    $lines[] = 'opcache: key files invalidated';
}

if (function_exists('apcu_clear_cache')) {
    @apcu_clear_cache();
    $lines[] = 'apcu: cleared';
}

if (function_exists('realpath_cache_clear')) {
    @realpath_cache_clear();
}

$lines[] = 'version: ' . \app_version();

echo "OK\n" . implode("\n", $lines) . "\n";
