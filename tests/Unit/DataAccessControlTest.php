<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Auth;
use App\Repositories\CustomerRepository;
use App\Repositories\ReportRepository;
use App\Services\DataAccessControl;
use PHPUnit\Framework\TestCase;

final class DataAccessControlTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testDefaultOperatorScopeFiltersToOwnCreatedRecords(): void
    {
        $_SESSION['user'] = [
            'id' => 7,
            'role' => 'operator',
            'permissions' => Auth::permissionsFromUser(['role' => 'operator', 'permissions' => null]),
        ];

        $where = [];
        $params = [];
        DataAccessControl::applyOwnerScope($where, $params, 'c.created_by');

        $this->assertContains('c.created_by IN (:acl_user_0)', $where);
        $this->assertSame(7, $params['acl_user_0']);
    }

    public function testSelectedUserScopeIncludesOwnAndAllowedUsers(): void
    {
        $_SESSION['user'] = [
            'id' => 7,
            'role' => 'operator',
            'permissions' => Auth::permissionsFromUser([
                'role' => 'operator',
                'permissions' => json_encode([
                    'data_access_scope' => 'selected',
                    'data_access_user_ids' => [9, '11', 7],
                ], JSON_UNESCAPED_UNICODE),
            ]),
        ];

        $where = [];
        $params = [];
        DataAccessControl::applyOwnerScope($where, $params, 'p.created_by');

        $this->assertContains('p.created_by IN (:acl_user_0, :acl_user_1, :acl_user_2)', $where);
        $this->assertSame([7, 9, 11], array_values($params));
    }

    public function testAdminAndAllDataScopeDoNotAddOwnerFilter(): void
    {
        $_SESSION['user'] = [
            'id' => 2,
            'role' => 'operator',
            'permissions' => Auth::permissionsFromUser([
                'role' => 'operator',
                'permissions' => json_encode(['data_access_scope' => 'all'], JSON_UNESCAPED_UNICODE),
            ]),
        ];

        $where = ['c.deleted_at IS NULL'];
        $params = [];
        DataAccessControl::applyOwnerScope($where, $params, 'c.created_by');

        $this->assertSame(['c.deleted_at IS NULL'], $where);
        $this->assertSame([], $params);
    }

    public function testCustomerSearchFiltersUseCreatedByScope(): void
    {
        $_SESSION['user'] = [
            'id' => 7,
            'role' => 'operator',
            'permissions' => Auth::permissionsFromUser(['role' => 'operator', 'permissions' => null]),
        ];
        $repo = (new \ReflectionClass(CustomerRepository::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(CustomerRepository::class, 'filters');
        $method->setAccessible(true);

        [$where, $params] = $method->invoke($repo, []);

        $this->assertContains('c.created_by IN (:acl_user_0)', $where);
        $this->assertSame(7, $params['acl_user_0']);
    }

    public function testReportPurchaseFiltersUseCreatedByScope(): void
    {
        $_SESSION['user'] = [
            'id' => 7,
            'role' => 'operator',
            'permissions' => Auth::permissionsFromUser(['role' => 'operator', 'permissions' => null]),
        ];
        $repo = (new \ReflectionClass(ReportRepository::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(ReportRepository::class, 'purchaseFilters');
        $method->setAccessible(true);

        [$where, $params] = $method->invoke($repo, []);

        $this->assertContains('p.created_by IN (:acl_user_0)', $where);
        $this->assertSame(7, $params['acl_user_0']);
    }

    public function testModifyOthersRequiresExplicitPermissionAndVisibility(): void
    {
        $_SESSION['user'] = [
            'id' => 7,
            'role' => 'operator',
            'permissions' => Auth::permissionsFromUser([
                'role' => 'operator',
                'permissions' => json_encode([
                    'data_access_scope' => 'selected',
                    'data_access_user_ids' => [9],
                    'data_access_can_modify_others' => true,
                ], JSON_UNESCAPED_UNICODE),
            ]),
        ];

        $this->assertTrue(DataAccessControl::canModifyOwner(7));
        $this->assertTrue(DataAccessControl::canModifyOwner(9));
        $this->assertFalse(DataAccessControl::canModifyOwner(11));
    }
}
