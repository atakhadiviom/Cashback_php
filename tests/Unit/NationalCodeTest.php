<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class NationalCodeTest extends TestCase
{
    public function testValidNationalCode(): void
    {
        $this->assertTrue(Validator::isValidIranianNationalCode('0499370899'));
    }

    public function testInvalidNationalCode(): void
    {
        $this->assertFalse(Validator::isValidIranianNationalCode('1234567890'));
        $this->assertFalse(Validator::isValidIranianNationalCode('0000000000'));
    }

    public function testCustomerValidationAcceptsTenDigitCodeWithoutChecksumBlocking(): void
    {
        $errors = Validator::customer([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'national_code' => '1234567890',
            'phone_number' => '09123456789',
        ], false, false);

        $this->assertArrayNotHasKey('national_code', $errors);
    }

    public function testCustomerValidationAcceptsElevenDigitCompanyCode(): void
    {
        $errors = Validator::customer([
            'first_name' => 'Test',
            'last_name' => 'Company',
            'national_code' => '12345678901',
            'phone_number' => '09123456789',
        ], false, false);

        $this->assertArrayNotHasKey('national_code', $errors);
    }

    public function testCustomerValidationRejectsUnsupportedIdentifierLengths(): void
    {
        foreach (['123456789', '123456789012'] as $nationalCode) {
            $errors = Validator::customer([
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'national_code' => $nationalCode,
                'phone_number' => '09123456789',
            ], false, false);

            $this->assertArrayHasKey('national_code', $errors);
        }
    }
}
