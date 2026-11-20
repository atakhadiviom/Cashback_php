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

    public function updateTier(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $repository = new TierRepository();
        if ($id <= 0 || $repository->find($id) === null || $name === '') {
            Flash::set('danger', 'داده‌های سطح معتبر نیست.');
            \redirect('/admin/loyalty');
            return;
        }

        $repository->update($id, [
            'name' => mb_substr($name, 0, 60),
            'min_lifetime_spend' => $this->amount($_POST['min_lifetime_spend'] ?? '0'),
            'max_lifetime_spend' => trim((string) ($_POST['max_lifetime_spend'] ?? '')) === '' ? null : $this->amount($_POST['max_lifetime_spend']),
            'cashback_percent' => $this->amount($_POST['cashback_percent'] ?? '0'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int) \normalize_digits((string) ($_POST['sort_order'] ?? 0)),
        ]);
        (new \App\Services\ActivityLogger())->log('customer_tier_update', 'سطح مشتری ویرایش شد: ' . $name);
        Flash::set('success', 'سطح مشتری ویرایش شد.');
        \redirect('/admin/loyalty');
    }

    public function deleteTier(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $repository = new TierRepository();
        if ($id <= 0 || $repository->find($id) === null) {
            Flash::set('danger', 'سطح یافت نشد.');
            \redirect('/admin/loyalty');
            return;
        }

        $affected = $repository->customerCount($id);
        $repository->delete($id);
        (new \App\Services\ActivityLogger())->log('customer_tier_update', 'سطح مشتری حذف شد و ' . $affected . ' مشتری بدون سطح شدند.');
        Flash::set('success', 'سطح حذف شد' . ($affected > 0 ? ' و ' . $affected . ' مشتری بدون سطح شدند.' : '.'));
        \redirect('/admin/loyalty');
    }

    public function updatePromotion(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $repository = new PromotionRepository();
        if ($id <= 0 || $repository->find($id) === null || $name === '') {
            Flash::set('danger', 'داده‌های پروموشن معتبر نیست.');
            \redirect('/admin/loyalty');
            return;
        }

        $repository->update($id, [
            'name' => mb_substr($name, 0, 80),
            'percent_bonus' => $this->amount($_POST['percent_bonus'] ?? '0'),
            'fixed_bonus' => trim((string) ($_POST['fixed_bonus'] ?? '')) === '' ? null : $this->amount($_POST['fixed_bonus']),
            'starts_at' => (string) ($_POST['starts_at'] ?? \current_datetime()),
            'ends_at' => (string) ($_POST['ends_at'] ?? \current_datetime()),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        Flash::set('success', 'پروموشن ویرایش شد.');
        \redirect('/admin/loyalty');
    }

    public function deletePromotion(): void
    {
        Csrf::requireValid();
        (new PromotionRepository())->delete((int) ($_POST['id'] ?? 0));
        Flash::set('success', 'پروموشن حذف شد.');
        \redirect('/admin/loyalty');
    }

    public function recalculate(): void
    {
        Csrf::requireValid();
        $result = (new \App\Services\CustomerTierService())->recomputeAll();
        (new \App\Services\ActivityLogger())->log(
            'customer_tier_update',
            'بازمحاسبه سطح همه مشتریان: ' . $result['changed'] . ' تغییر از ' . $result['processed'] . ' مشتری.'
        );
        Flash::set('success', 'بازمحاسبه انجام شد: ' . $result['changed'] . ' سطح تغییر کرد (از ' . $result['processed'] . ' مشتری).');
        \redirect('/admin/loyalty');
    }

    private function amount(mixed $value): float
    {
        return (float) str_replace(',', '', \normalize_digits((string) $value));
    }

    public function storeTier(): void
    {
        Csrf::requireValid();
        (new TierRepository())->create([
            'name' => trim((string) ($_POST['name'] ?? '')),
            'min_lifetime_spend' => (float) str_replace(',', '', \normalize_digits((string) ($_POST['min_lifetime_spend'] ?? '0'))),
            'max_lifetime_spend' => trim((string) ($_POST['max_lifetime_spend'] ?? '')) === '' ? null : (float) str_replace(',', '', \normalize_digits((string) $_POST['max_lifetime_spend'])),
            'cashback_percent' => (float) str_replace(',', '', \normalize_digits((string) ($_POST['cashback_percent'] ?? '5'))),
            'is_active' => isset($_POST['is_active']) ? 1 : 1,
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
            'starts_at' => \App\Core\Jalali::parseDateTimeInput((string) ($_POST['starts_at'] ?? '')) ?: \current_datetime(),
            'ends_at' => \App\Core\Jalali::parseDateTimeInput((string) ($_POST['ends_at'] ?? '')) ?: \current_datetime(),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_at' => \current_datetime(),
        ]);
        Flash::set('success', 'پروموشن اضافه شد.');
        \redirect('/admin/loyalty');
    }
}
