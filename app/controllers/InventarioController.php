<?php
// app/controllers/InventarioController.php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Producto.php';

class InventarioController {
    private $db;
    private $producto;

    public function __construct() {
        $database = new Database();
        // ESTA LÍNEA ES LA CLAVE: Asignamos la conexión a la propiedad de la clase
        $this->db = $database->getConnection(); 
        $this->producto = new Producto($this->db);
    }

    public function listar() {
        // 1. Si se pasó un ID por la URL y no está vacío...
        if (isset($_GET['sucursal_id']) && $_GET['sucursal_id'] !== "") {
            return $this->producto->obtenerPorSucursal($_GET['sucursal_id']);
        } 
        
        // 2. Si el usuario es empleado, forzamos su sucursal (seguridad)
        if ($_SESSION['role'] !== 'Admin') {
            return $this->producto->obtenerPorSucursal($_SESSION['id_sucursal']);
        }

        // 3. Si es Admin y el filtro está vacío, mostramos TODO
        return $this->producto->obtenerTodoElInventario();
    }

    public function abastecer() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_inventario = $_POST['id_inventario'];
            $cantidad_nueva = $_POST['cantidad'];

            try {
                // Ahora $this->db ya no será NULL
                $query = "UPDATE inventario SET stock_actual = stock_actual + :cantidad WHERE id_inventario = :id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':cantidad' => $cantidad_nueva,
                    ':id'       => $id_inventario
                ]);

                header("Location: ../../public/index.php?view=inventario&status=abastecido");
                exit();
            } catch (Exception $e) {
                die("Error al abastecer: " . $e->getMessage());
            }
        }
    }

    public function listarProductosDisponibles() {
        // Obtenemos la sucursal del usuario de la sesión
        $id_sucursal = $_SESSION['id_sucursal'] ?? null;

        // Si no hay sucursal (y no es admin), no debería poder vender nada
        if (!$id_sucursal && $_SESSION['role'] !== 'Admin') {
            return [];
        }

        // La consulta une 'inventario' con 'productos' para obtener nombres y precios
        // Usamos 'stock_actual' que es el nombre que usas en el método abastecer
        $query = "SELECT p.id_producto, p.nombre_producto, p.precio_base, i.stock_actual as stock, i.id_inventario
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto";
        
        // Si no es Admin, solo mostramos lo de SU sucursal
        if ($_SESSION['role'] !== 'Admin') {
            $query .= " WHERE i.id_sucursal = :id_s AND i.stock_actual > 0";
        } else {
            $query .= " WHERE i.stock_actual > 0";
        }

        $query .= " ORDER BY p.nombre_producto ASC";

        try {
            $stmt = $this->db->prepare($query);
            if ($_SESSION['role'] !== 'Admin') {
                $stmt->bindValue(':id_s', $id_sucursal);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return []; // Retorna vacío si hay error para no romper la vista
        }
    }
}

// Enrutador del controlador
if (isset($_GET['action'])) {
    if (!isset($_SESSION)) { session_start(); }
    $controller = new InventarioController();
    
    if ($_GET['action'] == 'abastecer') {
        $controller->abastecer();
    }
}