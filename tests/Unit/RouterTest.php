<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private array $previousServer;
    private array $previousConfig;
    private array $previousSession;

    protected function setUp(): void
    {
        $this->previousServer = $_SERVER;
        $this->previousConfig = $GLOBALS['config'] ?? [];
        $this->previousSession = $_SESSION ?? [];
        $_SESSION = [];
        $GLOBALS['config']['app']['base_url'] = '';
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->previousServer;
        $GLOBALS['config'] = $this->previousConfig;
        $_SESSION = $this->previousSession;
        http_response_code(200);
    }

    public function testDispatchCallsMatchingUnauthenticatedRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/health';
        $router = new Router();
        $router->get('/health', [RouterTestController::class, 'health'], false);

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertSame('ok', $output);
    }

    public function testDispatchReturns404ForMissingRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/missing';
        $router = new Router();

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertSame('404', $output);
        $this->assertSame(404, http_response_code());
    }

    public function testDispatchStripsConfiguredBaseUrl(): void
    {
        $GLOBALS['config']['app']['base_url'] = '/panel';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/panel/health';
        $router = new Router();
        $router->get('/health', [RouterTestController::class, 'health'], false);

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertSame('ok', $output);
    }
}

final class RouterTestController
{
    public function health(): void
    {
        echo 'ok';
    }
}
