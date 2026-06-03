<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\AppSettingsRepository;
use App\Repositories\SmsRepository;

final class SettingsController
{
    public function edit(): void
    {
        View::render('admin/sms_settings', [
            'settings' => (new SmsRepository())->settings(),
            'cashbackPercentage' => (new AppSettingsRepository())->cashbackPercentage(),
        ]);
    }

    public function update(): void
    {
        Csrf::requireValid();
        $cashbackPercentage = (float) str_replace(',', '.', \normalize_digits((string) ($_POST['cashback_percentage'] ?? '5')));
        if ($cashbackPercentage < 0 || $cashbackPercentage > 100) {
            Flash::set('danger', 'درصد کش‌بک باید بین ۰ تا ۱۰۰ باشد.');
            \redirect('/admin/sms-settings');
        }

        (new AppSettingsRepository())->updateCashbackPercentage($cashbackPercentage);

        $repo = new SmsRepository();
        $token = trim((string) ($_POST['api_token'] ?? ''));
        $repo->updateSettings([
            'api_token' => $token,
            'sender_number' => trim((string) ($_POST['sender_number'] ?? '')),
            'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0,
            'purchase_sms_enabled' => isset($_POST['purchase_sms_enabled']) ? 1 : 0,
            'birthday_sms_enabled' => isset($_POST['birthday_sms_enabled']) ? 1 : 0,
            'wallet_reduction_sms_enabled' => isset($_POST['wallet_reduction_sms_enabled']) ? 1 : 0,
            'welcome_sms_enabled' => isset($_POST['welcome_sms_enabled']) ? 1 : 0,
            'purchase_template' => (string) ($_POST['purchase_template'] ?? ''),
            'birthday_template' => (string) ($_POST['birthday_template'] ?? ''),
            'wallet_reduction_template' => (string) ($_POST['wallet_reduction_template'] ?? ''),
            'welcome_template' => (string) ($_POST['welcome_template'] ?? ''),
            'updated_at' => \current_datetime(),
        ], $token !== '');
        Flash::set('success', 'تنظیمات ذخیره شد.');
        \redirect('/admin/sms-settings');
    }
}
