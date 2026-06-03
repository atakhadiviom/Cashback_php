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
}
