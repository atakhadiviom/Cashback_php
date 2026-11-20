<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CashbackSettingsRepository;

/**
 * The system name shown in the sidebar, the browser title and the login page.
 * A value saved from the settings page wins over the config file, so the
 * operator can rename the panel without touching cashback_config.php.
 */
final class BrandingService
{
    public const DEFAULT_NAME = 'سامانه پیگیری فروش و باشگاه مشتریان و کش بک';

    private static ?string $cached = null;

    public static function name(?array $settings = null): string
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        if ($settings !== null) {
            $fromDb = trim((string) ($settings['panel_title'] ?? ''));
        } else {
            try {
                $fromDb = trim((string) ((new CashbackSettingsRepository())->settings()['panel_title'] ?? ''));
            } catch (\Throwable) {
                $fromDb = '';
            }
        }

        $fromConfig = trim((string) \config_value('app.company_name', ''));
        if ($fromConfig === '') {
            $fromConfig = trim((string) \config_value('app.name', ''));
        }

        self::$cached = $fromDb !== '' ? $fromDb : ($fromConfig !== '' ? $fromConfig : self::DEFAULT_NAME);

        return self::$cached;
    }
}
