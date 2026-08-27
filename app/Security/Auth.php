<?php
declare(strict_types=1);

namespace App\Security;

use App\Http\Response;

final class Auth
{
    public const ADMIN = 1;
    public const EMPLOYEE = 2;
    public const SUPERUSER = 3;

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

    public static function canAccessBranch(int $branchId): bool
    {
        $role = (int) ($_SESSION['id_rol'] ?? 0);
        if ($role === self::SUPERUSER) return true;
        if ($role === self::ADMIN) return in_array($branchId, array_map('intval', $_SESSION['sucursales'] ?? []), true);
        return $role === self::EMPLOYEE && $branchId === (int) ($_SESSION['id_sucursal'] ?? 0);
    }

    public static function allowedBranches(): ?array
    {
        $role = (int) ($_SESSION['id_rol'] ?? 0);
        if ($role === self::SUPERUSER) return null;
        if ($role === self::ADMIN) return array_values(array_unique(array_map('intval', $_SESSION['sucursales'] ?? [])));
        if ($role === self::EMPLOYEE) return [(int) ($_SESSION['id_sucursal'] ?? 0)];
        return [];
    }

    public static function requireBranch(int $branchId): void
    {
        self::requireLogin();
        if ($branchId <= 0 || !self::canAccessBranch($branchId)) {
            Response::json(['status' => 'error', 'message' => 'No tienes acceso a la sucursal seleccionada.'], 403);
        }
    }
}
