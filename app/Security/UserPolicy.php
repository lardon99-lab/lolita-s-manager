<?php
declare(strict_types=1);

namespace App\Security;

use App\Http\Response;
use App\Http\Validator;
use PDO;

final class UserPolicy
{
    public static function assignableRoles(): array
    {
        return (int) ($_SESSION['id_rol'] ?? 0) === Auth::SUPERUSER
            ? [Auth::ADMIN, Auth::EMPLOYEE, Auth::SUPERUSER, Auth::OWNER]
            : [Auth::EMPLOYEE];
    }

    public static function requireAssignableRole(int $roleId): void
    {
        Auth::requirePermission('users.manage');
        if (!in_array($roleId, self::assignableRoles(), true)) {
            Response::json(['status' => 'error', 'message' => 'No puedes asignar ese rol.'], 403);
        }
    }

    public static function requireManageTarget(PDO $db, int $userId): array
    {
        Auth::requirePermission('users.manage');
        $stmt = $db->prepare(
            'SELECT u.id_usuario, u.id_rol, u.id_sucursal, u.estado_usuario
             FROM usuarios u WHERE u.id_usuario = ?'
        );
        $stmt->execute([$userId]);
        $target = $stmt->fetch();
        if (!$target) Response::json(['status' => 'error', 'message' => 'Usuario no encontrado.'], 404);

        if ((int) ($_SESSION['id_rol'] ?? 0) === Auth::SUPERUSER) return $target;
        if ((int) $target['id_rol'] !== Auth::EMPLOYEE) {
            Response::json(['status' => 'error', 'message' => 'No puedes administrar esta cuenta.'], 403);
        }

        $branches = self::targetBranches($db, $userId, (int) ($target['id_sucursal'] ?? 0));
        $allowed = Auth::allowedBranches('users.manage') ?? [];
        if ($branches === [] || array_diff($branches, $allowed) !== []) {
            Response::json(['status' => 'error', 'message' => 'No puedes administrar esta cuenta.'], 403);
        }

        return $target;
    }

    public static function validateAssignments(int $roleId, array $branchIds): array
    {
        $branchIds = Validator::idList($branchIds, 'sucursales', false);
        if ($roleId === Auth::SUPERUSER) return [];
        if ($branchIds === []) Response::json(['status' => 'error', 'message' => 'Debes asignar al menos una sucursal.'], 422);
        if ($roleId === Auth::EMPLOYEE && count($branchIds) !== 1) {
            Response::json(['status' => 'error', 'message' => 'Un empleado debe tener exactamente una sucursal.'], 422);
        }
        foreach ($branchIds as $branchId) Auth::requireBranch($branchId, 'users.manage');
        return $branchIds;
    }

    private static function targetBranches(PDO $db, int $userId, int $fallbackBranch): array
    {
        $stmt = $db->prepare('SELECT id_sucursal FROM usuario_sucursales WHERE id_usuario = ?');
        $stmt->execute([$userId]);
        $branches = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if ($branches === [] && $fallbackBranch > 0) $branches[] = $fallbackBranch;
        return array_values(array_unique($branches));
    }
}
