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
        self::assertSame([2], Auth::allowedBranches());
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
}
