<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../core/Database.php';

class VentaController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function procesarVenta() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        // Ahora sí coincidirán los nombres
        $id_usuario = $_SESSION['id_usuario'] ?? null;
        $id_sucursal = $_SESSION['id_sucursal'] ?? null;
        
        if (!$id_usuario) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no válida. Por favor re-inicia sesión.']);
            exit;
        }

        $id_producto = $_POST['id_producto'];
        $cantidad = (int)$_POST['cantidad'];
        $id_usuario = $_SESSION['id_usuario'];
        $id_sucursal = $_SESSION['id_sucursal'];

        try {
            // 0. Asegurar que los datos de sesión existan antes de empezar
            $id_usuario = $_SESSION['id_usuario'] ?? null;
            $id_sucursal = $_SESSION['id_sucursal'] ?? null;
            $rol_usuario = $_SESSION['role'] ?? 'Empleado';

            if (!$id_usuario) {
                throw new Exception("Error: El ID de usuario no está definido en la sesión.");
            }

            $this->db->beginTransaction();

            // 1. Construir la consulta dinámicamente
            // Si es Admin, no filtramos por ID de sucursal para que pueda vender de cualquier stock disponible
            $sql = "SELECT p.precio_base, i.stock_actual, i.id_inventario, i.id_sucursal as sucursal_real 
                    FROM productos p
                    INNER JOIN inventario i ON p.id_producto = i.id_producto
                    WHERE p.id_producto = :idp";

            if ($rol_usuario !== 'Admin') {
                $sql .= " AND i.id_sucursal = :ids";
            }
            
            $sql .= " AND i.stock_actual >= :cant LIMIT 1";

            $stmt = $this->db->prepare($sql);

            $params = [
                ':idp'  => $id_producto,
                ':cant' => $cantidad
            ];

            if ($rol_usuario !== 'Admin') {
                $params[':ids'] = $id_sucursal;
            }

            $stmt->execute($params);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$res) {
                $msg = ($rol_usuario === 'Admin') 
                    ? "No hay stock suficiente de este producto en ninguna sucursal." 
                    : "No hay suficiente stock en tu sucursal asignada.";
                throw new Exception($msg);
            }

            if (!$res) {
                throw new Exception("El producto no existe en el inventario.");
            }

            if ($res['stock_actual'] < $cantidad) {
                // Aquí lanzamos el error que detiene la transacción
                throw new Exception("Error crítico: Solo quedan " . $res['stock_actual'] . " unidades disponibles.");
            }


            $total_venta = $res['precio_base'] * $cantidad;
            // Si es admin, usaremos la sucursal donde se encontró el stock para el registro
            $sucursal_para_registro = ($rol_usuario === 'Admin') ? $res['sucursal_real'] : $id_sucursal;

            // 2. Restar de la tabla INVENTARIO usando el ID específico encontrado
            $update = $this->db->prepare("UPDATE inventario SET stock_actual = stock_actual - :cant WHERE id_inventario = :idinv");
            $update->execute([
                ':cant'  => $cantidad, 
                ':idinv' => $res['id_inventario']
            ]);

            // 3. Registrar la venta
            $insert = $this->db->prepare("INSERT INTO ventas (id_producto, cantidad, total, fecha_venta, id_usuario, id_sucursal) 
                                        VALUES (:id_p, :cant, :total, NOW(), :id_u, :id_s)");
            $insert->execute([
                ':id_p'  => $id_producto,
                ':cant'  => $cantidad,
                ':total' => $total_venta,
                ':id_u'  => $id_usuario,
                ':id_s'  => $sucursal_para_registro
            ]);

            $this->db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Venta registrada con éxito']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Fuera de la clase VentaController
if (isset($_GET['action'])) {
    if (!isset($_SESSION)) { session_start(); }
    $controller = new VentaController();
    
    if ($_GET['action'] == 'procesarVenta') {
        $controller->procesarVenta();
    }
}