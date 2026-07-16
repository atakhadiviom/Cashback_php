<?php

declare(strict_types=1);

/**
 * Run pending SQL migrations. Usage: php database/migrate.php
 */
require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Services\MigrationRunner;

$result = (new MigrationRunner(Database::pdo()))->runPending(__DIR__ . '/migrations');

foreach ($result['applied'] as $version) {
    echo "Applying {$version}...\nOK\n";
}

foreach ($result['repaired'] as $version) {
    echo "Repairing {$version}...\nOK\n";
}

echo 'Migrations complete.' . PHP_EOL;
