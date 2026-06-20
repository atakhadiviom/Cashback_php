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
        switch ($eventType) {
            case 'purchase':
                $flag = 'purchase_sms_enabled';
                break;
            case 'birthday':
                $flag = 'birthday_sms_enabled';
                break;
            case 'wallet_reduction':
                $flag = 'wallet_reduction_sms_enabled';
                break;
            case 'welcome':
                $flag = 'welcome_sms_enabled';
                break;
            case 'service_confirmation':
                $flag = 'service_sms_enabled';
                break;
            case 'contract_renewal':
                $flag = 'contract_renewal_sms_enabled';
                break;
            case 'purchase_void':
            case 'referral_bonus':
                $flag = 'purchase_sms_enabled';
                break;
            default:
                $flag = 'sms_enabled';
                break;
        }
        if (empty($settings['sms_enabled'])) {
            return null;
        }
        if ($eventType !== 'otp' && empty($settings[$flag] ?? 0)) {
            return null;
        }

        switch ($eventType) {
            case 'purchase_void':
                $templateKey = 'purchase_void_template';
                break;
            case 'referral_bonus':
                $templateKey = 'referral_template';
                break;
            case 'otp':
                $templateKey = 'otp_template';
                break;
            case 'service_confirmation':
                $templateKey = 'service_template';
                break;
            case 'contract_renewal':
                $templateKey = 'contract_renewal_template';
                break;
            default:
                $templateKey = $eventType . '_template';
                break;
        }
        $template = (string) ($settings[$templateKey] ?? '');
        if ($eventType === 'otp' && $template === '' && !empty($vars['message'])) {
            $message = (string) $vars['message'];
        } else {
            $message = (new SmsTemplateRenderer())->render($template, $customer, $vars);
        }

        return $this->sendRaw((string) $customer['phone_number'], $message, $eventType, $customer['id'] ?? null);
    }

    public function sendRaw(string $phone, string $message, string $eventType, ?int $customerId): ?int
    {
        $settings = $this->sms->settings();
        if (empty($settings['sms_enabled'])) {
            return null;
        }

        $logId = $this->sms->logPending($customerId, $phone, $eventType, $message);

        try {
            $result = (new IppanelSmsProvider())->send($settings, $phone, $message);
            if ($result['ok']) {
                $this->sms->updateLog($logId, 'sent', $result['response']);
                (new ActivityLogger())->log('sms_sent', 'پیامک ' . $eventType . ' برای ' . $phone, $customerId);
            } else {
                $this->sms->scheduleRetry($logId, $result['response']);
                (new ActivityLogger())->log('sms_failed', 'خطا در ارسال پیامک: ' . $eventType, $customerId);
            }
        } catch (\Throwable $exception) {
            $this->sms->scheduleRetry($logId, $exception->getMessage());
            (new ActivityLogger())->log('sms_failed', 'خطا در ارسال پیامک: ' . $exception->getMessage(), $customerId);
        }

        return $logId;
    }

    public function retryPending(): int
    {
        $count = 0;
        foreach ($this->sms->dueForRetry() as $log) {
            $settings = $this->sms->settings();
            if (empty($settings['sms_enabled'])) {
                break;
            }
            try {
                $result = (new IppanelSmsProvider())->send($settings, (string) $log['phone_number'], (string) $log['message']);
                if ($result['ok']) {
                    $this->sms->updateLog((int) $log['id'], 'sent', $result['response']);
                    $count++;
                } else {
                    $this->sms->scheduleRetry((int) $log['id'], $result['response'], (int) $log['retry_count'] + 1);
                }
            } catch (\Throwable $exception) {
                $this->sms->scheduleRetry((int) $log['id'], $exception->getMessage(), (int) $log['retry_count'] + 1);
            }
        }
        return $count;
    }
}
