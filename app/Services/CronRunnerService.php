<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\CashbackSettingsRepository;
use App\Repositories\ContractRenewalSmsRepository;
use App\Repositories\CronStateRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\SmsRepository;
use App\Repositories\WalletRepository;

final class CronRunnerService
{
    private CronStateRepository $state;

    public function __construct(?CronStateRepository $state = null)
    {
        $this->state = $state ?? new CronStateRepository();
    }

    /** @return array{ok: bool, messages: string[]} */
    public function runBirthdaySms(): array
    {
        $messages = [];
        $settings = (new SmsRepository())->settings();
        if (empty($settings['sms_enabled']) || empty($settings['birthday_sms_enabled'])) {
            return ['ok' => true, 'messages' => ['Birthday SMS is disabled.']];
        }

        $year = (int) date('Y');
        $customers = (new CustomerRepository())->birthdayTodayWithoutHistory($year);
        if (!$customers) {
            $this->state->markRun('birthday');
            return ['ok' => true, 'messages' => ['No eligible birthday customers today.']];
        }

        $bonus = (float) ((new CashbackSettingsRepository())->settings()['birthday_bonus_amount'] ?? 0);
        $sms = new SmsService();
        $smsRepo = new SmsRepository();
        $customersRepo = new CustomerRepository();

        foreach ($customers as $customer) {
            $logId = $sms->sendEvent('birthday', $customer);
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
                    $messages[] = "Birthday bonus credited for customer #{$customer['id']}.";
                } catch (\Throwable $e) {
                    $pdo->rollBack();
                    $messages[] = "Bonus failed for #{$customer['id']}: {$e->getMessage()}";
                }
            }

            $messages[] = "Birthday SMS attempted for customer #{$customer['id']} ({$customer['phone_number']}).";
        }

        $this->state->markRun('birthday');
        return ['ok' => true, 'messages' => $messages];
    }

    /** @return array{ok: bool, messages: string[]} */
    public function runContractRenewalReminders(int $reminderDays = 5): array
    {
        $settings = (new SmsRepository())->settings();
        if (empty($settings['sms_enabled']) || empty($settings['contract_renewal_sms_enabled'])) {
            return ['ok' => true, 'messages' => ['Contract renewal SMS is disabled.']];
        }

        $customers = (new CustomerRepository())->dueForContractRenewal($reminderDays);
        if (!$customers) {
            $this->state->markRun('contract_renewal');
            return ['ok' => true, 'messages' => ['No eligible contract renewals today.']];
        }

        $sms = new SmsService();
        $history = new ContractRenewalSmsRepository();
        $messages = [];

        foreach ($customers as $customer) {
            $customerId = (int) $customer['id'];
            $contractEndsAt = (string) $customer['contract_ends_at'];
            if ($history->exists($customerId, $contractEndsAt, $reminderDays)) {
                $messages[] = "Skipping customer #{$customerId}: reminder already sent.";
                continue;
            }
            $logId = $sms->sendEvent('contract_renewal', $customer);
            $history->insert($customerId, $contractEndsAt, $reminderDays, $logId);
            $messages[] = "Contract renewal SMS attempted for customer #{$customerId} ({$customer['phone_number']}).";
        }

        $this->state->markRun('contract_renewal');
        return ['ok' => true, 'messages' => $messages];
    }

    /** @return array{ok: bool, messages: string[]} */
    public function runSmsRetry(): array
    {
        $count = (new SmsService())->retryPending();
        $this->state->markRun('sms_retry');
        return ['ok' => true, 'messages' => ["Retried {$count} SMS messages."]];
    }

    /** @return array{ok: bool, messages: string[]} */
    public function runDailyTasks(): array
    {
        $messages = [];
        foreach ([$this->runBirthdaySms(), $this->runContractRenewalReminders()] as $result) {
            $messages = array_merge($messages, $result['messages']);
        }
        return ['ok' => true, 'messages' => $messages];
    }

    /** @return array{ok: bool, messages: string[]} */
    public function runTask(string $task): array
    {
        return match ($task) {
            'birthday' => $this->runBirthdaySms(),
            'contract_renewal' => $this->runContractRenewalReminders(),
            'sms_retry' => $this->runSmsRetry(),
            'all' => $this->runAll(),
            default => ['ok' => false, 'messages' => ['Unknown cron task.']],
        };
    }

    /** @return array{ok: bool, messages: string[]} */
    public function runAll(): array
    {
        $messages = [];
        foreach ([$this->runDailyTasks(), $this->runSmsRetry()] as $result) {
            $messages = array_merge($messages, $result['messages']);
        }
        return ['ok' => true, 'messages' => $messages];
    }

    /** Run due tasks when an admin opens the dashboard (no server cron required). */
    public function maybeRunFromDashboard(): array
    {
        if (!(bool) \config_value('cron.dashboard_auto_run', true)) {
            return ['ok' => true, 'messages' => []];
        }

        $messages = [];
        if ($this->state->shouldRunDaily('birthday')) {
            $messages = array_merge($messages, $this->runBirthdaySms()['messages']);
        }
        if ($this->state->shouldRunDaily('contract_renewal')) {
            $messages = array_merge($messages, $this->runContractRenewalReminders()['messages']);
        }

        $retryMinutes = (int) \config_value('cron.sms_retry_interval_minutes', 15);
        if ($this->state->shouldRunInterval('sms_retry', $retryMinutes)) {
            $messages = array_merge($messages, $this->runSmsRetry()['messages']);
        }

        return ['ok' => true, 'messages' => $messages];
    }

    public function state(): CronStateRepository
    {
        return $this->state;
    }
}
