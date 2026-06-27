<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DueDateService;
use PHPUnit\Framework\TestCase;

final class DueDateServiceTest extends TestCase
{
    public function testValidationRequiresCoreFields(): void
    {
        $errors = DueDateService::validate([]);

        $this->assertArrayHasKey('customer_id', $errors);
        $this->assertArrayHasKey('due_date', $errors);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertArrayHasKey('due_type', $errors);
        $this->assertArrayHasKey('status', $errors);
    }

    public function testValidationAcceptsValidPayload(): void
    {
        $customer = ['id' => 1, 'deleted_at' => null];
        $operator = ['id' => 2, 'is_active' => 1];
        $errors = DueDateService::validate([
            'customer_id' => 1,
            'operator_id' => 2,
            'due_date' => '2026-07-15',
            'amount' => '1500000',
            'due_type' => 'check',
            'status' => 'pending',
        ], $customer, null, $operator);

        $this->assertSame([], $errors);
    }

    public function testValidationRejectsMismatchedPurchaseCustomer(): void
    {
        $customer = ['id' => 1, 'deleted_at' => null];
        $operator = ['id' => 2, 'is_active' => 1];
        $purchase = ['id' => 10, 'customer_id' => 2, 'status' => 'active', 'invoice_ref' => 'INV-1'];
        $errors = DueDateService::validate([
            'customer_id' => 1,
            'operator_id' => 2,
            'due_date' => '2026-07-15',
            'amount' => '1500000',
            'due_type' => 'invoice',
            'status' => 'pending',
            'purchase_id' => 10,
        ], $customer, $purchase, $operator);

        $this->assertArrayHasKey('purchase_id', $errors);
    }

    public function testDueTypeAndStatusLabels(): void
    {
        $this->assertSame('چک', DueDateService::dueTypeLabel('check'));
        $this->assertSame('در انتظار', DueDateService::statusLabel('pending'));
        $this->assertArrayHasKey('installment', DueDateService::dueTypeOptions());
        $this->assertArrayHasKey('overdue', DueDateService::statusOptions());
    }
}
