<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SmsRepository;

final class SmsService
{
    private SmsRepository $sms;

    public function __construct()
    {
        $this->sms = new SmsRepository();
    }

    public function sendEvent(string $eventType, array $customer, array $vars = []): ?int
    {
        $settings = $this->sms->settings();
        $flag = match ($eventType) {
            'purchase' => 'purchase_sms_enabled',
            'birthday' => 'birthday_sms_enabled',
            'wallet_reduction' => 'wallet_reduction_sms_enabled',
            'welcome' => 'welcome_sms_enabled',
            default => 'sms_enabled',
        };
        if (empty($settings['sms_enabled']) || empty($settings[$flag])) {
            return null;
        }

        $templateKey = $eventType . '_template';
        $template = (string) ($settings[$templateKey] ?? '');
        $message = (new SmsTemplateRenderer())->render($template, $customer, $vars);
        $logId = $this->sms->logPending($customer['id'] ?? null, (string) $customer['phone_number'], $eventType, $message);

        try {
            $result = (new IppanelSmsProvider())->send($settings, (string) $customer['phone_number'], $message);
            $status = $result['ok'] ? 'sent' : 'failed';
            $this->sms->updateLog($logId, $status, $result['response']);
            (new ActivityLogger())->log($status === 'sent' ? 'sms_sent' : 'sms_failed', 'پیامک ' . $eventType . ' برای ' . ($customer['phone_number'] ?? '') . ' ثبت شد.', $customer['id'] ?? null);
        } catch (\Throwable $exception) {
            $this->sms->updateLog($logId, 'failed', $exception->getMessage());
            (new ActivityLogger())->log('sms_failed', 'خطا در ارسال پیامک: ' . $exception->getMessage(), $customer['id'] ?? null);
        }

        return $logId;
    }
}
