<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Http\Response;
use App\Http\Validator;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;

final class SucursalController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function listar(): array
    {
        Auth::requirePermission('branches.manage');
        return $this->db->query('SELECT id_sucursal, nombre_sucursal, direccion, telefono, estado FROM sucursales ORDER BY nombre_sucursal')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(): void
    {
        Auth::requirePermission('branches.manage');
        [$name, $address, $phone] = $this->validatedInput($_POST);
        $stmt = $this->db->prepare('INSERT INTO sucursales (nombre_sucursal, direccion, telefono) VALUES (?, ?, ?)');
        $stmt->execute([$name, $address, $phone]);
        $id = (int) $this->db->lastInsertId();
        (new AuditService($this->db))->record('branch.created', 'sucursales', $id, $id, ['nombre' => $name]);
        Response::json(['status' => 'success', 'message' => 'Sucursal creada.']);
    }

    public function actualizar(): void
    {
        Auth::requirePermission('branches.manage');
        $id = Validator::positiveInt($_POST['id_sucursal'] ?? null, 'sucursal');
        [$name, $address, $phone] = $this->validatedInput($_POST);
        $stmt = $this->db->prepare('UPDATE sucursales SET nombre_sucursal = ?, direccion = ?, telefono = ? WHERE id_sucursal = ?');
        $stmt->execute([$name, $address, $phone, $id]);
        (new AuditService($this->db))->record('branch.updated', 'sucursales', $id, $id, ['nombre' => $name]);
        Response::json(['status' => 'success', 'message' => 'Sucursal actualizada.']);
    }

    public function cambiarEstado(): void
    {
        Auth::requirePermission('branches.manage');
        $id = Validator::positiveInt($_POST['id_sucursal'] ?? null, 'sucursal');
        $state = Validator::enum($_POST['estado'] ?? '', ['Activa', 'Inactiva'], 'estado');
        $stmt = $this->db->prepare('UPDATE sucursales SET estado = ? WHERE id_sucursal = ?');
        $stmt->execute([$state, $id]);
        (new AuditService($this->db))->record('branch.state_changed', 'sucursales', $id, $id, ['estado' => $state]);
        Response::json(['status' => 'success', 'message' => 'Estado actualizado.']);
    }

    private function validatedInput(array $input): array
    {
        $name = Validator::text($input['nombre'] ?? '', 'nombre', 100);
        $address = Validator::text($input['direccion'] ?? '', 'direccion', 500, false);
        $phone = Validator::text($input['telefono'] ?? '', 'telefono', 20, false);
        if ($phone !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $phone)) throw new InvalidArgumentException('El telefono no es valido.');
        return [$name, $address !== '' ? $address : null, $phone !== '' ? $phone : null];
    }
}

if (isset($_GET['action'])) {
    Auth::requireLogin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    Csrf::validateRequest();
    $controller = new SucursalController();
    match ((string) $_GET['action']) {
        'guardar' => $controller->guardar(),
        'actualizar' => $controller->actualizar(),
        'cambiar_estado' => $controller->cambiarEstado(),
        default => Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404),
    };
}
