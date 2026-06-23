<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\PromotionRepository;
use App\Repositories\TierRepository;

final class LoyaltyController
{
    public function index(): void
    {
        View::render('admin/loyalty', [
            'tiers' => (new TierRepository())->all(),
            'promotions' => (new PromotionRepository())->all(),
        ]);
    }

    public function storeTier(): void
    {
        Csrf::requireValid();
        (new TierRepository())->create([
            'name' => trim((string) ($_POST['name'] ?? '')),
            'min_lifetime_spend' => (float) str_replace(',', '', \normalize_digits((string) ($_POST['min_lifetime_spend'] ?? '0'))),
            'max_lifetime_spend' => trim((string) ($_POST['max_lifetime_spend'] ?? '')) === '' ? null : (float) str_replace(',', '', \normalize_digits((string) $_POST['max_lifetime_spend'])),
            'cashback_percent' => (float) str_replace(',', '', \normalize_digits((string) ($_POST['cashback_percent'] ?? '5'))),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'created_at' => \current_datetime(),
        ]);
        Flash::set('success', 'سطح مشتری اضافه شد.');
        \redirect('/admin/loyalty');
    }

    public function storePromotion(): void
    {
        Csrf::requireValid();
        (new PromotionRepository())->create([
            'name' => trim((string) ($_POST['name'] ?? '')),
            'percent_bonus' => (float) str_replace(',', '', \normalize_digits((string) ($_POST['percent_bonus'] ?? '0'))),
            'fixed_bonus' => trim((string) ($_POST['fixed_bonus'] ?? '')) === '' ? null : (float) str_replace(',', '', \normalize_digits((string) $_POST['fixed_bonus'])),
            'starts_at' => (string) ($_POST['starts_at'] ?? \current_datetime()),
            'ends_at' => (string) ($_POST['ends_at'] ?? \current_datetime()),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_at' => \current_datetime(),
        ]);
        Flash::set('success', 'پروموشن اضافه شد.');
        \redirect('/admin/loyalty');
    }
}
