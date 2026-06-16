<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use App\Core\Flash;
use PHPUnit\Framework\TestCase;

final class FlashCsrfTest extends TestCase
{
    private array $previousSession;

    protected function setUp(): void
    {
        $this->previousSession = $_SESSION ?? [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->previousSession;
    }

    public function testFlashMessagesAreReturnedOnce(): void
    {
        Flash::set('success', 'Saved');

        $this->assertSame([['type' => 'success', 'message' => 'Saved']], Flash::all());
        $this->assertSame([], Flash::all());
    }

    public function testCsrfTokenIsStableForSessionAndValidates(): void
    {
        $token = Csrf::token();

        $this->assertSame($token, Csrf::token());
        $this->assertTrue(Csrf::validate($token));
        $this->assertFalse(Csrf::validate('wrong-token'));
    }
}
