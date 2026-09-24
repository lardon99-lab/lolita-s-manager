<?php
declare(strict_types=1);

use App\Http\Response;
use App\Http\Validator;
use App\Http\Input\UserInput;
use App\Security\Auth;
use App\Security\Csrf;
use App\Security\UserPolicy;
use App\Services\AuditService;

require_once __DIR__ . '/../core/Database.php';

final class UsuarioController
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Database())->getConnection();
    }

    public function listar(): array
    {
        Auth::requirePermission('users.manage');
        $params = [];
        $where = '';
        if ((int) ($_SESSION['id_rol'] ?? 0) !== Auth::SUPERUSER) {
            $allowed = Auth::allowedBranches('users.manage') ?? [];
            if ($allowed === []) return [];
            $holders = implode(',', array_fill(0, count($allowed), '?'));
            $where = "WHERE u.id_rol = ? AND EXISTS (
                          SELECT 1 FROM usuario_sucursales scope
                          WHERE scope.id_usuario = u.id_usuario AND scope.id_sucursal IN ($holders)
                      )";
            $params = [Auth::EMPLOYEE, ...$allowed];
        }

        $query = "SELECT u.id_usuario, u.nombre_usuario, u.nombre_real, u.estado_usuario, u.id_rol,
                         r.nombre_rol, s.nombre_sucursal,
                         (SELECT GROUP_CONCAT(s2.nombre_sucursal ORDER BY s2.nombre_sucursal SEPARATOR ', ')
                          FROM usuario_sucursales us
                          JOIN sucursales s2 ON us.id_sucursal = s2.id_sucursal
                          WHERE us.id_usuario = u.id_usuario) AS sucursales_admin
                  FROM usuarios u
                  JOIN roles r ON u.id_rol = r.id_rol
                  LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
                  $where
                  ORDER BY u.id_usuario DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerSucursales(): array
    {
        $allowed = Auth::allowedBranches('users.manage');
        if ($allowed === null) {
            return $this->db->query("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' ORDER BY nombre_sucursal")->fetchAll();
        }
        if ($allowed === []) return [];
        $holders = implode(',', array_fill(0, count($allowed), '?'));
        $stmt = $this->db->prepare("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' AND id_sucursal IN ($holders) ORDER BY nombre_sucursal");
        $stmt->execute($allowed);
        return $stmt->fetchAll();
    }

    public function obtenerRolesAsignables(): array
    {
        $roles = UserPolicy::assignableRoles();
        $holders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->db->prepare("SELECT id_rol, nombre_rol FROM roles WHERE id_rol IN ($holders) ORDER BY id_rol");
        $stmt->execute($roles);
        return $stmt->fetchAll();
    }

    public function registrarAjax(): void
    {
        try {
            $input = UserInput::create($_POST);
            $name = $input['username'];
            $realName = $input['real_name'];
            $password = $input['password'];
            $roleId = $input['role_id'];
            UserPolicy::requireAssignableRole($roleId);
            $branches = UserPolicy::validateAssignments($roleId, $input['branch_ids']);

            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "INSERT INTO usuarios (nombre_usuario, password_hash, id_rol, id_sucursal, nombre_real, estado_usuario)
                 VALUES (?, ?, ?, ?, ?, 'Activo')"
            );
            $directBranch = $roleId === Auth::EMPLOYEE ? $branches[0] : null;
            $stmt->execute([$name, password_hash($password, PASSWORD_DEFAULT), $roleId, $directBranch, $realName]);
            $userId = (int) $this->db->lastInsertId();
            $this->syncBranches($userId, $branches);
            if ((int) ($_SESSION['id_rol'] ?? 0) === Auth::SUPERUSER) {
                $this->syncGlobalInventoryPermission($userId, !empty($_POST['inventory_view_all']));
            }
            $this->db->commit();

            (new AuditService($this->db))->record('create', 'usuario', $userId, $directBranch, ['role_id' => $roleId, 'branches' => $branches]);
            Response::json(['status' => 'success', 'message' => 'Usuario registrado correctamente.'], 201);
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            App\Support\Logger::error($error);
            $message = $error instanceof InvalidArgumentException ? $error->getMessage() : 'No fue posible registrar el usuario.';
            Response::json(['status' => 'error', 'message' => $message], 422);
        }
    }

    public function obtenerAjax(): void
    {
        $id = Validator::positiveInt($_GET['id'] ?? null, 'usuario');
        UserPolicy::requireManageTarget($this->db, $id);
        $stmt = $this->db->prepare('SELECT id_usuario, nombre_usuario, nombre_real, id_rol, id_sucursal, estado_usuario FROM usuarios WHERE id_usuario = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        $branchStmt = $this->db->prepare('SELECT id_sucursal FROM usuario_sucursales WHERE id_usuario = ? ORDER BY id_sucursal');
        $branchStmt->execute([$id]);
        $branches = array_map('intval', $branchStmt->fetchAll(PDO::FETCH_COLUMN));
        if ($branches === [] && !empty($user['id_sucursal'])) $branches[] = (int) $user['id_sucursal'];

        $permissionStmt = $this->db->prepare("SELECT COUNT(*) FROM usuario_permisos up JOIN permisos p ON p.id_permiso = up.id_permiso WHERE up.id_usuario = ? AND p.codigo = 'inventory.view_all'");
        $permissionStmt->execute([$id]);
        Response::json([
            'status' => 'success',
            'usuario' => $user,
            'sucursales' => $branches,
            'inventory_view_all' => (bool) $permissionStmt->fetchColumn(),
        ]);
    }

    public function editarAjax(): void
    {
        try {
            $input = UserInput::update($_POST);
            $id = $input['id'];
            $target = UserPolicy::requireManageTarget($this->db, $id);
            $name = $input['username'];
            $realName = $input['real_name'];
            $status = $input['status'];
            $roleId = $input['role_id'];
            UserPolicy::requireAssignableRole($roleId);
            $branches = UserPolicy::validateAssignments($roleId, $input['branch_ids']);
            if ($id === (int) ($_SESSION['id_usuario'] ?? 0) && $status !== 'Activo') {
                throw new InvalidArgumentException('No puedes desactivar tu propia cuenta.');
            }
            $this->protectLastSuperuser($target, $roleId, $status);

            $this->db->beginTransaction();
            $directBranch = $roleId === Auth::EMPLOYEE ? $branches[0] : null;
            $stmt = $this->db->prepare('UPDATE usuarios SET nombre_usuario = ?, nombre_real = ?, id_rol = ?, id_sucursal = ?, estado_usuario = ? WHERE id_usuario = ?');
            $stmt->execute([$name, $realName, $roleId, $directBranch, $status, $id]);
            $this->syncBranches($id, $branches);
            if ((int) ($_SESSION['id_rol'] ?? 0) === Auth::SUPERUSER) {
                $this->syncGlobalInventoryPermission($id, !empty($_POST['inventory_view_all']));
            }
            $this->db->commit();

            (new AuditService($this->db))->record('update', 'usuario', $id, $directBranch, ['role_id' => $roleId, 'branches' => $branches, 'status' => $status]);
            Response::json(['status' => 'success', 'message' => 'Usuario actualizado correctamente.']);
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            App\Support\Logger::error($error);
            $message = $error instanceof InvalidArgumentException ? $error->getMessage() : 'No fue posible editar el usuario.';
            Response::json(['status' => 'error', 'message' => $message], 422);
        }
    }

    public function cambiarPasswordAjax(): void
    {
        try {
            $id = Validator::positiveInt($_POST['id_usuario'] ?? null, 'usuario');
            UserPolicy::requireManageTarget($this->db, $id);
            $password = Validator::password($_POST['nueva_password'] ?? '');
            $stmt = $this->db->prepare('UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            (new AuditService($this->db))->record('password_reset', 'usuario', $id);
            Response::json(['status' => 'success', 'message' => 'Contrasena actualizada.']);
        } catch (Throwable $error) {
            App\Support\Logger::error($error);
            $message = $error instanceof InvalidArgumentException ? $error->getMessage() : 'No fue posible cambiar la contrasena.';
            Response::json(['status' => 'error', 'message' => $message], 422);
        }
    }

    private function syncBranches(int $userId, array $branches): void
    {
        $this->db->prepare('DELETE FROM usuario_sucursales WHERE id_usuario = ?')->execute([$userId]);
        $stmt = $this->db->prepare('INSERT INTO usuario_sucursales (id_usuario, id_sucursal) VALUES (?, ?)');
        foreach ($branches as $branchId) $stmt->execute([$userId, $branchId]);
    }

    private function syncGlobalInventoryPermission(int $userId, bool $enabled): void
    {
        $permissionId = $this->db->query("SELECT id_permiso FROM permisos WHERE codigo = 'inventory.view_all'")->fetchColumn();
        if ($permissionId === false) throw new RuntimeException('No se encontro el permiso de inventario global.');
        $this->db->prepare('DELETE FROM usuario_permisos WHERE id_usuario = ? AND id_permiso = ?')->execute([$userId, $permissionId]);
        if ($enabled) $this->db->prepare('INSERT INTO usuario_permisos (id_usuario, id_permiso) VALUES (?, ?)')->execute([$userId, $permissionId]);
    }

    private function protectLastSuperuser(array $target, int $newRoleId, string $newStatus): void
    {
        if ((int) $target['id_rol'] !== Auth::SUPERUSER || ($newRoleId === Auth::SUPERUSER && $newStatus === 'Activo')) return;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE id_rol = ? AND estado_usuario = 'Activo' AND id_usuario <> ?");
        $stmt->execute([Auth::SUPERUSER, $target['id_usuario']]);
        if ((int) $stmt->fetchColumn() === 0) throw new InvalidArgumentException('No puedes desactivar o degradar al ultimo superusuario activo.');
    }
}

$action = $_GET['action'] ?? null;
if ($action !== null) {
    Auth::requirePermission('users.manage');
    $postActions = ['registrarAjax', 'editarAjax', 'cambiarPasswordAjax'];
    if (in_array($action, $postActions, true)) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
        Csrf::validateRequest();
    } elseif ($action !== 'obtenerAjax' || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    }

    $controller = new UsuarioController();
    match ($action) {
        'registrarAjax' => $controller->registrarAjax(),
        'obtenerAjax' => $controller->obtenerAjax(),
        'editarAjax' => $controller->editarAjax(),
        'cambiarPasswordAjax' => $controller->cambiarPasswordAjax(),
        default => Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404),
    };
}
