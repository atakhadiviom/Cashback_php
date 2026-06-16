<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HelperFunctionsTest extends TestCase
{
    private array $previousConfig;
    private array $previousServer;

    protected function setUp(): void
    {
        $this->previousConfig = $GLOBALS['config'] ?? [];
        $this->previousServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $GLOBALS['config'] = $this->previousConfig;
        $_SERVER = $this->previousServer;
    }

    public function testNormalizeDigitsConvertsPersianAndArabicDigits(): void
    {
        $this->assertSame('0123456789', \normalize_digits('۰۱۲۳۴۵۶۷۸۹'));
        $this->assertSame('0123456789', \normalize_digits('٠١٢٣٤٥٦٧٨٩'));
    }

    public function testMoneyFormatsAmountsWithoutDecimals(): void
    {
        $this->assertSame('1,234,568', \money(1234567.89));
    }

    public function testConfigValueReadsNestedKeysAndDefaults(): void
    {
        $GLOBALS['config'] = ['app' => ['name' => 'Cashback']];

        $this->assertSame('Cashback', \config_value('app.name'));
        $this->assertSame('fallback', \config_value('app.missing', 'fallback'));
    }

    public function testUrlUsesConfiguredBaseUrl(): void
    {
        $GLOBALS['config'] = ['app' => ['base_url' => '/panel']];

        $this->assertSame('/panel/customers', \url('/customers'));
    }
}
