<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;
use Throwable;
use ZipArchive;

final class AppUpdaterService
{
    private string $rootPath;

    /** @var string[] */
    private array $protectedPaths = [
        '.git',
        '.env',
        'cashback_config.php',
        'config/config.php',
        'storage',
        'vendor',
    ];

    public function __construct(?string $rootPath = null)
    {
        $this->rootPath = rtrim($rootPath ?? dirname(__DIR__, 2), '/');
    }

    /** @return array<string, mixed> */
    public function status(): array
    {
        return [
            'enabled' => (bool) \config_value('updater.enabled', false),
            'owner' => (string) \config_value('updater.github_owner', ''),
            'repo' => (string) \config_value('updater.github_repo', ''),
            'branch' => (string) \config_value('updater.branch', 'main'),
            'zip_available' => class_exists(ZipArchive::class),
            'curl_available' => function_exists('curl_init'),
            'backup_dir' => $this->backupDir(),
        ];
    }

    /** @return array{ok: bool, messages: string[], backup: string|null} */
    public function updateFromMain(bool $runMigrations): array
    {
        $messages = [];
        $backupPath = null;
        $workspace = null;

        try {
            $this->assertReady();
            $workspace = $this->makeWorkspace();
            $zipPath = $workspace . '/source.zip';
            $extractPath = $workspace . '/extract';

            $messages[] = 'Downloading update package from GitHub main branch...';
            $this->downloadZip($zipPath);

            $messages[] = 'Extracting update package...';
            $sourceRoot = $this->extractZip($zipPath, $extractPath);

            $messages[] = 'Creating local backup before replacing files...';
            $backupPath = $this->createBackup();

            $messages[] = 'Copying updated application files...';
            $copied = $this->copyTree($sourceRoot, $this->rootPath);
            $messages[] = "Updated {$copied} files.";

            if ($runMigrations) {
                $messages[] = 'Running pending database migrations...';
                $migrationCount = $this->runMigrations();
                $messages[] = "Applied {$migrationCount} migrations.";
            }

            $messages[] = 'Update completed.';

            return ['ok' => true, 'messages' => $messages, 'backup' => $backupPath];
        } catch (Throwable $exception) {
            $messages[] = 'Update failed: ' . $exception->getMessage();
            return ['ok' => false, 'messages' => $messages, 'backup' => $backupPath];
        } finally {
            if (is_string($workspace)) {
                $this->removeDirectory($workspace);
            }
        }
    }

    private function assertReady(): void
    {
        if (!(bool) \config_value('updater.enabled', false)) {
            throw new RuntimeException('Updater is disabled in config.');
        }
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is not available on this hosting account.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is not available on this hosting account.');
        }
        if ($this->githubOwner() === '' || $this->githubRepo() === '') {
            throw new RuntimeException('GitHub owner/repo is not configured.');
        }
        if (!is_writable($this->rootPath)) {
            throw new RuntimeException('Application folder is not writable.');
        }
        if (!is_dir($this->backupDir()) && !mkdir($this->backupDir(), 0755, true) && !is_dir($this->backupDir())) {
            throw new RuntimeException('Could not create backup folder.');
        }
        if (!is_writable($this->backupDir())) {
            throw new RuntimeException('Backup folder is not writable.');
        }
    }

    private function downloadZip(string $targetPath): void
    {
        $url = sprintf(
            'https://codeload.github.com/%s/%s/zip/refs/heads/%s',
            rawurlencode($this->githubOwner()),
            rawurlencode($this->githubRepo()),
            rawurlencode($this->branch())
        );

        $handle = fopen($targetPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Could not create temporary ZIP file.');
        }

        $curl = curl_init($url);
        if ($curl === false) {
            fclose($handle);
            throw new RuntimeException('Could not initialize cURL.');
        }

        $headers = ['User-Agent: Cashback-App-Updater'];
        $token = trim((string) \config_value('updater.github_token', ''));
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($curl, [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 20,
        ]);

        $ok = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        fclose($handle);

        if ($ok !== true || $statusCode < 200 || $statusCode >= 300) {
            @unlink($targetPath);
            throw new RuntimeException('GitHub download failed' . ($error !== '' ? ': ' . $error : " with HTTP {$statusCode}."));
        }
        if (!is_file($targetPath) || filesize($targetPath) < 1000) {
            @unlink($targetPath);
            throw new RuntimeException('Downloaded update package is empty or invalid.');
        }
    }

    private function extractZip(string $zipPath, string $extractPath): string
    {
        if (!mkdir($extractPath, 0755, true) && !is_dir($extractPath)) {
            throw new RuntimeException('Could not create extraction folder.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not open downloaded ZIP package.');
        }
        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new RuntimeException('Could not extract downloaded ZIP package.');
        }
        $zip->close();

        $entries = array_values(array_filter(glob($extractPath . '/*') ?: [], 'is_dir'));
        if (count($entries) !== 1) {
            throw new RuntimeException('Unexpected GitHub ZIP structure.');
        }
        return $entries[0];
    }

    private function createBackup(): string
    {
        $backupPath = $this->backupDir() . '/cashback-backup-' . date('Ymd-His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create backup ZIP.');
        }

        $this->addDirectoryToZip($zip, $this->rootPath, '');
        $zip->close();

        return $backupPath;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $relativeBase): void
    {
        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            $relative = ltrim($relativeBase . '/' . $item, '/');
            if ($this->isProtectedPath($relative) || str_starts_with($relative, 'storage/backups/')) {
                continue;
            }

            if (is_dir($path)) {
                $this->addDirectoryToZip($zip, $path, $relative);
            } elseif (is_file($path)) {
                $zip->addFile($path, $relative);
            }
        }
    }

    private function copyTree(string $source, string $destination): int
    {
        $copied = 0;
        $items = scandir($source);
        if ($items === false) {
            throw new RuntimeException('Could not read extracted update files.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $item;
            $targetPath = $destination . '/' . $item;
            $relative = ltrim(substr($targetPath, strlen($this->rootPath)), '/');
            if ($this->isProtectedPath($relative)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new RuntimeException("Could not create folder {$relative}.");
                }
                $copied += $this->copyTree($sourcePath, $targetPath);
                continue;
            }

            if (!is_file($sourcePath)) {
                continue;
            }
            if (!is_dir(dirname($targetPath)) && !mkdir(dirname($targetPath), 0755, true) && !is_dir(dirname($targetPath))) {
                throw new RuntimeException("Could not create folder for {$relative}.");
            }
            if (!copy($sourcePath, $targetPath)) {
                throw new RuntimeException("Could not update {$relative}.");
            }
            @chmod($targetPath, 0644);
            $copied++;
        }

        return $copied;
    }

    private function runMigrations(): int
    {
        $migrationDir = $this->rootPath . '/database/migrations';
        $files = glob($migrationDir . '/*.sql') ?: [];
        sort($files);

        $schemaFile = $migrationDir . '/000_schema_migrations.sql';
        if (!is_file($schemaFile)) {
            return 0;
        }

        $pdo = Database::pdo();
        $pdo->exec((string) file_get_contents($schemaFile));

        $applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(\PDO::FETCH_COLUMN);
        $applied = array_flip($applied);
        $count = 0;

        foreach ($files as $file) {
            $version = basename($file);
            if ($version === '000_schema_migrations.sql' || isset($applied[$version])) {
                continue;
            }

            $sql = (string) file_get_contents($file);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }

            $stmt = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (:version, :applied_at)');
            $stmt->execute(['version' => $version, 'applied_at' => date('Y-m-d H:i:s')]);
            $count++;
        }

        return $count;
    }

    private function makeWorkspace(): string
    {
        $base = $this->rootPath . '/storage/updater';
        if (!is_dir($base) && !mkdir($base, 0755, true) && !is_dir($base)) {
            throw new RuntimeException('Could not create updater workspace.');
        }

        $workspace = $base . '/' . bin2hex(random_bytes(8));
        if (!mkdir($workspace, 0755, true) && !is_dir($workspace)) {
            throw new RuntimeException('Could not create updater workspace.');
        }
        return $workspace;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($directory);
    }

    private function isProtectedPath(string $relativePath): bool
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        foreach ($this->protectedPaths as $protectedPath) {
            if ($relativePath === $protectedPath || str_starts_with($relativePath, $protectedPath . '/')) {
                return true;
            }
        }
        return false;
    }

    private function backupDir(): string
    {
        return $this->rootPath . '/storage/backups';
    }

    private function githubOwner(): string
    {
        return trim((string) \config_value('updater.github_owner', ''));
    }

    private function githubRepo(): string
    {
        return trim((string) \config_value('updater.github_repo', ''));
    }

    private function branch(): string
    {
        return trim((string) \config_value('updater.branch', 'main')) ?: 'main';
    }
}
