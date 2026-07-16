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
    private ?\Closure $remoteVersionProvider;

    /** @var string[] */
    private array $protectedPaths = [
        '.git',
        '.env',
        'cashback_config.php',
        'config/config.php',
        'storage',
        'vendor',
    ];

    public function __construct(?string $rootPath = null, ?\Closure $remoteVersionProvider = null)
    {
        $this->rootPath = rtrim($rootPath ?? dirname(__DIR__, 2), '/');
        $this->remoteVersionProvider = $remoteVersionProvider;
    }

    /** @return array<string, mixed> */
    public function status(): array
    {
        $version = \app_version();
        $remoteVersion = null;
        $remoteError = null;

        try {
            $remoteVersion = $this->remoteVersion();
        } catch (Throwable $exception) {
            $remoteError = $exception->getMessage();
        }

        return [
            'enabled' => (bool) \config_value('updater.enabled', true),
            'owner' => (string) \config_value('updater.github_owner', ''),
            'repo' => (string) \config_value('updater.github_repo', ''),
            'branch' => (string) \config_value('updater.branch', 'main'),
            'version' => $version,
            'remote_version' => $remoteVersion,
            'remote_error' => $remoteError,
            'update_available' => is_string($remoteVersion) && version_compare($remoteVersion, $version, '>'),
            'zip_available' => class_exists(ZipArchive::class),
            'curl_available' => function_exists('curl_init'),
            'backup_dir' => $this->backupDir(),
        ];
    }

    /** @return array{ok: bool, messages: string[], backup: string|null} */
    public function updateFromMain(?bool $runMigrations = null, ?bool $setupCpanelCron = null): array
    {
        $runMigrations = $runMigrations ?? (bool) \config_value('updater.auto_run_migrations', true);
        $setupCpanelCron = $setupCpanelCron ?? (bool) \config_value('updater.auto_setup_cpanel_cron', true);
        $messages = [];
        $backupPath = null;
        $workspace = null;
        $filesCopied = false;

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ignore_user_abort(true);

        try {
            $this->assertReady();
            $workspace = $this->makeWorkspace();
            $zipPath = $workspace . '/source.zip';
            $extractPath = $workspace . '/extract';

            $messages[] = 'Downloading update package from GitHub ' . $this->branch() . ' branch...';
            $this->downloadZip($zipPath);

            $messages[] = 'Extracting update package...';
            $sourceRoot = $this->extractZip($zipPath, $extractPath);

            $messages[] = 'Creating local backup before replacing files...';
            $backupPath = $this->createBackup();

            if ($runMigrations) {
                $messages[] = 'Running pending database migrations from the update package (before copying files)...';
                $migrationResult = $this->runMigrations($sourceRoot . '/database/migrations');
                if ($migrationResult['applied'] !== []) {
                    $messages[] = 'Applied migrations: ' . implode(', ', $migrationResult['applied']) . '.';
                }
                if ($migrationResult['repaired'] !== []) {
                    $messages[] = 'Repaired migrations: ' . implode(', ', $migrationResult['repaired']) . '.';
                }
                if ($migrationResult['applied'] === [] && $migrationResult['repaired'] === []) {
                    $messages[] = 'Database schema is already up to date.';
                }
            } else {
                $messages[] = 'Database migrations were skipped. Enable the migration checkbox to apply schema changes safely before new code is deployed.';
            }

            $messages[] = 'Copying updated application files...';
            try {
                $copied = $this->copyTree($sourceRoot, $this->rootPath);
                $filesCopied = true;
                $messages[] = "Updated {$copied} files.";
            } catch (Throwable $copyException) {
                if (is_string($backupPath)) {
                    $messages[] = 'File copy failed. Restoring previous application files from backup...';
                    $this->restoreFromBackup($backupPath);
                    $messages[] = 'Previous application files were restored from backup.';
                }
                throw $copyException;
            }

            if ($setupCpanelCron) {
                $messages[] = 'Ensuring cPanel cron jobs...';
                $cronResult = (new CpanelCronService())->ensureCronJobs();
                $messages[] = $cronResult['ok']
                    ? 'cPanel cron: ' . $cronResult['message']
                    : 'cPanel cron setup skipped or failed: ' . $cronResult['message'];
            }

            $messages[] = 'Update completed.';

            return ['ok' => true, 'messages' => $messages, 'backup' => $backupPath];
        } catch (Throwable $exception) {
            $messages[] = 'Update failed: ' . $exception->getMessage();
            if ($filesCopied) {
                $messages[] = 'Application files may have been partially updated. Backup: '
                    . ($backupPath ?? 'not available') . '.';
            } else {
                $messages[] = 'Application files were not replaced, so the live site should still run the previous version.';
            }
            if ($runMigrations && !$filesCopied) {
                $messages[] = 'If migrations ran successfully before the failure, the database may already include schema changes from the update package.';
            }
            return ['ok' => false, 'messages' => $messages, 'backup' => $backupPath];
        } finally {
            if (is_string($workspace)) {
                $this->removeDirectory($workspace);
            }
        }
    }

    private function assertReady(): void
    {
        if (!(bool) \config_value('updater.enabled', true)) {
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

    private function remoteVersion(): ?string
    {
        if ($this->remoteVersionProvider instanceof \Closure) {
            $version = trim((string) ($this->remoteVersionProvider)());
            return $version !== '' ? ltrim($version, "vV \t\n\r\0\x0B") : null;
        }

        if ($this->githubOwner() === '' || $this->githubRepo() === '') {
            return null;
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is not available on this hosting account.');
        }

        $url = sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/VERSION',
            rawurlencode($this->githubOwner()),
            rawurlencode($this->githubRepo()),
            rawurlencode($this->branch())
        );

        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Could not initialize cURL.');
        }

        $headers = ['User-Agent: Cashback-App-Updater'];
        $token = trim((string) \config_value('updater.github_token', ''));
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if (!is_string($body) || $statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('GitHub version check failed' . ($error !== '' ? ': ' . $error : " with HTTP {$statusCode}."));
        }

        $version = ltrim(trim($body), "vV \t\n\r\0\x0B");
        return $version !== '' ? $version : null;
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

    /** @return array{applied: list<string>, repaired: list<string>} */
    private function runMigrations(string $migrationDir): array
    {
        return (new MigrationRunner(Database::pdo()))->runPending($migrationDir);
    }

    private function restoreFromBackup(string $backupPath): void
    {
        if (!is_file($backupPath)) {
            throw new RuntimeException('Backup file is missing; could not restore previous application files.');
        }

        $zip = new ZipArchive();
        if ($zip->open($backupPath) !== true) {
            throw new RuntimeException('Could not open backup ZIP for restore.');
        }

        $workspace = $this->rootPath . '/storage/updater/restore-' . bin2hex(random_bytes(4));
        if (!mkdir($workspace, 0755, true) && !is_dir($workspace)) {
            $zip->close();
            throw new RuntimeException('Could not create restore workspace.');
        }

        try {
            if (!$zip->extractTo($workspace)) {
                throw new RuntimeException('Could not extract backup ZIP for restore.');
            }
            $zip->close();
            $this->copyTree($workspace, $this->rootPath);
        } finally {
            $this->removeDirectory($workspace);
        }
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
