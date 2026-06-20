<?php

declare(strict_types=1);

namespace App\Repositories;

final class CronStateRepository
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/storage/cron_state.json';
    }

    public function lastRun(string $task): ?string
    {
        $state = $this->read();
        $value = $state[$task] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function markRun(string $task, ?string $at = null): void
    {
        $state = $this->read();
        $state[$task] = $at ?? \current_datetime();
        $this->write($state);
    }

    public function shouldRunDaily(string $task): bool
    {
        $last = $this->lastRun($task);
        if ($last === null) {
            return true;
        }
        return substr($last, 0, 10) !== date('Y-m-d');
    }

    public function shouldRunInterval(string $task, int $minutes): bool
    {
        $last = $this->lastRun($task);
        if ($last === null) {
            return true;
        }
        $elapsed = time() - strtotime($last);
        return $elapsed >= $minutes * 60;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->read();
    }

    /** @return array<string, string> */
    private function read(): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $json = file_get_contents($this->path);
        if ($json === false || trim($json) === '') {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, string> $state */
    private function write(array $state): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create cron state directory.');
        }
        file_put_contents($this->path, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
