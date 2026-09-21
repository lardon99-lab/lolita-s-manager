<?php
declare(strict_types=1);

use App\Security\Auth;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testEmployeeCanOnlyAccessAssignedBranch(): void
    {
        $_SESSION = ['id_rol' => Auth::EMPLOYEE, 'id_sucursal' => 2];
        self::assertTrue(Auth::canAccessBranch(2));
        self::assertFalse(Auth::canAccessBranch(1));
        self::assertFalse(Auth::hasPermission('inventory.adjust'));
        self::assertFalse(Auth::canAccessBranch(2, 'inventory.adjust'));
        self::assertSame([2], Auth::allowedBranches());
    }

    public function testEmployeeCannotAdjustInventoryWithStaleSessionPermission(): void
    {
        $_SESSION = [
            'id_rol' => Auth::EMPLOYEE,
            'id_sucursal' => 2,
            'permissions' => ['inventory.view', 'inventory.adjust'],
        ];

        self::assertFalse(Auth::hasPermission('inventory.adjust'));
        self::assertFalse(Auth::canAccessBranch(2, 'inventory.adjust'));
    }

    public function testAdminUsesExplicitBranchAssignments(): void
    {
        $_SESSION = ['id_rol' => Auth::ADMIN, 'sucursales' => ['1', 3, 3]];
        self::assertTrue(Auth::canAccessBranch(1));
        self::assertFalse(Auth::canAccessBranch(2));
        self::assertSame([1, 3], Auth::allowedBranches());
    }

    public function testSuperuserHasGlobalScope(): void
    {
        $_SESSION = ['id_rol' => Auth::SUPERUSER];
        self::assertTrue(Auth::canAccessBranch(999));
        self::assertNull(Auth::allowedBranches());
    }

    public function testOwnerOnlyReadsAssignedBranches(): void
    {
        $_SESSION = [
            'id_rol' => Auth::OWNER,
            'sucursales' => [3],
            'permissions' => ['inventory.view', 'orders.view', 'sales.view'],
        ];

        self::assertTrue(Auth::canAccessBranch(3, 'inventory.view'));
        self::assertFalse(Auth::canAccessBranch(1, 'inventory.view'));
        self::assertFalse(Auth::canAccessBranch(3, 'inventory.adjust'));
        self::assertSame([3], Auth::allowedBranches('orders.view'));
    }

    public function testInventoryViewAllDoesNotGrantMutationAccess(): void
    {
        $_SESSION = [
            'id_rol' => Auth::OWNER,
            'sucursales' => [3],
            'permissions' => ['inventory.view', 'inventory.view_all'],
        ];

        self::assertNull(Auth::allowedBranches('inventory.view'));
        self::assertTrue(Auth::canAccessBranch(1, 'inventory.view'));
        self::assertFalse(Auth::canAccessBranch(1, 'inventory.adjust'));
    }
}
