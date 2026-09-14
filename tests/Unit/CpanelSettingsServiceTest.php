<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CpanelSettingsService;
use PHPUnit\Framework\TestCase;

final class CpanelSettingsServiceTest extends TestCase
{
    private string $path;
    private array $previousConfig;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/cpanel_settings_test_' . bin2hex(random_bytes(4)) . '.php';
        $this->previousConfig = $GLOBALS['config'] ?? [];
        $GLOBALS['config']['cpanel'] = [
            'enabled' => false,
            'host' => 'config.example.com:2083',
            'username' => 'config_user',
            'api_token' => 'config_token',
            'password' => 'config_password',
            'domain' => 'config.example.com',
            'app_root' => '',
            'home_dir' => '',
            'php_path' => '/usr/local/bin/ea-php81',
        ];
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
        $GLOBALS['config'] = $this->previousConfig;
    }

    public function testAllFallsBackToConfigWhenNoOverlay(): void
    {
        $service = new CpanelSettingsService($this->path);
        $all = $service->all();

        $this->assertSame('config.example.com:2083', $all['host']);
        $this->assertSame('config_token', $all['api_token']);
        $this->assertFalse($all['enabled']);
    }

    public function testOverlayOverridesConfigOnMerge(): void
    {
        $service = new CpanelSettingsService($this->path);
        $service->save([
            'enabled' => true,
            'host' => 'overlay.example.com:2083',
            'username' => 'overlay_user',
            'api_token' => 'overlay_token',
            'password' => '',
            'domain' => 'overlay.example.com',
            'app_root' => '/home/overlay/app',
            'home_dir' => '',
            'php_path' => '/usr/local/bin/ea-php84',
        ]);

        // Reset runtime config to base values; overlay file should still win via all().
        $GLOBALS['config']['cpanel'] = [
            'enabled' => false,
            'host' => 'config.example.com:2083',
            'username' => 'config_user',
            'api_token' => 'config_token',
            'password' => 'config_password',
            'domain' => 'config.example.com',
            'app_root' => '',
            'home_dir' => '',
            'php_path' => '/usr/local/bin/ea-php81',
        ];

        $all = $service->all();
        $this->assertTrue($all['enabled']);
        $this->assertSame('overlay.example.com:2083', $all['host']);
        $this->assertSame('overlay_user', $all['username']);
        $this->assertSame('overlay_token', $all['api_token']);
        $this->assertSame('/home/overlay/app', $all['app_root']);
        $this->assertSame('/usr/local/bin/ea-php84', $all['php_path']);
        // Blank password on save keeps previous effective password (from config at save time).
        $this->assertSame('config_password', $all['password']);
    }

    public function testBlankTokenKeepsPreviousValue(): void
    {
        $service = new CpanelSettingsService($this->path);
        $service->save([
            'enabled' => true,
            'host' => 'host.example.com:2083',
            'username' => 'user1',
            'api_token' => 'secret_token_abc',
            'password' => 'secret_pass',
            'domain' => 'host.example.com',
            'app_root' => '',
            'home_dir' => '',
            'php_path' => '/usr/local/bin/ea-php81',
        ]);

        $service->save([
            'enabled' => true,
            'host' => 'host.example.com:2083',
            'username' => 'user1',
            'api_token' => '',
            'password' => '',
            'domain' => 'host.example.com',
            'app_root' => '/home/user1/app',
            'home_dir' => '',
            'php_path' => '/usr/local/bin/ea-php81',
        ]);

        $all = $service->all();
        $this->assertSame('secret_token_abc', $all['api_token']);
        $this->assertSame('secret_pass', $all['password']);
        $this->assertSame('/home/user1/app', $all['app_root']);
    }

    public function testSaveWritesPhpFileAndUpdatesRuntimeConfig(): void
    {
        $service = new CpanelSettingsService($this->path);
        $saved = $service->save([
            'enabled' => true,
            'host' => 'live.example.com:2083',
            'username' => 'live_user',
            'api_token' => 'live_token',
            'password' => '',
            'domain' => 'live.example.com',
            'app_root' => '',
            'home_dir' => '/home3/live_user',
            'php_path' => '',
        ]);

        $this->assertFileExists($this->path);
        $fromFile = require $this->path;
        $this->assertIsArray($fromFile);
        $this->assertTrue($fromFile['enabled']);
        $this->assertSame('live_token', $fromFile['api_token']);
        $this->assertSame('/usr/local/bin/ea-php81', $fromFile['php_path']);
        $this->assertSame('live_token', $GLOBALS['config']['cpanel']['api_token']);
        $this->assertSame($saved['username'], 'live_user');
    }

    public function testMaskedHintsReflectStoredSecrets(): void
    {
        $service = new CpanelSettingsService($this->path);
        $this->assertStringContainsString('وارد کنید', $service->maskedTokenHint());

        $service->save([
            'enabled' => true,
            'host' => 'h',
            'username' => 'u',
            'api_token' => 'tok',
            'password' => 'pwd',
            'domain' => 'd',
            'app_root' => '',
            'home_dir' => '',
            'php_path' => '/usr/local/bin/ea-php81',
        ]);

        $this->assertTrue($service->hasApiToken());
        $this->assertTrue($service->hasPassword());
        $this->assertStringContainsString('ذخیره شده', $service->maskedTokenHint());
        $this->assertStringContainsString('ذخیره شده', $service->maskedPasswordHint());
    }
}
