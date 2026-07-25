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

            $insertSql = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
                ? 'INSERT OR IGNORE INTO schema_migrations (version, applied_at) VALUES (:version, :applied_at)'
                : 'INSERT IGNORE INTO schema_migrations (version, applied_at) VALUES (:version, :applied_at)';
            $stmt = $this->pdo->prepare($insertSql);
            $stmt->execute(['version' => $version, 'applied_at' => date('Y-m-d H:i:s')]);
            $newlyApplied[] = $version;

            // Reload because some migrations (e.g. 000a) may record other versions themselves.
            $applied = array_flip(
                $this->pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN)
            );
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
            foreach ($this->expandStatement($statement) as $expanded) {
                $this->execStatement($expanded);
            }
        }

        $this->verifyCreateTables($sql, $version);
    }

    private function executeCreateTableStatements(string $sql, string $version): void
    {
        foreach ($this->statements($sql) as $statement) {
            if (!preg_match('/^\s*CREATE\s+TABLE/i', $statement)) {
                continue;
            }
            $this->execStatement($statement);
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

    /**
     * Split multi-clause ALTER TABLE ... ADD ... statements so a partially-applied
     * migration can add only the missing columns/indexes.
     *
     * @return list<string>
     */
    private function expandStatement(string $statement): array
    {
        if (!preg_match('/^\s*ALTER\s+TABLE\s+(`?[A-Za-z0-9_]+`?)\s+(.*)$/is', $statement, $matches)) {
            return [$statement];
        }

        $table = $matches[1];
        $body = trim($matches[2]);
        if (!preg_match('/^ADD\b/i', $body)) {
            return [$statement];
        }

        $parts = $this->splitAlterAddClauses($body);
        if (count($parts) <= 1) {
            return [$statement];
        }

        return array_map(
            static fn (string $part): string => 'ALTER TABLE ' . $table . ' ' . $part,
            $parts
        );
    }

    /** @return list<string> */
    private function splitAlterAddClauses(string $body): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $length = strlen($body);

        for ($i = 0; $i < $length; $i++) {
            $char = $body[$i];
            if ($char === '(') {
                $depth++;
                $current .= $char;
                continue;
            }
            if ($char === ')') {
                $depth = max(0, $depth - 1);
                $current .= $char;
                continue;
            }
            if ($char === ',' && $depth === 0) {
                $rest = ltrim(substr($body, $i + 1));
                if (preg_match('/^ADD\b/i', $rest)) {
                    $parts[] = trim($current);
                    $current = '';
                    continue;
                }
            }
            $current .= $char;
        }

        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    private function execStatement(string $statement): void
    {
        try {
            $this->pdo->exec($statement);
        } catch (PDOException $exception) {
            if (!$this->isIgnorableSchemaError($exception)) {
                throw $exception;
            }
        }
    }

    private function isIgnorableSchemaError(PDOException $exception): bool
    {
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        // 1050 table exists, 1060 duplicate column, 1061 duplicate key/index name
        if (in_array($driverCode, [1050, 1060, 1061], true)) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'duplicate column')
            || str_contains($message, 'duplicate key name')
            || str_contains($message, 'already exists');
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
