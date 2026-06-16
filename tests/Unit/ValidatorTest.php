<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testCustomerValidationRequiresNamesPhoneAndIdentifier(): void
    {
        $errors = Validator::customer([
            'first_name' => '',
            'last_name' => '',
            'national_code' => '123',
            'phone_number' => '12345',
        ], false, false);

        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('national_code', $errors);
        $this->assertArrayHasKey('phone_number', $errors);
    }

    public function testCustomerValidationReportsDuplicateIdentifier(): void
    {
        $errors = Validator::customer([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'national_code' => '1234567890',
            'phone_number' => '09123456789',
        ], false, true);

        $this->assertArrayHasKey('national_code', $errors);
    }

    public function testCustomerValidationHandlesBirthdayRules(): void
    {
        $requiredErrors = Validator::customer([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'national_code' => '1234567890',
            'phone_number' => '09123456789',
            'birthday' => '',
        ], true, false);

        $invalidErrors = Validator::customer([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'national_code' => '1234567890',
            'phone_number' => '09123456789',
            'birthday' => '1403/01/01',
        ], false, false);

        $this->assertArrayHasKey('birthday', $requiredErrors);
        $this->assertArrayHasKey('birthday', $invalidErrors);
    }

    public function testPositiveAmountValidationNormalizesDigitsAndSeparators(): void
    {
        $this->assertSame([], Validator::positiveAmount('۱,۰۰۰'));
        $this->assertArrayHasKey('amount', Validator::positiveAmount('۰'));
    }
}
