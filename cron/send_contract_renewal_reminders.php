<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Repositories\ContractRenewalSmsRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\SmsRepository;
use App\Services\SmsService;

$reminderDays = 5;
$smsRepo = new SmsRepository();
$settings = $smsRepo->settings();

if (empty($settings['sms_enabled']) || empty($settings['contract_renewal_sms_enabled'])) {
    echo "Contract renewal SMS is disabled.\n";
    exit(0);
}

$customers = (new CustomerRepository())->dueForContractRenewal($reminderDays);

if (!$customers) {
    echo "No eligible contract renewals today.\n";
    exit(0);
}

$service = new SmsService();
$history = new ContractRenewalSmsRepository();

foreach ($customers as $customer) {
    $customerId = (int) $customer['id'];
    $contractEndsAt = (string) $customer['contract_ends_at'];

    if ($history->exists($customerId, $contractEndsAt, $reminderDays)) {
        echo "Skipping customer #{$customerId}: reminder already sent.\n";
        continue;
    }

    $logId = $service->sendEvent('contract_renewal', $customer);
    $history->insert($customerId, $contractEndsAt, $reminderDays, $logId);
    echo "Contract renewal SMS attempted for customer #{$customerId} ({$customer['phone_number']}).\n";
}
