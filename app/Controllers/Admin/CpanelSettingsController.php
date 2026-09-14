<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Services\CpanelCronService;
use App\Services\CpanelSettingsService;

final class CpanelSettingsController
{
    public function edit(): void
    {
        $settingsService = new CpanelSettingsService();
        $settings = $settingsService->all();
        $cpanel = new CpanelCronService();
        $connection = [
            'configured' => $cpanel->isEnabled(),
            'list' => $cpanel->isEnabled()
                ? $cpanel->listCrons()
                : ['ok' => false, 'message' => 'cPanel API not configured', 'crons' => []],
        ];

        View::render('admin/cpanel_settings', [
            'settings' => $settings,
            'hasApiToken' => $settingsService->hasApiToken(),
            'hasPassword' => $settingsService->hasPassword(),
            'tokenHint' => $settingsService->maskedTokenHint(),
            'passwordHint' => $settingsService->maskedPasswordHint(),
            'connection' => $connection,
        ]);
    }

    public function update(): void
    {
        Csrf::requireValid();

        $payload = $this->payloadFromRequest();
        $errors = $this->validate($payload);
        if ($errors !== []) {
            Flash::set('danger', implode(' ', $errors));
            \redirect('/admin/cpanel-settings');
        }

        try {
            (new CpanelSettingsService())->save($payload);
            Flash::set('success', 'تنظیمات cPanel ذخیره شد.');
        } catch (\Throwable $e) {
            Flash::set('danger', 'ذخیره تنظیمات ناموفق بود: ' . $e->getMessage());
        }

        \redirect('/admin/cpanel-settings');
    }

    public function setupCron(): void
    {
        Csrf::requireValid();

        $payload = $this->payloadFromRequest();
        $errors = $this->validate($payload);
        if ($errors !== []) {
            Flash::set('danger', implode(' ', $errors));
            \redirect('/admin/cpanel-settings');
        }

        try {
            (new CpanelSettingsService())->save($payload);
        } catch (\Throwable $e) {
            Flash::set('danger', 'ذخیره تنظیمات ناموفق بود: ' . $e->getMessage());
            \redirect('/admin/cpanel-settings');
        }

        $result = (new CpanelCronService())->ensureCronJobs();
        if ($result['ok']) {
            Flash::set('success', 'تنظیمات ذخیره شد. ' . $result['message']);
        } else {
            Flash::set('danger', 'تنظیمات ذخیره شد، اما ثبت کرون‌جاب ناموفق بود: ' . $result['message']);
        }

        \redirect('/admin/cpanel-settings');
    }

    /**
     * @return array{
     *     enabled: bool,
     *     host: string,
     *     username: string,
     *     api_token: string,
     *     password: string,
     *     domain: string,
     *     app_root: string,
     *     home_dir: string,
     *     php_path: string
     * }
     */
    private function payloadFromRequest(): array
    {
        return [
            'enabled' => isset($_POST['enabled']),
            'host' => trim((string) ($_POST['host'] ?? '')),
            'username' => trim((string) ($_POST['username'] ?? '')),
            'api_token' => trim((string) ($_POST['api_token'] ?? '')),
            'password' => trim((string) ($_POST['password'] ?? '')),
            'domain' => trim((string) ($_POST['domain'] ?? '')),
            'app_root' => trim((string) ($_POST['app_root'] ?? '')),
            'home_dir' => trim((string) ($_POST['home_dir'] ?? '')),
            'php_path' => trim((string) ($_POST['php_path'] ?? '')),
        ];
    }

    /**
     * @param array{enabled: bool, host: string, username: string} $payload
     * @return list<string>
     */
    private function validate(array $payload): array
    {
        $errors = [];
        if (!$payload['enabled']) {
            return $errors;
        }
        if ($payload['host'] === '') {
            $errors[] = 'وقتی اتصال فعال است، آدرس میزبان (host) الزامی است.';
        }
        if ($payload['username'] === '') {
            $errors[] = 'وقتی اتصال فعال است، نام کاربری cPanel الزامی است.';
        }

        return $errors;
    }
}
