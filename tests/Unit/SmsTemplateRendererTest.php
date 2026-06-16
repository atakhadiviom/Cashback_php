<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\SmsTemplateRenderer;
use PHPUnit\Framework\TestCase;

final class SmsTemplateRendererTest extends TestCase
{
    private array $previousConfig;

    protected function setUp(): void
    {
        $this->previousConfig = $GLOBALS['config'] ?? [];
        $GLOBALS['config']['app']['company_name'] = 'Cashback Co';
    }

    protected function tearDown(): void
    {
        $GLOBALS['config'] = $this->previousConfig;
    }

    public function testRenderReplacesCustomerMoneyAndCompanyPlaceholders(): void
    {
        $message = (new SmsTemplateRenderer())->render(
            'سلام {full_name} از {company_name}: خرید {purchase_amount} کش‌بک {cashback_amount} موجودی {wallet_balance}',
            ['first_name' => 'Ali', 'last_name' => 'Ahmadi', 'wallet_balance' => 2500000],
            ['purchase_amount' => 1000000, 'cashback_amount' => 50000]
        );

        $this->assertSame('سلام Ali Ahmadi از Cashback Co: خرید 1,000,000 کش‌بک 50,000 موجودی 2,500,000', $message);
    }

    public function testRenderSupportsOtpCode(): void
    {
        $message = (new SmsTemplateRenderer())->render(
            'کد ورود: {otp_code}',
            ['first_name' => 'Ali', 'last_name' => 'Ahmadi'],
            ['otp_code' => 123456]
        );

        $this->assertSame('کد ورود: 123456', $message);
    }
}
