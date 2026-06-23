<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\TierRepository;

final class CustomerTierService
{
    private TierRepository $tiers;
    private PurchaseRepository $purchases;
    private CustomerRepository $customers;

    public function __construct()
    {
        $this->tiers = new TierRepository();
        $this->purchases = new PurchaseRepository();
        $this->customers = new CustomerRepository();
    }

    public function recalculateForCustomer(int $customerId): ?int
    {
        $lifetimeSpend = $this->purchases->lifetimeSpend($customerId);
        $tier = $this->tiers->findTierBySpend($lifetimeSpend);

        if ($tier) {
            $this->customers->updateTier($customerId, (int) $tier['id']);
            (new ActivityLogger())->log('customer_tier_update', 'سطح مشتری به ' . $tier['name'] . ' تغییر کرد.', $customerId);
        }

        return $tier ? (int) $tier['id'] : null;
    }

    public static function tierLabel(?string $tierName): string
    {
        return $tierName ?? 'نامشخص';
    }
}
