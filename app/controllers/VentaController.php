<?php
use App\Security\Auth;
use App\Security\Csrf;

require_once __DIR__ . '/../core/Database.php';

class VentaController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function procesarVenta() {
        header('Content-Type: application/json');

        // 1. Validar Sesión
        $id_usuario = $_SESSION['id_usuario'] ?? null;
        if (!$id_usuario) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no válida. Por favor re-inicia sesión.']);
            exit;
        }

        // 2. Leer el JSON enviado por Fetch API
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id_sucursal_target = $input['id_sucursal'] ?? ($_SESSION['id_sucursal'] ?? null);
        $productos_carrito = $input['productos'] ?? [];

        if (empty($productos_carrito) || !$id_sucursal_target) {
            echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío o la sucursal es inválida.']);
            exit;
        }

        $id_sucursal_target = (int) $id_sucursal_target;
        Auth::requireBranch($id_sucursal_target);

        try {
            $this->db->beginTransaction();

            $gran_total = 0;
            $items_a_insertar = [];

            // Preparamos la consulta de stock (FOR UPDATE bloquea la fila temporalmente para evitar ventas dobles simultáneas)
            $stmtStock = $this->db->prepare("SELECT p.precio_base, i.stock_actual, i.id_inventario 
                                             FROM productos p
                                             INNER JOIN inventario i ON p.id_producto = i.id_producto
                                             WHERE p.id_producto = :idp AND i.id_sucursal = :ids FOR UPDATE");

            // 3. Validar stock de TODOS los productos antes de insertar nada
            foreach ($productos_carrito as $prod) {
                $id_prod = (int)$prod['id'];
                $cant = (int)$prod['cantidad'];

                if ($cant <= 0) {
                    throw new Exception("Cantidad inválida para el producto ID {$id_prod}.");
                }

                $stmtStock->execute([':idp' => $id_prod, ':ids' => $id_sucursal_target]);
                $res = $stmtStock->fetch(PDO::FETCH_ASSOC);

                if (!$res) {
                    throw new Exception("Producto no encontrado en esta sucursal (ID: {$id_prod}).");
                }
                if ($res['stock_actual'] < $cant) {
                    throw new Exception("Stock insuficiente para el producto (ID: {$id_prod}). Stock actual: {$res['stock_actual']}");
                }

                // Recalculamos el subtotal en el servidor por seguridad
                $precio_real = $res['precio_base'];
                $subtotal = $precio_real * $cant;
                $gran_total += $subtotal;

                // Guardamos los datos validados para la inserción
                $items_a_insertar[] = [
                    'id_producto' => $id_prod,
                    'cantidad' => $cant,
                    'precio_unitario' => $precio_real,
                    'subtotal' => $subtotal,
                    'id_inventario' => $res['id_inventario']
                ];
            }

            // 4. Registrar Cabecera en ventas_directas
            $insVenta = $this->db->prepare("INSERT INTO ventas_directas (id_usuario, id_sucursal, total) VALUES (:usr, :suc, :tot)");
            $insVenta->execute([
                ':usr' => $id_usuario,
                ':suc' => $id_sucursal_target,
                ':tot' => $gran_total
            ]);
            $id_venta_generada = $this->db->lastInsertId();

            // 5. Registrar Detalles y Actualizar Inventario
            $insItem = $this->db->prepare("INSERT INTO venta_items (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (:idv, :idp, :cant, :pre, :sub)");
            $updStock = $this->db->prepare("UPDATE inventario SET stock_actual = stock_actual - :cant WHERE id_inventario = :idinv");

            foreach ($items_a_insertar as $item) {
                // Insertar en detalle
                $insItem->execute([
                    ':idv' => $id_venta_generada,
                    ':idp' => $item['id_producto'],
                    ':cant' => $item['cantidad'],
                    ':pre' => $item['precio_unitario'],
                    ':sub' => $item['subtotal']
                ]);

                // Descontar inventario
                $updStock->execute([
                    ':cant' => $item['cantidad'],
                    ':idinv' => $item['id_inventario']
                ]);
            }

            $this->db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Venta registrada con éxito']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) { 
                $this->db->rollBack(); 
            }
            \App\Support\Logger::error($e);
            echo json_encode(['status' => 'error', 'message' => 'No fue posible registrar la venta.']);
        }
        exit;
    }
}

// Router
if (isset($_GET['action']) && $_GET['action'] == 'procesarVenta') {
    Auth::requireLogin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        \App\Http\Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    }
    Csrf::validateRequest();
    $controller = new VentaController();
    $controller->procesarVenta();
}
