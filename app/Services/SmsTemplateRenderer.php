<?php

declare(strict_types=1);

namespace App\Services;

final class SmsTemplateRenderer
{
    public function render(string $template, array $customer, array $vars = []): string
    {
        $values = array_merge([
            'first_name' => $customer['first_name'] ?? '',
            'last_name' => $customer['last_name'] ?? '',
            'full_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'purchase_amount' => '',
            'cashback_amount' => '',
            'wallet_balance' => isset($customer['wallet_balance']) ? \money($customer['wallet_balance']) : '',
            'birthday' => $customer['birthday'] ?? '',
            'contract_number' => $customer['contract_number'] ?? '',
            'contract_ends_at' => isset($customer['contract_ends_at']) ? \App\Core\Jalali::formatDate($customer['contract_ends_at']) : '',
            'service_type' => '',
            'service_date' => '',
            'paid_amount' => '',
            'due_amount' => '',
            'due_date' => '',
            'reference_number' => '',
            'due_type_label' => '',
            'date' => date('Y-m-d'),
            'company_name' => \config_value('app.company_name', 'نوآوران زیبایی'),
        ], $vars);

        if (isset($vars['otp_code'])) {
            $values['otp_code'] = (string) $vars['otp_code'];
        }

        foreach (['purchase_amount', 'cashback_amount', 'wallet_balance', 'paid_amount', 'due_amount'] as $moneyKey) {
            if ($values[$moneyKey] !== '' && is_numeric($values[$moneyKey])) {
                $values[$moneyKey] = \money($values[$moneyKey]);
            }
        }

        return strtr($template, array_combine(
            array_map(fn (string $key): string => '{' . $key . '}', array_keys($values)),
            array_map(fn ($value): string => (string) $value, array_values($values))
        ));
    }
}
