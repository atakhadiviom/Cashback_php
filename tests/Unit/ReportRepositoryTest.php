<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\ReportRepository;
use PHPUnit\Framework\TestCase;

final class ReportRepositoryTest extends TestCase
{
    public function testPurchaseFiltersExcludeDeletedCustomersByDefault(): void
    {
        $repo = (new \ReflectionClass(ReportRepository::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(ReportRepository::class, 'purchaseFilters');
        $method->setAccessible(true);

        [$where] = $method->invoke($repo, []);

        $this->assertContains('c.deleted_at IS NULL', $where);
        $this->assertContains("p.status = 'active'", $where);
    }
}
