<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MigrationRunner;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function testRunPendingReturnsEmptyWhenMigrationDirectoryIsMissingSchemaFile(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $dir = sys_get_temp_dir() . '/cashback-migrations-' . bin2hex(random_bytes(4));
        mkdir($dir);

        try {
            $result = (new MigrationRunner($pdo))->runPending($dir);
            $this->assertSame(['applied' => [], 'repaired' => []], $result);
        } finally {
            @rmdir($dir);
        }
    }

    public function testRunPendingAppliesNewMigrationAndVerifiesCreatedTable(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $dir = sys_get_temp_dir() . '/cashback-migrations-' . bin2hex(random_bytes(4));
        mkdir($dir);

        file_put_contents($dir . '/000_schema_migrations.sql', <<<'SQL'
CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(255) PRIMARY KEY,
  applied_at DATETIME NOT NULL
);
SQL);
        file_put_contents($dir . '/001_example.sql', <<<'SQL'
CREATE TABLE IF NOT EXISTS example_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL
);
SQL);

        try {
            $result = (new MigrationRunner($pdo))->runPending($dir);

            $this->assertSame(['001_example.sql'], $result['applied']);
            $this->assertSame([], $result['repaired']);
            $this->assertNotFalse($pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'example_items'")->fetch());
        } finally {
            @unlink($dir . '/000_schema_migrations.sql');
            @unlink($dir . '/001_example.sql');
            @rmdir($dir);
        }
    }

    public function testRunPendingRepairsMissingTablesForRecordedMigration(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $dir = sys_get_temp_dir() . '/cashback-migrations-' . bin2hex(random_bytes(4));
        mkdir($dir);

        file_put_contents($dir . '/000_schema_migrations.sql', <<<'SQL'
CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(255) PRIMARY KEY,
  applied_at DATETIME NOT NULL
);
SQL);
        file_put_contents($dir . '/001_example.sql', <<<'SQL'
CREATE TABLE IF NOT EXISTS example_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL
);
SQL);

        try {
            $pdo->exec(file_get_contents($dir . '/000_schema_migrations.sql'));
            $pdo->exec("INSERT INTO schema_migrations (version, applied_at) VALUES ('001_example.sql', '2026-01-01 00:00:00')");

            $result = (new MigrationRunner($pdo))->runPending($dir);

            $this->assertSame([], $result['applied']);
            $this->assertSame(['001_example.sql'], $result['repaired']);
            $this->assertNotFalse($pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'example_items'")->fetch());
        } finally {
            @unlink($dir . '/000_schema_migrations.sql');
            @unlink($dir . '/001_example.sql');
            @rmdir($dir);
        }
    }
}
