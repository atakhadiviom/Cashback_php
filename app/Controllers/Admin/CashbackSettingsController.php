<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CashbackSettingsRepository;
use App\Services\ActivityLogger;

final class CashbackSettingsController
{
    public function edit(): void
    {
        View::render('admin/cashback_settings', ['settings' => (new CashbackSettingsRepository())->settings()]);
    }

    public function update(): void
    {
        Csrf::requireValid();
        $nullableFloat = static fn ($v): ?float => trim((string) $v) === '' ? null : (float) str_replace(',', '', \normalize_digits((string) $v));

        (new CashbackSettingsRepository())->update([
            'cashback_percent' => (float) str_replace(',', '', \normalize_digits((string) ($_POST['cashback_percent'] ?? '5'))),
            'min_purchase_amount' => $nullableFloat($_POST['min_purchase_amount'] ?? ''),
            'max_cashback_per_purchase' => $nullableFloat($_POST['max_cashback_per_purchase'] ?? ''),
            'min_redemption_amount' => $nullableFloat($_POST['min_redemption_amount'] ?? ''),
            'max_redemption_percent_of_purchase' => $nullableFloat($_POST['max_redemption_percent_of_purchase'] ?? ''),
            'large_redemption_threshold' => $nullableFloat($_POST['large_redemption_threshold'] ?? ''),
            'birthday_bonus_amount' => (float) str_replace(',', '', \normalize_digits((string) ($_POST['birthday_bonus_amount'] ?? '0'))),
            'referral_bonus_amount' => (float) str_replace(',', '', \normalize_digits((string) ($_POST['referral_bonus_amount'] ?? '0'))),
            'duplicate_purchase_window_minutes' => max(0, (int) ($_POST['duplicate_purchase_window_minutes'] ?? 5)),
            'updated_at' => \current_datetime(),
        ]);
        (new ActivityLogger())->log('settings_update', 'تنظیمات کش‌بک به‌روزرسانی شد.');
        Flash::set('success', 'تنظیمات کش‌بک ذخیره شد.');
        \redirect('/admin/cashback-settings');
    }
}
