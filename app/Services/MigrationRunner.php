<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{applied: list<string>, repaired: list<string>}
     */
    public function runPending(string $migrationDir): array
    {
        $migrationDir = rtrim($migrationDir, '/');
        $files = glob($migrationDir . '/*.sql') ?: [];
        sort($files);

        $schemaFile = $migrationDir . '/000_schema_migrations.sql';
        if (!is_file($schemaFile)) {
            return ['applied' => [], 'repaired' => []];
        }

        $this->pdo->exec((string) file_get_contents($schemaFile));

        $applied = $this->pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $applied = array_flip($applied);

        $newlyApplied = [];
        $repaired = [];

        foreach ($files as $file) {
            $version = basename($file);
            if ($version === '000_schema_migrations.sql') {
                continue;
            }

            $sql = (string) file_get_contents($file);

            if (isset($applied[$version])) {
                if ($this->needsCreateTableRepair($sql)) {
                    $this->executeCreateTableStatements($sql, $version);
                    $repaired[] = $version;
                }
                continue;
            }

            try {
                $this->executeMigration($sql, $version);
            } catch (PDOException $exception) {
                throw new RuntimeException(
                    "Migration {$version} failed: " . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO schema_migrations (version, applied_at) VALUES (:version, :applied_at)'
            );
            $stmt->execute(['version' => $version, 'applied_at' => date('Y-m-d H:i:s')]);
            $newlyApplied[] = $version;
        }

        return ['applied' => $newlyApplied, 'repaired' => $repaired];
    }

    private function needsCreateTableRepair(string $sql): bool
    {
        foreach ($this->createTablesInMigration($sql) as $table) {
            if (!$this->tableExists($table)) {
                return true;
            }
        }

        return false;
    }

    private function executeMigration(string $sql, string $version): void
    {
        foreach ($this->statements($sql) as $statement) {
            $this->pdo->exec($statement);
        }

        $this->verifyCreateTables($sql, $version);
    }

    private function executeCreateTableStatements(string $sql, string $version): void
    {
        foreach ($this->statements($sql) as $statement) {
            if (!preg_match('/^\s*CREATE\s+TABLE/i', $statement)) {
                continue;
            }
            $this->pdo->exec($statement);
        }

        $this->verifyCreateTables($sql, $version);
    }

    private function verifyCreateTables(string $sql, string $version): void
    {
        foreach ($this->createTablesInMigration($sql) as $table) {
            if (!$this->tableExists($table)) {
                throw new RuntimeException("Migration {$version} verification failed: table {$table} was not created.");
            }
        }
    }

    /** @return list<string> */
    private function createTablesInMigration(string $sql): array
    {
        if (!preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i', $sql, $matches)) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }

    /** @return list<string> */
    private function statements(string $sql): array
    {
        return array_values(array_filter(array_map('trim', explode(';', $sql)), static fn (string $s): bool => $s !== ''));
    }

    private function tableExists(string $table): bool
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $stmt = $this->pdo->query(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = " . $this->pdo->quote($table)
            );

            return (bool) ($stmt?->fetch());
        }

        $stmt = $this->pdo->query('SHOW TABLES LIKE ' . $this->pdo->quote($table));

        return (bool) ($stmt?->fetch());
    }
}
