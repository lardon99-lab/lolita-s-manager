<?php
declare(strict_types=1);

use App\Security\OrderStatusPolicy;
use PHPUnit\Framework\TestCase;

final class OrderStatusPolicyTest extends TestCase
{
    public function testAdminPermissionFinishesPendingOrder(): void
    {
        self::assertSame('orders.finish', OrderStatusPolicy::requiredPermission('Pendiente', 'Terminado'));
    }

    public function testEmployeePermissionDeliversFinishedOrder(): void
    {
        self::assertSame('orders.deliver', OrderStatusPolicy::requiredPermission('Terminado', 'Entregado'));
    }

    public function testSkippingOrReversingStatesIsRejected(): void
    {
        self::assertNull(OrderStatusPolicy::requiredPermission('Pendiente', 'Entregado'));
        self::assertNull(OrderStatusPolicy::requiredPermission('Terminado', 'Pendiente'));
        self::assertSame([], OrderStatusPolicy::transitions('Entregado'));
    }

    public function testTransitionsAreRestrictedToTheirResponsibleRoles(): void
    {
        self::assertTrue(OrderStatusPolicy::roleCanTransition(\App\Security\Auth::ADMIN, 'Pendiente', 'Terminado'));
        self::assertFalse(OrderStatusPolicy::roleCanTransition(\App\Security\Auth::EMPLOYEE, 'Pendiente', 'Terminado'));
        self::assertTrue(OrderStatusPolicy::roleCanTransition(\App\Security\Auth::EMPLOYEE, 'Terminado', 'Entregado'));
        self::assertFalse(OrderStatusPolicy::roleCanTransition(\App\Security\Auth::ADMIN, 'Terminado', 'Entregado'));
    }
}
