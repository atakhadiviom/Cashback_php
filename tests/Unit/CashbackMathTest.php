<?php

declare(strict_types=1);

namespace Tests\Unit;

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
}
