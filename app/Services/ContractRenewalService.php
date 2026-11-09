<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Jalali;
use App\Repositories\ContractRenewalRepository;
use App\Repositories\CustomerRepository;

final class ContractRenewalService
{
    private ContractRenewalRepository $renewals;
    private CustomerRepository $customers;

    public function __construct()
    {
        $this->renewals = new ContractRenewalRepository();
        $this->customers = new CustomerRepository();
    }

    public function create(array $data): array
    {
        $customerId = (int) ($data['customer_id'] ?? 0);
        $customer = $customerId > 0 ? $this->customers->find($customerId) : null;
        $newStart = $this->normalizeDate((string) ($data['new_starts_at'] ?? ''));
        $newEnd = $this->normalizeDate((string) ($data['new_ends_at'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));

        $errors = [];
        if (!$customer || !empty($customer['deleted_at'])) {
            $errors['customer_id'] = 'مشتری معتبر انتخاب نشده است.';
        }
        if ($newEnd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newEnd)) {
            $errors['new_ends_at'] = 'تاریخ پایان قرارداد معتبر نیست.';
        }
        if ($newStart !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newStart)) {
            $errors['new_starts_at'] = 'تاریخ شروع قرارداد معتبر نیست.';
        }
        if ($newStart !== '' && $newEnd !== '' && $newEnd < $newStart) {
            $errors['new_ends_at'] = 'تاریخ پایان نباید قبل از تاریخ شروع باشد.';
        }
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $previousStarts = !empty($customer['contract_starts_at']) ? (string) $customer['contract_starts_at'] : null;
        $previousEnds = !empty($customer['contract_ends_at']) ? (string) $customer['contract_ends_at'] : null;

        $id = $this->renewals->create([
            'customer_id' => $customerId,
            'operator_id' => SystemUserService::actorId(),
            'previous_starts_at' => $previousStarts,
            'previous_ends_at' => $previousEnds,
            'new_starts_at' => $newStart !== '' ? $newStart : null,
            'new_ends_at' => $newEnd,
            'note' => $note !== '' ? $note : null,
            'created_at' => \current_datetime(),
        ]);

        $startsToSave = $newStart !== '' ? $newStart : $previousStarts;
        $this->customers->updateContractDates($customerId, $startsToSave, $newEnd);

        (new ActivityLogger())->log(
            'contract_renewal',
            'قرارداد مشتری تا ' . $newEnd . ' تمدید شد.',
            $customerId
        );

        return ['ok' => true, 'id' => $id, 'customer_id' => $customerId, 'errors' => []];
    }

    private function normalizeDate(string $raw): string
    {
        $raw = trim(\normalize_digits($raw));
        if ($raw === '') {
            return '';
        }

        $gregorian = Jalali::parseInputToGregorian($raw);

        return $gregorian ?? $raw;
    }
}
