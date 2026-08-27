<?php
// app/controllers/PedidoController.php
require_once __DIR__ . '/../../vendor/autoload.php'; 

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../Reports/CajaReporteTrait.php';
require_once __DIR__ . '/../Reports/PendientesReporteTrait.php';
require_once __DIR__ . '/../Reports/ReporteCajaPdfTrait.php';

use Dompdf\Dompdf; 
use Dompdf\Options;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\PedidoService;

class PedidoController {
    use CajaReporteTrait;
    use PendientesReporteTrait;
    use ReporteCajaPdfTrait;

    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Carga clientes y productos para el formulario
    public function prepararFormulario() {
    $clientes = $this->db->query("SELECT id_cliente, nombre_completo FROM clientes")->fetchAll(PDO::FETCH_ASSOC);
    $productos = $this->db->query("SELECT id_producto, nombre_producto, precio_base FROM productos")->fetchAll(PDO::FETCH_ASSOC);
    // Agregamos la consulta de sucursales
    $allowedBranches = Auth::allowedBranches();
    if ($allowedBranches === null) {
        $sucursales = $this->db->query("SELECT id_sucursal, nombre_sucursal FROM sucursales ORDER BY nombre_sucursal")->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($allowedBranches === []) {
        $sucursales = [];
    } else {
        $holders = implode(',', array_fill(0, count($allowedBranches), '?'));
        $branchStmt = $this->db->prepare("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE id_sucursal IN ($holders) ORDER BY nombre_sucursal");
        $branchStmt->execute($allowedBranches);
        $sucursales = $branchStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return [
        'clientes' => $clientes, 
        'productos' => $productos,
        'sucursales' => $sucursales
    ];
    }

    public function crear(): void {
        (new PedidoService($this->db))->crear($_POST);
    }

    public function obtenerDetalles($id_pedido) {
        $query = "SELECT d.*, p.nombre_producto 
                FROM pedido_detalles d
                INNER JOIN productos p ON d.id_producto = p.id_producto
                WHERE d.id_pedido = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id_pedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos($estado = 'Todos') {
        try {
            // Obtenemos el rol de forma segura
            $id_rol = (int)($_SESSION['id_rol'] ?? 0);
            
            $query = "SELECT p.*, c.nombre_completo as nombre_cliente, c.telefono 
                    FROM pedidos p
                    INNER JOIN clientes c ON p.id_cliente = c.id_cliente";

            $condiciones = [];
            $params = [];

            // 1. Filtro por Estado (Si no es 'Todos')
            if ($estado !== 'Todos' && !empty($estado)) {
                $condiciones[] = "p.estado = :estado";
                $params[':estado'] = $estado;
            }

            // 2. Filtro de Seguridad por Sucursal (Solo para Empleados)
            // El rol 2 es Empleado. Si es 1 (Admin) o 3 (Super), se salta esta parte.
            if ($id_rol === 2) {
                // Verificamos que la sucursal exista en la sesión para evitar el Warning
                $id_sucursal = (int)($_SESSION['id_sucursal'] ?? 0);
                
                if ($id_sucursal > 0) {
                    $condiciones[] = "p.id_sucursal = :id_sucursal";
                    $params[':id_sucursal'] = $id_sucursal;
                }
            } elseif ($id_rol === Auth::ADMIN) {
                $allowed = Auth::allowedBranches() ?? [];
                if ($allowed === []) return [];
                $branchParams = [];
                foreach ($allowed as $index => $branchId) {
                    $key = ':allowed_branch_' . $index;
                    $branchParams[] = $key;
                    $params[$key] = $branchId;
                }
                $condiciones[] = 'p.id_sucursal IN (' . implode(',', $branchParams) . ')';
            }

            // Unir condiciones si existen
            if (!empty($condiciones)) {
                $query .= " WHERE " . implode(" AND ", $condiciones);
            }

            $query .= " ORDER BY p.fecha_entrega ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            \App\Support\Logger::error($e);
            return [];
        }
    }

    public function actualizarEstado() {
    if (isset($_GET['id']) && isset($_GET['nuevo_estado'])) {
        try {
            $query = "UPDATE pedidos SET estado = :estado WHERE id_pedido = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':estado' => $_GET['nuevo_estado'],
                ':id'     => $_GET['id']
            ]);

            header("Location: index.php?view=pedidos-lista&msg=status_updated", true, 303);
            exit();
        } catch (Exception $e) {
            \App\Support\Logger::error($e);
            die("No fue posible actualizar el pedido.");
        }
    }
}
    public function obtenerMetricasDashboard() {
        // Pedidos pendientes totales
        $pendientes = $this->db->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetchColumn();
        
        // Pedidos para hoy
        date_default_timezone_set('America/Tegucigalpa');
        $hoy = date('Y-m-d');
        $paraHoy = $this->db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_entrega) = '$hoy' AND estado != 'Entregado'")->fetchColumn();
        
        // Ventas completadas del día en zona horaria de Honduras
        date_default_timezone_set('America/Tegucigalpa');
        $hoy = date('Y-m-d');
        $stmtVentas = $this->db->prepare(
            "SELECT COALESCE((SELECT SUM(total_pedido) FROM pedidos WHERE estado = 'Entregado' AND DATE(fecha_registro) = :hoy_pedidos), 0) + COALESCE((SELECT SUM(total) FROM ventas_directas WHERE DATE(fecha_venta) = :hoy_ventas), 0) AS total_ventas"
        );
        $stmtVentas->execute([':hoy_pedidos' => $hoy, ':hoy_ventas' => $hoy]);
        $ventas = (float) $stmtVentas->fetchColumn();

        return [
            'pendientes' => $pendientes,
            'para_hoy'   => $paraHoy,
            'ventas'     => $ventas ?? 0
        ];
    }

    public function actualizarEstadoAjax() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $id = \App\Http\Validator::positiveInt($_GET['id'] ?? null, 'pedido');
        $nuevoEstado = \App\Http\Validator::enum($_GET['nuevo_estado'] ?? '', ['Pendiente', 'En Preparacion', 'En Preparación', 'Listo', 'Entregado', 'Cancelado'], 'estado');
        $liquidar = isset($_GET['liquidar']) && $_GET['liquidar'] === 'true';

        try {
            $this->db->beginTransaction();

            $scopeStmt = $this->db->prepare('SELECT id_sucursal FROM pedidos WHERE id_pedido = ? FOR UPDATE');
            $scopeStmt->execute([$id]);
            $orderBranch = $scopeStmt->fetchColumn();
            if ($orderBranch === false) throw new InvalidArgumentException('El pedido no existe.');
            Auth::requireBranch((int) $orderBranch);

            $fechaRegistro = null;
            if ($nuevoEstado === 'Entregado') {
                date_default_timezone_set('America/Tegucigalpa');
                $fechaRegistro = date('Y-m-d H:i:s');
            }

            if ($liquidar) {
                $query = "UPDATE pedidos SET 
                            estado = :estado, 
                            monto_abonado = monto_abonado + saldo_pendiente, 
                            saldo_pendiente = 0,
                            estado_pago = 'Pagado'";
                if ($fechaRegistro) {
                    $query .= ", fecha_registro = :fecha_registro";
                }
                $query .= " WHERE id_pedido = :id";
            } else {
                $query = "UPDATE pedidos SET estado = :estado";
                if ($fechaRegistro) {
                    $query .= ", fecha_registro = :fecha_registro";
                }
                $query .= " WHERE id_pedido = :id";
            }

            $stmt = $this->db->prepare($query);
            $params = [
                ':estado' => $nuevoEstado,
                ':id'     => $id
            ];
            if ($fechaRegistro) {
                $params[':fecha_registro'] = $fechaRegistro;
            }
            $stmt->execute($params);

            $this->db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Estado actualizado correctamente']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            \App\Support\Logger::error($e);
            echo json_encode(['status' => 'error', 'message' => 'No fue posible actualizar el pedido.']);
        }
        exit();
    }


}

if (isset($_GET['action'])) {
    Auth::requireLogin();
    
    $controller = new PedidoController();
    $action = $_GET['action'];

    $postActions = ['crear', 'actualizar_estado_ajax', 'guardarMerma'];
    if (in_array($action, $postActions, true)) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            \App\Http\Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
        }
        Csrf::validateRequest();
    }
    if ($action === 'actualizar_estado') {
        \App\Http\Response::json(['status' => 'error', 'message' => 'Esta operacion requiere POST.'], 405);
    }

    if ($action === 'crear') {
        $controller->crear();
    } elseif ($action === 'actualizar_estado') {
        $controller->actualizarEstado();
    } elseif ($action === 'actualizar_estado_ajax') {
        $controller->actualizarEstadoAjax();
    } elseif ($action === 'reporte_pendientes') {
        $controller->generarReportePendientes();
    } elseif ($action === 'guardarMerma') { // NUEVA RUTA ATRAPADA
        $controller->guardarMerma();
    } elseif ($action === 'exportarPdf') {
        // En caso de que se llame directamente
        $controller->descargarReportePDF($_GET);
    }
}
