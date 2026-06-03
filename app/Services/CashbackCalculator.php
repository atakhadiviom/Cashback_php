<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CashbackSettingsRepository;
use App\Repositories\PromotionRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\TierRepository;

final class CashbackCalculator
{
    /**
     * @return array{cashback: float, percent_applied: float, promotion_id: ?int, errors: array<string, string>}
     */
    public function calculate(float $amount, ?array $customer = null): array
    {
        $settings = (new CashbackSettingsRepository())->settings();
        $errors = [];

        $minPurchase = isset($settings['min_purchase_amount']) ? (float) $settings['min_purchase_amount'] : null;
        if ($minPurchase !== null && $minPurchase > 0 && $amount < $minPurchase) {
            $errors['amount'] = 'حداقل مبلغ خرید برای کش‌بک ' . \money($minPurchase) . ' ریال است.';
            return ['cashback' => 0.0, 'percent_applied' => 0.0, 'promotion_id' => null, 'errors' => $errors];
        }

        $percent = (float) ($settings['cashback_percent'] ?? 5.0);
        if ($customer) {
            $lifetime = (new PurchaseRepository())->lifetimeSpend((int) $customer['id']);
            $tier = (new TierRepository())->forLifetimeSpend($lifetime);
            if ($tier) {
                $percent = (float) $tier['cashback_percent'];
            }
        }

        $cashback = round($amount * ($percent / 100), 2);
        $promotionId = null;
        $promo = (new PromotionRepository())->activeNow();
        if ($promo) {
            $promotionId = (int) $promo['id'];
            $bonusPercent = (float) $promo['percent_bonus'];
            if ($bonusPercent > 0) {
                $cashback = round($cashback + ($amount * ($bonusPercent / 100)), 2);
            }
            if (!empty($promo['fixed_bonus'])) {
                $cashback = round($cashback + (float) $promo['fixed_bonus'], 2);
            }
        }

        $maxCashback = isset($settings['max_cashback_per_purchase']) ? (float) $settings['max_cashback_per_purchase'] : null;
        if ($maxCashback !== null && $maxCashback > 0 && $cashback > $maxCashback) {
            $cashback = $maxCashback;
        }

        return [
            'cashback' => $cashback,
            'percent_applied' => $percent,
            'promotion_id' => $promotionId,
            'errors' => $errors,
        ];
    }
}
