<?php
declare(strict_types=1);

namespace App\Security;

use App\Http\Response;

final class Auth
{
    public const ADMIN = 1;
    public const EMPLOYEE = 2;
    public const SUPERUSER = 3;
    public const OWNER = 4;

    private const ROLE_PERMISSIONS = [
        self::ADMIN => [
            'inventory.view', 'inventory.adjust', 'products.manage',
            'orders.view', 'orders.create', 'orders.update',
            'sales.view', 'sales.create', 'reports.view', 'cash.adjust', 'users.manage', 'clients.manage',
        ],
        self::EMPLOYEE => [
            'inventory.view',
            'orders.view', 'orders.create', 'orders.update',
            'sales.view', 'sales.create', 'reports.view',
        ],
        self::SUPERUSER => ['*'],
        self::OWNER => ['inventory.view', 'orders.view', 'sales.view', 'reports.view'],
    ];

    public static function requireLogin(bool $json = true): void
    {
        if (isset($_SESSION['id_usuario'])) return;
        if ($json) Response::json(['status' => 'error', 'message' => 'Sesion no valida.'], 401);
        Response::redirect('../../views/auth/login.php');
    }

    public static function requireRoles(array $roles): void
    {
        self::requireLogin();
        if (!in_array((int) ($_SESSION['id_rol'] ?? 0), $roles, true)) {
            Response::json(['status' => 'error', 'message' => 'No tienes permiso para realizar esta accion.'], 403);
        }
    }

    public static function hasPermission(string $permission): bool
    {
        $role = (int) ($_SESSION['id_rol'] ?? 0);
        if ($role === self::EMPLOYEE && $permission === 'inventory.adjust') return false;

        $permissions = isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])
            ? $_SESSION['permissions']
            : (self::ROLE_PERMISSIONS[$role] ?? []);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public static function canAccessBranch(int $branchId, ?string $permission = null): bool
    {
        if ($branchId <= 0) return false;
        if ($permission !== null && !self::hasPermission($permission)) return false;

        $role = (int) ($_SESSION['id_rol'] ?? 0);
        if ($role === self::SUPERUSER) return true;
        if ($permission === 'inventory.view' && self::hasPermission('inventory.view_all')) return true;

        return in_array($branchId, self::sessionBranches(), true);
    }

    public static function allowedBranches(?string $permission = null): ?array
    {
        $role = (int) ($_SESSION['id_rol'] ?? 0);
        if ($permission !== null && !self::hasPermission($permission)) return [];
        if ($role === self::SUPERUSER) return null;
        if ($permission === 'inventory.view' && self::hasPermission('inventory.view_all')) return null;
        return self::sessionBranches();
    }

    public static function requireBranch(int $branchId, ?string $permission = null): void
    {
        self::requireLogin();
        if (!self::canAccessBranch($branchId, $permission)) {
            Response::json(['status' => 'error', 'message' => 'No tienes acceso a la sucursal seleccionada.'], 403);
        }
    }

    public static function requirePermission(string $permission, ?int $branchId = null): void
    {
        self::requireLogin();
        if (!self::hasPermission($permission)) {
            Response::json(['status' => 'error', 'message' => 'No tienes permiso para realizar esta accion.'], 403);
        }
        if ($branchId !== null) self::requireBranch($branchId, $permission);
    }

    private static function sessionBranches(): array
    {
        $branches = array_map('intval', $_SESSION['sucursales'] ?? []);
        $singleBranch = (int) ($_SESSION['id_sucursal'] ?? 0);
        if ($singleBranch > 0) $branches[] = $singleBranch;
        return array_values(array_unique(array_filter($branches, static fn (int $id): bool => $id > 0)));
    }
}
