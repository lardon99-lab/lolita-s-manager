<?php
// app/controllers/PedidoController.php
require_once __DIR__ . '/../core/Database.php';

class PedidoController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Carga clientes y productos para el formulario
    public function prepararFormulario() {
        $clientes = $this->db->query("SELECT id_cliente, nombre_completo FROM clientes")->fetchAll(PDO::FETCH_ASSOC);
        $productos = $this->db->query("SELECT id_producto, nombre_producto, precio_base FROM productos")->fetchAll(PDO::FETCH_ASSOC);
        
        return ['clientes' => $clientes, 'productos' => $productos];
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // INICIAMOS TRANSACCIÓN
                $this->db->beginTransaction();

                // 1. Insertar Cabecera del Pedido
                $queryPedido = "INSERT INTO pedidos (id_cliente, id_sucursal, id_usuario, fecha_entrega, total_pedido, observaciones_generales) 
                                VALUES (:id_c, :id_s, :id_u, :fecha, :total, :obs)";
                
                $stmt = $this->db->prepare($queryPedido);
                $stmt->execute([
                    ':id_c'   => $_POST['id_cliente'],
                    ':id_s'   => $_POST['id_sucursal'],
                    ':id_u'   => $_SESSION['user_id'],
                    ':fecha'  => $_POST['fecha_entrega'],
                    ':total'  => $_POST['total_final'],
                    ':obs'    => $_POST['observaciones']
                ]);

                $id_pedido = $this->db->lastInsertId();

                // 2. Insertar Detalles (Recorremos los arrays enviados desde el JS)
                $queryDetalle = "INSERT INTO pedido_detalles (id_pedido, id_producto, cantidad, precio_unitario, detalles_personalizacion, subtotal) 
                                 VALUES (?, ?, ?, ?, ?, ?)";
                $stmtDetalle = $this->db->prepare($queryDetalle);

                foreach ($_POST['productos'] as $index => $id_prod) {
                    // Nota: En un sistema real, el precio debería venir de la DB, no del POST por seguridad
                    // Aquí simplificamos para el ejemplo
                    $cant = $_POST['cantidades'][$index];
                    $perso = $_POST['personalizacion'][$index];
                    
                    // Necesitamos el precio del producto para el subtotal
                    $stmtP = $this->db->prepare("SELECT precio_base FROM productos WHERE id_producto = ?");
                    $stmtP->execute([$id_prod]);
                    $precio = $stmtP->fetchColumn();
                    $subtotal = $precio * $cant;

                    $stmtDetalle->execute([
                        $id_pedido, 
                        $id_prod, 
                        $cant, 
                        $precio, 
                        $perso, 
                        $subtotal
                    ]);
                }

                // SI TODO BIEN, CONFIRMAMOS
                $this->db->commit();
                header("Location: ../../public/index.php?view=dashboard&msg=pedido_ok");

            } catch (Exception $e) {
                // SI ALGO FALLA, DESHACEMOS TODO
                $this->db->rollBack();
                die("Error al guardar el pedido: " . $e->getMessage());
            }
        }
    }
}

// Enrutador del controlador
if (isset($_GET['action']) && $_GET['action'] == 'crear') {
    session_start();
    $controller = new PedidoController();
    $controller->crear();
}