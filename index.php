<?php

declare(strict_types=1);

// DirectAdmin/shared hosting often forces the subdomain docroot to the project root.
// Redirect into /public/ so static assets and routing work without relying on rewrites.
$target = '/public/';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header('Location: ' . $target, true, 302);
exit;

