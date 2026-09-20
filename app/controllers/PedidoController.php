<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../Reports/CajaReporteTrait.php';
require_once __DIR__ . '/../Reports/PendientesReporteTrait.php';
require_once __DIR__ . '/../Reports/ReporteCajaPdfTrait.php';

use App\Http\Response;
use App\Http\Validator;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;
use App\Services\PedidoService;
use App\Services\ProductCustomizationService;
use App\Services\ProductDesignService;
use App\Services\OrderDesignStorage;

final class PedidoController
{
    use CajaReporteTrait;
    use PendientesReporteTrait;
    use ReporteCajaPdfTrait;

    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function prepararFormulario(): array
    {
        Auth::requirePermission('orders.create');
        $productos = $this->db->query(
            "SELECT p.id_producto, p.nombre_producto, p.precio_base, p.tipo_producto, c.nombre_categoria,
                    GROUP_CONCAT(DISTINCT i.id_sucursal ORDER BY i.id_sucursal) AS branch_ids
             FROM productos p
             JOIN categorias c ON c.id_categoria = p.id_categoria
             JOIN inventario i ON i.id_producto = p.id_producto
             WHERE p.estado = 'Activo' AND c.estado = 'Activo'
             GROUP BY p.id_producto, p.nombre_producto, p.precio_base, p.tipo_producto, c.nombre_categoria
             ORDER BY p.nombre_producto"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'productos' => $productos,
            'configuraciones' => (new ProductCustomizationService($this->db))->configurationsForProducts(array_column($productos, 'id_producto')),
            'configuraciones_diseno' => (new ProductDesignService($this->db))->configurationsForProducts(array_column($productos, 'id_producto')),
            'sucursales' => $this->branchesFor('orders.create'),
        ];
    }

    public function crear(): void
    {
        (new PedidoService($this->db))->crear($_POST, $_FILES);
    }

    public function obtenerDetalles(int|string $orderId): array
    {
        Auth::requirePermission('orders.view');
        $id = Validator::positiveInt($orderId, 'pedido');
        $params = [':id' => $id];
        $scope = $this->branchScope('o.id_sucursal', Auth::allowedBranches('orders.view'), $params, 'detail');
        $stmt = $this->db->prepare(
            "SELECT d.*, p.nombre_producto,
                    dd.id_diseno, dd.color_descripcion, dd.frase, dd.instrucciones,
                    dd.recargo_unitario AS recargo_diseno, dd.archivo_nombre_interno,
                    (SELECT GROUP_CONCAT(CONCAT(pdo.grupo_nombre, ': ', pdo.opcion_nombre,
                        CASE WHEN pdo.recargo_unitario > 0 THEN CONCAT(' (+ L. ', FORMAT(pdo.recargo_unitario, 2), ')') ELSE '' END)
                        ORDER BY pdo.id_detalle_opcion SEPARATOR ' | ')
                     FROM pedido_detalle_opciones pdo WHERE pdo.id_detalle = d.id_detalle) AS opciones_personalizacion
             FROM pedido_detalles d
             JOIN pedidos o ON o.id_pedido = d.id_pedido
             JOIN productos p ON p.id_producto = d.id_producto
             LEFT JOIN pedido_detalle_diseno dd ON dd.id_detalle = d.id_detalle
             WHERE d.id_pedido = :id{$scope}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verDiseno(): void
    {
        Auth::requirePermission('orders.view');
        $designId = Validator::positiveInt($_GET['id'] ?? null, 'diseno');
        $params = [':id' => $designId];
        $scope = $this->branchScope('p.id_sucursal', Auth::allowedBranches('orders.view'), $params, 'design');
        $stmt = $this->db->prepare(
            "SELECT dd.archivo_nombre_interno, dd.archivo_mime
             FROM pedido_detalle_diseno dd
             JOIN pedido_detalles det ON det.id_detalle = dd.id_detalle
             JOIN pedidos p ON p.id_pedido = det.id_pedido
             WHERE dd.id_diseno = :id{$scope}"
        );
        $stmt->execute($params);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$file || !$file['archivo_nombre_interno']) {
            http_response_code(404);
            exit('Referencia no encontrada.');
        }
        $path = (new OrderDesignStorage(dirname(__DIR__, 2) . '/storage/order-designs'))->path((string) $file['archivo_nombre_interno']);
        if (!is_file($path)) {
            http_response_code(404);
            exit('Referencia no encontrada.');
        }
        header('Content-Type: ' . $file['archivo_mime']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="referencia-diseno"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function listarTodos(string $estado = 'Todos'): array
    {
        Auth::requirePermission('orders.view');
        $allowedStates = ['Todos', 'Pendiente', 'En PreparaciÃ³n', 'Listo', 'Entregado', 'Cancelado'];
        if (!in_array($estado, $allowedStates, true)) $estado = 'Todos';

        $conditions = [];
        $params = [];
        if ($estado !== 'Todos') {
            $conditions[] = 'p.estado = :estado';
            $params[':estado'] = $estado;
        }
        $scope = $this->branchScope('p.id_sucursal', Auth::allowedBranches('orders.view'), $params, 'list');
        if ($scope !== '') $conditions[] = substr($scope, 5);

        $sql = "SELECT p.*, c.nombre_completo AS nombre_cliente, c.telefono
                FROM pedidos p JOIN clientes c ON c.id_cliente = p.id_cliente";
        if ($conditions !== []) $sql .= ' WHERE ' . implode(' AND ', $conditions);
        $sql .= ' ORDER BY p.fecha_entrega ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstadoAjax(): void
    {
        $id = Validator::positiveInt($_POST['id'] ?? null, 'pedido');
        $newState = Validator::enum($_POST['nuevo_estado'] ?? '', ['En PreparaciÃ³n', 'Listo', 'Entregado', 'Cancelado'], 'estado');
        $settle = filter_var($_POST['liquidar'] ?? false, FILTER_VALIDATE_BOOL);
        $paymentMethod = Validator::enum($_POST['metodo_pago'] ?? 'Efectivo', ['Efectivo', 'Transferencia', 'Tarjeta', 'Otro'], 'metodo de pago');

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT id_sucursal, estado, saldo_pendiente FROM pedidos WHERE id_pedido = ? FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) throw new InvalidArgumentException('El pedido no existe.');
            Auth::requirePermission('orders.update', (int) $order['id_sucursal']);

            $transitions = [
                'Pendiente' => ['En PreparaciÃ³n', 'Cancelado'],
                'En PreparaciÃ³n' => ['Listo', 'Cancelado'],
                'Listo' => ['Entregado', 'Cancelado'],
                'Entregado' => [],
                'Cancelado' => [],
            ];
            $currentState = (string) $order['estado'];
            if (!in_array($newState, $transitions[$currentState] ?? [], true)) {
                throw new InvalidArgumentException('La transicion de estado no es valida.');
            }

            $balance = (float) $order['saldo_pendiente'];
            if ($newState === 'Entregado' && $balance > 0 && !$settle) {
                throw new InvalidArgumentException('Debes liquidar el saldo antes de entregar el pedido.');
            }
            if ($settle && $balance <= 0) throw new InvalidArgumentException('El pedido no tiene saldo pendiente.');

            if ($settle) {
                $payment = $this->db->prepare('INSERT INTO pagos_pedido (id_pedido, id_usuario, monto, metodo_pago) VALUES (?, ?, ?, ?)');
                $payment->execute([$id, (int) $_SESSION['id_usuario'], $balance, $paymentMethod]);
            }

            $updates = ['estado = :estado'];
            $params = [':estado' => $newState, ':id' => $id];
            if ($settle) {
                $updates[] = 'monto_abonado = monto_abonado + saldo_pendiente';
                $updates[] = 'saldo_pendiente = 0';
                $updates[] = "estado_pago = 'Pagado'";
            }
            if ($newState === 'Entregado') {
                $updates[] = 'fecha_registro = :delivered_at';
                $params[':delivered_at'] = date('Y-m-d H:i:s');
            }
            $update = $this->db->prepare('UPDATE pedidos SET ' . implode(', ', $updates) . ' WHERE id_pedido = :id');
            $update->execute($params);

            (new AuditService($this->db))->record('order.status_changed', 'pedidos', $id, (int) $order['id_sucursal'], [
                'estado_anterior' => $currentState,
                'estado_nuevo' => $newState,
                'monto_liquidado' => $settle ? $balance : 0,
            ]);
            $this->db->commit();
            Response::json(['status' => 'success', 'message' => 'Estado actualizado correctamente.']);
        } catch (InvalidArgumentException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            App\Support\Logger::error($e);
            Response::json(['status' => 'error', 'message' => 'No fue posible actualizar el pedido.'], 500);
        }
    }

    private function branchesFor(string $permission): array
    {
        $allowed = Auth::allowedBranches($permission);
        if ($allowed === null) {
            return $this->db->query("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' ORDER BY nombre_sucursal")->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($allowed === []) return [];
        $holders = implode(',', array_fill(0, count($allowed), '?'));
        $stmt = $this->db->prepare("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' AND id_sucursal IN ({$holders}) ORDER BY nombre_sucursal");
        $stmt->execute($allowed);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function branchScope(string $column, ?array $allowed, array &$params, string $prefix): string
    {
        if ($allowed === null) return '';
        if ($allowed === []) return ' AND 1 = 0';
        $holders = [];
        foreach ($allowed as $index => $branchId) {
            $key = ':' . $prefix . '_branch_' . $index;
            $holders[] = $key;
            $params[$key] = $branchId;
        }
        return ' AND ' . $column . ' IN (' . implode(',', $holders) . ')';
    }
}

if (isset($_GET['action'])) {
    Auth::requireLogin();
    $action = (string) $_GET['action'];
    $postActions = ['crear', 'actualizar_estado_ajax', 'guardarMerma'];
    if (in_array($action, $postActions, true)) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
        Csrf::validateRequest();
    }

    $controller = new PedidoController();
    match ($action) {
        'crear' => $controller->crear(),
        'actualizar_estado_ajax' => $controller->actualizarEstadoAjax(),
        'reporte_pendientes' => $controller->generarReportePendientes(),
        'ver_diseno' => $controller->verDiseno(),
        'guardarMerma' => $controller->guardarMerma(),
        'exportarPdf' => $controller->descargarReportePDF($_GET),
        default => Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404),
    };
}
