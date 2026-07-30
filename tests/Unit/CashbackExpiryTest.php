<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CashbackExpiryService;
use PHPUnit\Framework\TestCase;

/**
 * The FIFO allocation rule the whole cashback-expiry feature rests on: a redemption must
 * drain the soonest-to-expire lots first, and never draw more than a lot actually holds.
 */
final class CashbackExpiryTest extends TestCase
{
    /** @param list<array{id: int, remaining: float}> $lots */
    private function lots(array $lots): array
    {
        return $lots;
    }

    public function testRedemptionDrainsTheOldestLotFirst(): void
    {
        $allocations = CashbackExpiryService::allocate($this->lots([
            ['id' => 1, 'remaining' => 30_000.0],
            ['id' => 2, 'remaining' => 50_000.0],
        ]), 20_000.0);

        $this->assertSame([['lot_id' => 1, 'amount' => 20_000.0]], $allocations);
    }

    public function testRedemptionSpillsIntoTheNextLotWhenTheFirstRunsOut(): void
    {
        $allocations = CashbackExpiryService::allocate($this->lots([
            ['id' => 1, 'remaining' => 30_000.0],
            ['id' => 2, 'remaining' => 50_000.0],
            ['id' => 3, 'remaining' => 10_000.0],
        ]), 70_000.0);

        $this->assertSame([
            ['lot_id' => 1, 'amount' => 30_000.0],
            ['lot_id' => 2, 'amount' => 40_000.0],
        ], $allocations);
    }

    public function testAllocationNeverExceedsTheAvailableLotValue(): void
    {
        $allocations = CashbackExpiryService::allocate($this->lots([
            ['id' => 1, 'remaining' => 5_000.0],
            ['id' => 2, 'remaining' => 5_000.0],
        ]), 40_000.0);

        $total = array_sum(array_column($allocations, 'amount'));
        $this->assertSame(10_000.0, $total);
    }

    public function testEmptyLotsAreSkipped(): void
    {
        $allocations = CashbackExpiryService::allocate($this->lots([
            ['id' => 1, 'remaining' => 0.0],
            ['id' => 2, 'remaining' => 25_000.0],
        ]), 25_000.0);

        $this->assertSame([['lot_id' => 2, 'amount' => 25_000.0]], $allocations);
    }

    public function testNothingIsAllocatedForZeroOrNegativeAmounts(): void
    {
        $lots = $this->lots([['id' => 1, 'remaining' => 10_000.0]]);

        $this->assertSame([], CashbackExpiryService::allocate($lots, 0.0));
        $this->assertSame([], CashbackExpiryService::allocate($lots, -500.0));
    }

    public function testFractionalAmountsAreRoundedToRials(): void
    {
        $allocations = CashbackExpiryService::allocate($this->lots([
            ['id' => 1, 'remaining' => 33.333],
            ['id' => 2, 'remaining' => 100.0],
        ]), 50.005);

        $this->assertSame([
            ['lot_id' => 1, 'amount' => 33.33],
            ['lot_id' => 2, 'amount' => 16.68],
        ], $allocations);
        $this->assertSame(50.01, round(array_sum(array_column($allocations, 'amount')), 2));
    }

    /**
     * A partly-spent lot only exposes what is left, so a later redemption cannot
     * double-spend cashback that was already used.
     */
    public function testPartlySpentLotOnlyOffersItsRemainder(): void
    {
        $lot = ['id' => 1, 'amount' => 100_000.0, 'consumed_amount' => 60_000.0, 'expired_amount' => 0.0];
        $remaining = $lot['amount'] - $lot['consumed_amount'] - $lot['expired_amount'];

        $allocations = CashbackExpiryService::allocate([
            ['id' => 1, 'remaining' => $remaining],
            ['id' => 2, 'remaining' => 20_000.0],
        ], 50_000.0);

        $this->assertSame([
            ['lot_id' => 1, 'amount' => 40_000.0],
            ['lot_id' => 2, 'amount' => 10_000.0],
        ], $allocations);
    }
}
