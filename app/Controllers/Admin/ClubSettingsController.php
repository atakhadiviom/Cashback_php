<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\CashbackSettingsRepository;
use App\Services\ActivityLogger;

final class ClubSettingsController
{
    public function edit(): void
    {
        View::render('admin/club_settings', [
            'options' => (new CashbackSettingsRepository())->clubOptions(),
        ]);
    }

    public function update(): void
    {
        Csrf::requireValid();
        $repository = new CashbackSettingsRepository();

        $tierBasis = (string) ($_POST['tier_basis'] ?? 'lifetime');
        if (!in_array($tierBasis, ['lifetime', 'rolling'], true)) {
            $tierBasis = 'lifetime';
        }

        $payload = [
            'enabled' => isset($_POST['enabled']),
            'tier_basis' => $tierBasis,
            'tier_window_months' => (int) \normalize_digits((string) ($_POST['tier_window_months'] ?? 12)),
            'tier_downgrade_grace_days' => (int) \normalize_digits((string) ($_POST['tier_downgrade_grace_days'] ?? 30)),
            'tier_change_sms' => isset($_POST['tier_change_sms']),
            'tier_change_template' => trim((string) ($_POST['tier_change_template'] ?? '')),
            'campaign_daily_limit' => (int) \normalize_digits((string) ($_POST['campaign_daily_limit'] ?? 200)),
            'campaign_min_interval_days' => (int) \normalize_digits((string) ($_POST['campaign_min_interval_days'] ?? 7)),
            'rfm' => [
                'r' => $this->band($_POST, 'rfm_r'),
                'f' => $this->band($_POST, 'rfm_f'),
                'm' => $this->band($_POST, 'rfm_m', true),
                'refresh_hours' => (int) \normalize_digits((string) ($_POST['rfm_refresh_hours'] ?? 24)),
            ],
        ];

        if (!$repository->updateClubOptions($payload)) {
            Flash::set('danger', 'ذخیره تنظیمات باشگاه ممکن نشد؛ ابتدا مهاجرت ۰۷۳ را اجرا کنید.');
            \redirect('/admin/club');
            return;
        }

        (new ActivityLogger())->log('settings_update', 'تنظیمات باشگاه مشتریان به‌روزرسانی شد.');
        Flash::set('success', 'تنظیمات باشگاه مشتریان ذخیره شد.');
        \redirect('/admin/club');
    }

    /**
     * @param array<string, mixed> $source
     * @return list<float>
     */
    private function band(array $source, string $prefix, bool $money = false): array
    {
        $values = [];
        for ($i = 1; $i <= 4; $i++) {
            $raw = (string) ($source[$prefix . $i] ?? '0');
            $values[] = $money
                ? (float) \parse_money_input($raw)
                : (float) \normalize_digits($raw);
        }
        sort($values);

        return $values;
    }
}
