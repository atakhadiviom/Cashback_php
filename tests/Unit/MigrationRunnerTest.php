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

    public function testRunPendingIgnoresDuplicateColumnErrors(): void
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
        file_put_contents($dir . '/001_add_column.sql', <<<'SQL'
ALTER TABLE items ADD COLUMN note TEXT;
SQL);

        try {
            $pdo->exec(file_get_contents($dir . '/000_schema_migrations.sql'));
            $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY, note TEXT)');

            $result = (new MigrationRunner($pdo))->runPending($dir);

            $this->assertSame(['001_add_column.sql'], $result['applied']);
            $versions = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
            $this->assertSame(['001_add_column.sql'], $versions);
        } finally {
            @unlink($dir . '/000_schema_migrations.sql');
            @unlink($dir . '/001_add_column.sql');
            @rmdir($dir);
        }
    }

    public function testRunPendingReloadsVersionsRecordedInsideMigrationSql(): void
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
        file_put_contents($dir . '/000a_sync.sql', <<<'SQL'
INSERT OR IGNORE INTO schema_migrations (version, applied_at) VALUES ('001_example.sql', '2026-01-01 00:00:00');
SQL);
        file_put_contents($dir . '/001_example.sql', <<<'SQL'
ALTER TABLE items ADD COLUMN extra TEXT NOT NULL DEFAULT '';
SQL);

        try {
            $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY)');
            $result = (new MigrationRunner($pdo))->runPending($dir);

            $this->assertSame(['000a_sync.sql'], $result['applied']);
            $versions = $pdo->query('SELECT version FROM schema_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN);
            $this->assertSame(['000a_sync.sql', '001_example.sql'], $versions);

            $cols = $pdo->query('PRAGMA table_info(items)')->fetchAll(PDO::FETCH_ASSOC);
            $colNames = array_column($cols, 'name');
            $this->assertNotContains('extra', $colNames);
        } finally {
            @unlink($dir . '/000_schema_migrations.sql');
            @unlink($dir . '/000a_sync.sql');
            @unlink($dir . '/001_example.sql');
            @rmdir($dir);
        }
    }
}
