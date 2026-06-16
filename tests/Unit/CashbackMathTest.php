<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CashbackCalculator;
use PHPUnit\Framework\TestCase;

final class CashbackMathTest extends TestCase
{
    public function testPercentCalculation(): void
    {
        $amount = 1_000_000.0;
        $percent = 5.0;
        $cashback = round($amount * ($percent / 100), 2);
        $this->assertSame(50_000.0, $cashback);
    }

    public function testMaxCap(): void
    {
        $cashback = 100_000.0;
        $max = 75_000.0;
        $this->assertSame($max, min($cashback, $max));
    }

    public function testRedemptionPercentCap(): void
    {
        $purchase = 200_000.0;
        $maxPercent = 50.0;
        $maxAllowed = round($purchase * ($maxPercent / 100), 2);
        $this->assertSame(100_000.0, $maxAllowed);
        $this->assertTrue(150_000.0 > $maxAllowed);
    }

    public function testBasePercentAppliesWhenOnlyDefaultTierMatches(): void
    {
        $calculator = new CashbackCalculator(
            static fn (): array => ['cashback_percent' => 7.5],
            static fn (int $customerId): float => 0.0,
            static fn (float $lifetime): ?array => [
                'min_lifetime_spend' => 0,
                'cashback_percent' => 5.0,
            ],
            static fn (): ?array => null
        );

        $result = $calculator->calculate(1_000_000.0, ['id' => 1]);

        $this->assertSame(75_000.0, $result['cashback']);
        $this->assertSame(7.5, $result['percent_applied']);
    }

    public function testHigherTierOverridesBasePercent(): void
    {
        $calculator = new CashbackCalculator(
            static fn (): array => ['cashback_percent' => 7.5],
            static fn (int $customerId): float => 10_000_000.0,
            static fn (float $lifetime): ?array => [
                'min_lifetime_spend' => 10_000_000,
                'cashback_percent' => 12.0,
            ],
            static fn (): ?array => null
        );

        $result = $calculator->calculate(1_000_000.0, ['id' => 1]);

        $this->assertSame(120_000.0, $result['cashback']);
        $this->assertSame(12.0, $result['percent_applied']);
    }

    public function testMinimumPurchaseAmountBlocksCashback(): void
    {
        $calculator = new CashbackCalculator(
            static fn (): array => ['cashback_percent' => 10.0, 'min_purchase_amount' => 500_000],
            static fn (int $customerId): float => 0.0,
            static fn (float $lifetime): ?array => null,
            static fn (): ?array => null
        );

        $result = $calculator->calculate(250_000.0, ['id' => 1]);

        $this->assertSame(0.0, $result['cashback']);
        $this->assertSame(0.0, $result['percent_applied']);
        $this->assertArrayHasKey('amount', $result['errors']);
    }

    public function testPromotionBonusesAndMaxCapAreApplied(): void
    {
        $calculator = new CashbackCalculator(
            static fn (): array => ['cashback_percent' => 10.0, 'max_cashback_per_purchase' => 150_000],
            static fn (int $customerId): float => 0.0,
            static fn (float $lifetime): ?array => null,
            static fn (): ?array => [
                'id' => 12,
                'percent_bonus' => 5.0,
                'fixed_bonus' => 25_000,
            ]
        );

        $result = $calculator->calculate(1_000_000.0, ['id' => 1]);

        $this->assertSame(150_000.0, $result['cashback']);
        $this->assertSame(10.0, $result['percent_applied']);
        $this->assertSame(12, $result['promotion_id']);
    }
}
