<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Http\Response;
use App\Http\Validator;
use App\Http\Input\ClientInput;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;
use App\Services\ClientService;

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
        try {
            $client = (new ClientService($this->db))->create($_POST);
            $id = (int) $client['id_cliente'];
            (new AuditService($this->db))->record('client.created', 'clientes', $id, null, ['nombre' => $client['nombre_completo']]);

            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                Response::json(['status' => 'success', 'message' => 'Cliente creado.', 'cliente' => $client]);
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

    public function buscar(): void
    {
        if (!Auth::hasPermission('orders.create') && !Auth::hasPermission('clients.manage')) {
            Response::json(['status' => 'error', 'message' => 'No tienes permiso para consultar clientes.'], 403);
        }
        $term = (string) ($_GET['q'] ?? '');
        Response::json([
            'status' => 'success',
            'clientes' => (new ClientService($this->db))->search($term),
        ]);
    }

    public function actualizar(): void
    {
        Auth::requirePermission('clients.manage');
        $id = Validator::positiveInt($_POST['id_cliente'] ?? null, 'cliente');
        $input = ClientInput::from($_POST);
        $stmt = $this->db->prepare('UPDATE clientes SET nombre_completo = ?, telefono = ?, email = ? WHERE id_cliente = ?');
        $stmt->execute([$input['name'], $input['phone'], $input['email'], $id]);
        if ($stmt->rowCount() === 0 && !$this->exists($id)) throw new InvalidArgumentException('El cliente no existe.');
        (new AuditService($this->db))->record('client.updated', 'clientes', $id, null, ['nombre' => $input['name']]);
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

    private function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM clientes WHERE id_cliente = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }
}

if (isset($_GET['action'])) {
    Auth::requireLogin();
    $controller = new ClienteController();
    if ((string) $_GET['action'] === 'buscar') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
        $controller->buscar();
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    Csrf::validateRequest();
    match ((string) $_GET['action']) {
        'guardar' => $controller->guardar(),
        'actualizar' => $controller->actualizar(),
        'cambiar_estado' => $controller->cambiarEstado(),
        default => Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404),
    };
}
