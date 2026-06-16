<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ApiAuthService;
use PHPUnit\Framework\TestCase;

final class ApiAuthServiceTest extends TestCase
{
    public function testGenerateKeyReturnsPlainPrefixAndHash(): void
    {
        $key = ApiAuthService::generateKey();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}\.[a-f0-9]{32}$/', $key['plain']);
        $this->assertSame($key['prefix'], explode('.', $key['plain'], 2)[0]);
        $this->assertTrue(password_verify(explode('.', $key['plain'], 2)[1], $key['hash']));
    }
}
