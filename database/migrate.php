<?php

declare(strict_types=1);

/**
 * Run pending SQL migrations. Usage: php database/migrate.php
 */
require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;

$pdo = Database::pdo();
$dir = __DIR__ . '/migrations';
$files = glob($dir . '/*.sql');
sort($files);

$pdo->exec(file_get_contents($dir . '/000_schema_migrations.sql'));

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);

foreach ($files as $file) {
    $version = basename($file);
    if ($version === '000_schema_migrations.sql' || isset($applied[$version])) {
        continue;
    }

    echo "Applying {$version}...\n";
    $sql = file_get_contents($file);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $stmt = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (:version, :applied_at)');
    $stmt->execute(['version' => $version, 'applied_at' => date('Y-m-d H:i:s')]);
    echo "OK\n";
}

echo "Migrations complete.\n";
