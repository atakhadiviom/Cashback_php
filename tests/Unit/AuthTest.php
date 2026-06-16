<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Auth;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    private array $previousSession;
    private array $previousConfig;

    protected function setUp(): void
    {
        $this->previousSession = $_SESSION ?? [];
        $this->previousConfig = $GLOBALS['config'] ?? [];
        $_SESSION = [];
        $GLOBALS['config']['security']['session_lifetime_minutes'] = 120;
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->previousSession;
        $GLOBALS['config'] = $this->previousConfig;
    }

    public function testAdminPermissionsIncludeAllKnownCapabilities(): void
    {
        $permissions = Auth::permissionsFromUser(['role' => 'admin']);

        $this->assertTrue($permissions['manage_settings']);
        $this->assertTrue($permissions['import_customers']);
        $this->assertTrue($permissions['manage_loyalty']);
    }

    public function testOperatorPermissionsMergeStoredJsonWithDefaults(): void
    {
        $permissions = Auth::permissionsFromUser([
            'role' => 'operator',
            'permissions' => json_encode(['void_purchase' => true], JSON_THROW_ON_ERROR),
        ]);

        $this->assertTrue($permissions['purchase']);
        $this->assertTrue($permissions['void_purchase']);
        $this->assertFalse($permissions['manage_settings']);
    }

    public function testCanUsesCurrentSessionPermissions(): void
    {
        $_SESSION['user'] = [
            'id' => 2,
            'name' => 'Operator',
            'username' => 'op',
            'role' => 'operator',
            'permissions' => ['purchase' => true, 'manage_api' => false],
        ];
        $_SESSION['last_activity'] = time();

        $this->assertTrue(Auth::can('purchase'));
        $this->assertFalse(Auth::can('manage_api'));
    }

    public function testPortalLoginAndLogoutManageCustomerSession(): void
    {
        Auth::loginPortal(42);
        $this->assertSame(42, Auth::portalCustomerId());

        Auth::logoutPortal();
        $this->assertNull(Auth::portalCustomerId());
    }
}
