<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Http\Response;
use App\Http\Validator;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;

final class ClienteController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function listarTodos(): array
    {
        Auth::requirePermission('clients.manage');
        return $this->db->query('SELECT id_cliente, nombre_completo, telefono, email, estado FROM clientes ORDER BY nombre_completo')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(): void
    {
        if (!Auth::hasPermission('orders.create') && !Auth::hasPermission('clients.manage')) {
            Response::json(['status' => 'error', 'message' => 'No tienes permiso para crear clientes.'], 403);
        }
        [$name, $phone, $email] = $this->validatedInput($_POST);
        try {
            $stmt = $this->db->prepare('INSERT INTO clientes (nombre_completo, telefono, email) VALUES (?, ?, ?)');
            $stmt->execute([$name, $phone, $email]);
            $id = (int) $this->db->lastInsertId();
            (new AuditService($this->db))->record('client.created', 'clientes', $id, null, ['nombre' => $name]);

            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                Response::json(['status' => 'success', 'message' => 'Cliente creado.', 'id_cliente' => $id]);
            }
            Response::redirect('index.php?view=pedidos-nuevo&status=client_ok');
        } catch (PDOException $e) {
            App\Support\Logger::error($e);
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                Response::json(['status' => 'error', 'message' => 'No fue posible crear el cliente.'], 500);
            }
            Response::redirect('index.php?view=pedidos-nuevo&status=error');
        }
    }

    public function actualizar(): void
    {
        Auth::requirePermission('clients.manage');
        $id = Validator::positiveInt($_POST['id_cliente'] ?? null, 'cliente');
        [$name, $phone, $email] = $this->validatedInput($_POST);
        $stmt = $this->db->prepare('UPDATE clientes SET nombre_completo = ?, telefono = ?, email = ? WHERE id_cliente = ?');
        $stmt->execute([$name, $phone, $email, $id]);
        if ($stmt->rowCount() === 0 && !$this->exists($id)) throw new InvalidArgumentException('El cliente no existe.');
        (new AuditService($this->db))->record('client.updated', 'clientes', $id, null, ['nombre' => $name]);
        Response::json(['status' => 'success', 'message' => 'Cliente actualizado.']);
    }

    public function cambiarEstado(): void
    {
        Auth::requirePermission('clients.manage');
        $id = Validator::positiveInt($_POST['id_cliente'] ?? null, 'cliente');
        $state = Validator::enum($_POST['estado'] ?? '', ['Activo', 'Inactivo'], 'estado');
        $stmt = $this->db->prepare('UPDATE clientes SET estado = ? WHERE id_cliente = ?');
        $stmt->execute([$state, $id]);
        if ($stmt->rowCount() === 0 && !$this->exists($id)) throw new InvalidArgumentException('El cliente no existe.');
        (new AuditService($this->db))->record('client.state_changed', 'clientes', $id, null, ['estado' => $state]);
        Response::json(['status' => 'success', 'message' => 'Estado actualizado.']);
    }

    private function validatedInput(array $input): array
    {
        $name = Validator::text($input['nombre'] ?? '', 'nombre', 150);
        $phone = Validator::text($input['telefono'] ?? '', 'telefono', 20, false);
        if ($phone !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $phone)) throw new InvalidArgumentException('El telefono no es valido.');
        $email = trim((string) ($input['email'] ?? ''));
        if ($email !== '' && (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            throw new InvalidArgumentException('El correo no es valido.');
        }
        return [$name, $phone !== '' ? $phone : null, $email !== '' ? $email : null];
    }

    private function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM clientes WHERE id_cliente = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }
}

if (isset($_GET['action'])) {
    Auth::requireLogin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    Csrf::validateRequest();
    $controller = new ClienteController();
    match ((string) $_GET['action']) {
        'guardar' => $controller->guardar(),
        'actualizar' => $controller->actualizar(),
        'cambiar_estado' => $controller->cambiarEstado(),
        default => Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404),
    };
}
