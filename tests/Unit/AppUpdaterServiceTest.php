<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AppUpdaterService;
use PHPUnit\Framework\TestCase;

final class AppUpdaterServiceTest extends TestCase
{
    private array $previousConfig;

    protected function setUp(): void
    {
        $this->previousConfig = $GLOBALS['config'] ?? [];
        $GLOBALS['config']['updater'] = [
            'enabled' => false,
            'github_owner' => 'atakhadiviom',
            'github_repo' => 'Cashback_php',
            'branch' => 'main',
            'github_token' => '',
        ];
    }

    protected function tearDown(): void
    {
        $GLOBALS['config'] = $this->previousConfig;
    }

    public function testStatusReportsRemoteUpdateAvailable(): void
    {
        $status = (new AppUpdaterService(null, static fn (): string => '99.0.0'))->status();

        $this->assertSame('99.0.0', $status['remote_version']);
        $this->assertTrue($status['update_available']);
        $this->assertNull($status['remote_error']);
    }

    public function testStatusReportsUpToDateWhenRemoteMatchesInstalledVersion(): void
    {
        $installedVersion = \app_version();

        $status = (new AppUpdaterService(null, static fn (): string => $installedVersion))->status();

        $this->assertSame($installedVersion, $status['remote_version']);
        $this->assertFalse($status['update_available']);
        $this->assertNull($status['remote_error']);
    }

    public function testStatusCapturesRemoteVersionCheckErrors(): void
    {
        $status = (new AppUpdaterService(null, static function (): string {
            throw new \RuntimeException('network unavailable');
        }))->status();

        $this->assertNull($status['remote_version']);
        $this->assertFalse($status['update_available']);
        $this->assertSame('network unavailable', $status['remote_error']);
    }
}
