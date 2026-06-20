<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\CronStateRepository;
use PHPUnit\Framework\TestCase;

final class CronStateRepositoryTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/cron_state_test_' . bin2hex(random_bytes(4)) . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testShouldRunDailyWhenNeverRun(): void
    {
        $repo = new CronStateRepository($this->path);
        $this->assertTrue($repo->shouldRunDaily('birthday'));
    }

    public function testShouldNotRunDailyAfterMarkedToday(): void
    {
        $repo = new CronStateRepository($this->path);
        $repo->markRun('birthday', date('Y-m-d') . ' 08:00:00');
        $this->assertFalse($repo->shouldRunDaily('birthday'));
    }

    public function testShouldRunIntervalWhenNeverRun(): void
    {
        $repo = new CronStateRepository($this->path);
        $this->assertTrue($repo->shouldRunInterval('sms_retry', 15));
    }

    public function testShouldNotRunIntervalBeforeElapsed(): void
    {
        $repo = new CronStateRepository($this->path);
        $repo->markRun('sms_retry', date('Y-m-d H:i:s'));
        $this->assertFalse($repo->shouldRunInterval('sms_retry', 15));
    }
}
