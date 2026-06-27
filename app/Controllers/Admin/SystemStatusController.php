<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\View;
use App\Repositories\CronStateRepository;
use App\Repositories\SmsRepository;
use App\Services\CpanelCronService;

final class SystemStatusController
{
    public function index(): void
    {
        $root = dirname(__DIR__, 3);
        $storagePath = $root . '/storage';
        $cronState = (new CronStateRepository())->all();
        $smsSettings = (new SmsRepository())->settings();
        $webToken = trim((string) \config_value('cron.web_token', ''));

        $cpanel = new CpanelCronService();
        $cpanelStatus = $cpanel->isEnabled() ? $cpanel->listCrons() : ['ok' => false, 'message' => 'cPanel API disabled', 'crons' => []];

        View::render('admin/system_status', [
            'healthChecks' => $this->healthChecks($storagePath),
            'cronChecks' => $this->cronChecks($cronState, $smsSettings),
            'cronWebUrl' => $webToken !== '' ? \url('/internal/cron?task=all&token=' . rawurlencode($webToken)) : '',
            'cronStatePath' => $storagePath . '/cron_state.json',
            'phpVersion' => PHP_VERSION,
            'databaseName' => (string) \config_value('database.name', ''),
            'cpanelEnabled' => $cpanel->isEnabled(),
            'cpanelStatus' => $cpanelStatus,
        ]);
    }

    /** @return list<array{label: string, status: string, ok: bool, detail: string}> */
    private function healthChecks(string $storagePath): array
    {
        $checks = [
            [
                'label' => 'نسخه PHP',
                'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'status' => PHP_VERSION,
                'detail' => 'حداقل نسخه مورد نیاز 8.1 است.',
            ],
            [
                'label' => 'افزونه PDO MySQL',
                'ok' => extension_loaded('pdo_mysql'),
                'status' => extension_loaded('pdo_mysql') ? 'فعال' : 'غیرفعال',
                'detail' => 'برای اتصال به دیتابیس لازم است.',
            ],
            [
                'label' => 'افزونه cURL',
                'ok' => extension_loaded('curl'),
                'status' => extension_loaded('curl') ? 'فعال' : 'غیرفعال',
                'detail' => 'برای ارسال پیامک و آپدیت برنامه استفاده می‌شود.',
            ],
            [
                'label' => 'افزونه mbstring',
                'ok' => extension_loaded('mbstring'),
                'status' => extension_loaded('mbstring') ? 'فعال' : 'غیرفعال',
                'detail' => 'برای پردازش متن فارسی لازم است.',
            ],
            [
                'label' => 'نوشتن در storage',
                'ok' => is_dir($storagePath) && is_writable($storagePath),
                'status' => is_writable($storagePath) ? 'قابل نوشتن' : 'غیرقابل نوشتن',
                'detail' => $storagePath,
            ],
        ];

        try {
            $pdo = Database::pdo();
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            $checks[] = [
                'label' => 'اتصال دیتابیس',
                'ok' => true,
                'status' => 'متصل',
                'detail' => $version,
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'label' => 'اتصال دیتابیس',
                'ok' => false,
                'status' => 'خطا',
                'detail' => $e->getMessage(),
            ];
        }

        return $checks;
    }

    /**
     * @param array<string, string> $cronState
     * @param array<string, mixed> $smsSettings
     * @return list<array{key: string, label: string, schedule: string, last_run: string, due: bool, enabled: bool, detail: string}>
     */
    private function cronChecks(array $cronState, array $smsSettings): array
    {
        $retryMinutes = max(1, (int) \config_value('cron.sms_retry_interval_minutes', 15));

        return [
            [
                'key' => 'birthday',
                'label' => 'پیامک تولد',
                'schedule' => 'روزانه 08:00',
                'last_run' => $cronState['birthday'] ?? 'هرگز',
                'due' => $this->dailyDue($cronState['birthday'] ?? null),
                'enabled' => !empty($smsSettings['sms_enabled']) && !empty($smsSettings['birthday_sms_enabled']),
                'detail' => !empty($smsSettings['birthday_sms_enabled']) ? 'فعال در تنظیمات پیامک' : 'Birthday SMS is disabled.',
            ],
            [
                'key' => 'contract_renewal',
                'label' => 'یادآوری تمدید قرارداد',
                'schedule' => 'روزانه 09:00',
                'last_run' => $cronState['contract_renewal'] ?? 'هرگز',
                'due' => $this->dailyDue($cronState['contract_renewal'] ?? null),
                'enabled' => !empty($smsSettings['sms_enabled']) && !empty($smsSettings['contract_renewal_sms_enabled']),
                'detail' => !empty($smsSettings['contract_renewal_sms_enabled']) ? 'فعال در تنظیمات پیامک' : 'Contract renewal SMS is disabled.',
            ],
            [
                'key' => 'due_date_reminders',
                'label' => 'یادآوری سررسیدها',
                'schedule' => 'روزانه 10:00',
                'last_run' => $cronState['due_date_reminders'] ?? 'هرگز',
                'due' => $this->dailyDue($cronState['due_date_reminders'] ?? null),
                'enabled' => !empty($smsSettings['sms_enabled']) && !empty($smsSettings['due_date_reminder_sms_enabled']),
                'detail' => !empty($smsSettings['due_date_reminder_sms_enabled']) ? 'فعال در تنظیمات پیامک' : 'Due date reminder SMS is disabled.',
            ],
            [
                'key' => 'sms_retry',
                'label' => 'تلاش مجدد پیامک',
                'schedule' => 'هر ' . $retryMinutes . ' دقیقه',
                'last_run' => $cronState['sms_retry'] ?? 'هرگز',
                'due' => $this->intervalDue($cronState['sms_retry'] ?? null, $retryMinutes),
                'enabled' => true,
                'detail' => 'ارسال دوباره پیامک‌های ناموفق یا در انتظار.',
            ],
        ];
    }

    private function dailyDue(?string $lastRun): bool
    {
        return $lastRun === null || substr($lastRun, 0, 10) !== date('Y-m-d');
    }

    private function intervalDue(?string $lastRun, int $minutes): bool
    {
        if ($lastRun === null) {
            return true;
        }

        $timestamp = strtotime($lastRun);
        return $timestamp === false || time() - $timestamp >= $minutes * 60;
    }

    public function setupCpanelCron(): void
    {
        \App\Core\Csrf::requireValid();

        $service = new CpanelCronService();
        $result = $service->ensureCronJobs();

        if ($result['ok']) {
            \App\Core\Flash::set('success', $result['message']);
        } else {
            \App\Core\Flash::set('danger', 'cPanel setup failed: ' . $result['message']);
        }

        \redirect('/admin/system-status');
    }
}
