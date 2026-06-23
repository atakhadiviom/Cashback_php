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
        $nullableFloat = static fn ($v): ?float => trim((string) $v) === '' ? null : \parse_money_input($v);
        $now = \current_datetime();

        // Build enabled_menus JSON from the checkboxes
        $allMenuKeys = ['dashboard','customers','add_customer','purchases','services','followups','reminders','reports','sms_logs'];
        $enabled = [];
        foreach ($allMenuKeys as $key) {
            if (isset($_POST['menu_' . $key])) {
                $enabled[] = $key;
            }
        }
        // If nothing checked → null means "show all menus"
        $enabledMenusJson = empty($enabled) ? null : json_encode($enabled);

        $repo = new CashbackSettingsRepository();

        // Save the regular cashback/wallet fields
        $repo->update([
            'cashback_percent' => \parse_money_input($_POST['cashback_percent'] ?? '5'),
            'min_purchase_amount' => \parse_money_input($_POST['min_purchase_amount'] ?? '0'),
            'max_cashback_per_purchase' => $nullableFloat($_POST['max_cashback_per_purchase'] ?? ''),
            'min_redemption_amount' => $nullableFloat($_POST['min_redemption_amount'] ?? ''),
            'max_redemption_percent_of_purchase' => $nullableFloat($_POST['max_redemption_percent_of_purchase'] ?? ''),
            'large_redemption_threshold' => $nullableFloat($_POST['large_redemption_threshold'] ?? ''),
            'birthday_bonus_amount' => \parse_money_input($_POST['birthday_bonus_amount'] ?? '0'),
            'referral_bonus_amount' => \parse_money_input($_POST['referral_bonus_amount'] ?? '0'),
            'duplicate_purchase_window_minutes' => max(0, (int) ($_POST['duplicate_purchase_window_minutes'] ?? 5)),
            'updated_at' => $now,
        ]);

        // Try to save menu visibility
        $menusSaved = $repo->updateEnabledMenus($enabledMenusJson);

        (new ActivityLogger())->log('settings_update', 'تنظیمات کش‌بک به‌روزرسانی شد.');

        if ($menusSaved) {
            Flash::set('success', 'تنظیمات ذخیره شد. برای دیدن تغییرات منوها، صفحه را کامل رفرش کنید (Ctrl + Shift + R).');
        } else {
            Flash::set('warning', 'تنظیمات ذخیره شد، اما ستون کنترل منوها در دیتابیس وجود ندارد. لطفاً روی سرور دستور php database/migrate.php را اجرا کنید، سپس دوباره ذخیره کنید.');
        }

        \redirect('/admin/cashback-settings');
    }
}
