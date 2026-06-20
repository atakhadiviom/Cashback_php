<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\CustomerRepository;
use App\Repositories\ServiceRecordRepository;
use App\Services\ServiceRecordService;
use PHPUnit\Framework\TestCase;

final class ServiceManagementTest extends TestCase
{
    private function customerRepositoryWithoutDb(): CustomerRepository
    {
        return (new \ReflectionClass(CustomerRepository::class))->newInstanceWithoutConstructor();
    }

    private function serviceRecordRepositoryWithoutDb(): ServiceRecordRepository
    {
        return (new \ReflectionClass(ServiceRecordRepository::class))->newInstanceWithoutConstructor();
    }

    public function testCustomerSearchIncludesContractNumberFilter(): void
    {
        $repo = $this->customerRepositoryWithoutDb();
        $method = new \ReflectionMethod(CustomerRepository::class, 'filters');
        $method->setAccessible(true);
        [$where, $params] = $method->invoke($repo, ['contract_number' => 'ELV-100']);

        $this->assertContains('c.contract_number LIKE :contract_number', $where);
        $this->assertSame('%ELV-100%', $params['contract_number']);
    }

    public function testCustomerGlobalSearchIncludesContractNumber(): void
    {
        $repo = $this->customerRepositoryWithoutDb();
        $method = new \ReflectionMethod(CustomerRepository::class, 'filters');
        $method->setAccessible(true);
        [$where] = $method->invoke($repo, ['q' => 'احمد']);

        $this->assertStringContainsString('c.contract_number LIKE :q5', implode(' ', $where));
    }

    public function testServiceRecordValidationRequiresCoreFields(): void
    {
        $errors = ServiceRecordService::validate([], null, null);

        $this->assertArrayHasKey('customer_id', $errors);
        $this->assertArrayHasKey('technician_id', $errors);
        $this->assertArrayHasKey('service_type', $errors);
        $this->assertArrayHasKey('service_date', $errors);
    }

    public function testServiceRecordValidationAcceptsValidPayload(): void
    {
        $customer = ['id' => 1, 'deleted_at' => null];
        $technician = ['id' => 2, 'is_active' => 1];
        $errors = ServiceRecordService::validate([
            'customer_id' => 1,
            'technician_id' => 2,
            'service_type' => 'periodic',
            'service_date' => '2026-06-20',
            'paid_amount' => '150000',
        ], $customer, $technician);

        $this->assertSame([], $errors);
    }

    public function testServiceRecordValidationRejectsNegativePaidAmount(): void
    {
        $customer = ['id' => 1, 'deleted_at' => null];
        $technician = ['id' => 2, 'is_active' => 1];
        $errors = ServiceRecordService::validate([
            'customer_id' => 1,
            'technician_id' => 2,
            'service_type' => 'repair',
            'service_date' => '2026-06-20',
            'paid_amount' => '-1',
        ], $customer, $technician);

        $this->assertArrayHasKey('paid_amount', $errors);
    }

    public function testServiceReportFiltersByTechnicianPaymentStatusAndDateRange(): void
    {
        $repo = $this->serviceRecordRepositoryWithoutDb();
        $method = new \ReflectionMethod(ServiceRecordRepository::class, 'filters');
        $method->setAccessible(true);
        [$where, $params] = $method->invoke($repo, [
            'date_from' => '2026-01-01',
            'date_to' => '2026-06-30',
            'technician_id' => '5',
            'payment_status' => 'paid',
            'service_type' => 'inspection',
            'contract_number' => 'C-42',
        ]);

        $this->assertContains('s.service_date >= :date_from', $where);
        $this->assertContains('s.service_date <= :date_to', $where);
        $this->assertContains('s.technician_id = :technician_id', $where);
        $this->assertContains("s.payment_status = :payment_status", $where);
        $this->assertContains("s.service_type = :service_type", $where);
        $this->assertContains('c.contract_number LIKE :contract_number', $where);
        $this->assertSame('2026-01-01', $params['date_from']);
        $this->assertSame('2026-06-30', $params['date_to']);
        $this->assertSame(5, $params['technician_id']);
        $this->assertSame('paid', $params['payment_status']);
        $this->assertSame('inspection', $params['service_type']);
    }

    public function testContractRenewalHistoryUsesCompositeReminderKey(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/database/migrations/012_elevator_services.sql');
        $this->assertNotFalse($source);
        $this->assertStringContainsString('uq_contract_renewal_reminder (customer_id, contract_ends_at, reminder_days)', $source);
        $this->assertStringContainsString('INSERT IGNORE INTO contract_renewal_sms_history', file_get_contents(dirname(__DIR__, 2) . '/app/Repositories/ContractRenewalSmsRepository.php'));
    }
}
