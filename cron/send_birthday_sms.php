<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Repositories\CustomerRepository;
use App\Repositories\SmsRepository;
use App\Services\SmsService;

$smsRepo = new SmsRepository();
$settings = $smsRepo->settings();

if (empty($settings['sms_enabled']) || empty($settings['birthday_sms_enabled'])) {
    echo "Birthday SMS is disabled.\n";
    exit(0);
}

$year = (int) date('Y');
$customers = (new CustomerRepository())->birthdayTodayWithoutHistory($year);

if (!$customers) {
    echo "No eligible birthday customers today.\n";
    exit(0);
}

$service = new SmsService();
foreach ($customers as $customer) {
    $logId = $service->sendEvent('birthday', $customer);
    $smsRepo->insertBirthdayHistory((int) $customer['id'], $year, $logId);
    echo "Birthday SMS attempted for customer #{$customer['id']} ({$customer['phone_number']}).\n";
}
