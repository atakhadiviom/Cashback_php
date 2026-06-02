<?php

declare(strict_types=1);

// Redirect installer entrypoint into /public when docroot is the project root.
$target = '/public/install.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header('Location: ' . $target, true, 302);
exit;

