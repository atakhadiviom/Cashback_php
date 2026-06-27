<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\SmsRepository;

final class SettingsController
{
    public function edit(): void
    {
        View::render('admin/sms_settings', ['settings' => (new SmsRepository())->settings()]);
    }

    public function update(): void
    {
        Csrf::requireValid();
        $repo = new SmsRepository();
        $token = trim((string) ($_POST['api_token'] ?? ''));
        $senderNumber = \normalize_digits(trim((string) ($_POST['sender_number'] ?? '')));
        $repo->updateSettings([
            'api_token' => $token,
            'sender_number' => $senderNumber,
            'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0,
            'purchase_sms_enabled' => isset($_POST['purchase_sms_enabled']) ? 1 : 0,
            'birthday_sms_enabled' => isset($_POST['birthday_sms_enabled']) ? 1 : 0,
            'wallet_reduction_sms_enabled' => isset($_POST['wallet_reduction_sms_enabled']) ? 1 : 0,
            'welcome_sms_enabled' => isset($_POST['welcome_sms_enabled']) ? 1 : 0,
            'service_sms_enabled' => isset($_POST['service_sms_enabled']) ? 1 : 0,
            'contract_renewal_sms_enabled' => isset($_POST['contract_renewal_sms_enabled']) ? 1 : 0,
            'due_date_sms_enabled' => isset($_POST['due_date_sms_enabled']) ? 1 : 0,
            'due_date_reminder_sms_enabled' => isset($_POST['due_date_reminder_sms_enabled']) ? 1 : 0,
            'purchase_template' => (string) ($_POST['purchase_template'] ?? ''),
            'birthday_template' => (string) ($_POST['birthday_template'] ?? ''),
            'wallet_reduction_template' => (string) ($_POST['wallet_reduction_template'] ?? ''),
            'welcome_template' => (string) ($_POST['welcome_template'] ?? ''),
            'service_template' => (string) ($_POST['service_template'] ?? ''),
            'contract_renewal_template' => (string) ($_POST['contract_renewal_template'] ?? ''),
            'due_date_template' => (string) ($_POST['due_date_template'] ?? ''),
            'due_date_reminder_template' => (string) ($_POST['due_date_reminder_template'] ?? ''),
            'updated_at' => \current_datetime(),
        ], $token !== '');
        Flash::set('success', 'تنظیمات پیامک ذخیره شد.');
        \redirect('/admin/sms-settings');
    }
}
