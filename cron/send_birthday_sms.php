<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\SmsRepository;
use App\Repositories\WalletRepository;
use App\Services\SmsService;
use App\Services\SystemUserService;

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

$cashbackSettings = (new CashbackSettingsRepository())->settings();
$bonus = (float) ($cashbackSettings['birthday_bonus_amount'] ?? 0);
$service = new SmsService();
$customersRepo = new CustomerRepository();

foreach ($customers as $customer) {
    $logId = $service->sendEvent('birthday', $customer);
    $smsRepo->insertBirthdayHistory((int) $customer['id'], $year, $logId);

    if ($bonus > 0) {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $customersRepo->incrementWallet((int) $customer['id'], $bonus);
            $updated = $customersRepo->find((int) $customer['id']);
            (new WalletRepository())->create(
                (int) $customer['id'],
                'cashback',
                $bonus,
                (float) $updated['wallet_balance'],
                'پاداش تولد',
                null,
                SystemUserService::actorId()
            );
            $stmt = $pdo->prepare('UPDATE birthday_sms_history SET bonus_credited = 1 WHERE customer_id = :cid AND sent_year = :year');
            $stmt->execute(['cid' => $customer['id'], 'year' => $year]);
            $pdo->commit();
            echo "Birthday bonus credited for customer #{$customer['id']}.\n";
        } catch (\Throwable $e) {
            $pdo->rollBack();
            echo "Bonus failed for #{$customer['id']}: {$e->getMessage()}\n";
        }
    }

    echo "Birthday SMS attempted for customer #{$customer['id']} ({$customer['phone_number']}).\n";
}
