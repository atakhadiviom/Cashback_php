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

    public function testNormalizePersianTextUnifiesArabicAndPersianLetters(): void
    {
        $this->assertSame('ی', \normalize_persian_text('ي'));
        $this->assertSame('ک', \normalize_persian_text('ك'));
        $this->assertSame('نصرابادی', \normalize_persian_text('نصرآبادی'));
        $this->assertSame(
            \normalize_persian_text('شمس/سازندگان مشهد'),
            \normalize_persian_text('شمس/سازندگان مشهد')
        );
        $this->assertSame(
            \normalize_persian_text('يزدي'),
            \normalize_persian_text('یزدی')
        );
    }

    public function testSearchLikeTermNormalizesPersianQuery(): void
    {
        $this->assertSame('%یزدی%', \search_like_term('يزدي'));
    }

    public function testParseMoneyInputStripsGroupingAndPersianDigits(): void
    {
        $this->assertSame(1_000_000.0, \parse_money_input('1,000,000'));
        $this->assertSame(0.0, \parse_money_input(''));
        $this->assertSame(500_000.0, \parse_money_input('۵۰۰,۰۰۰'));
    }

    public function testMoneyInputValueFormatsDatabaseDecimalsSafely(): void
    {
        $this->assertSame('0', \money_input_value(0));
        $this->assertSame('0', \money_input_value('0.00'));
        $this->assertSame('1,000,000', \money_input_value('1000000.00'));
        $this->assertSame('', \money_input_value(null, ''));
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
