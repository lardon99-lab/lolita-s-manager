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
    // Agregamos la consulta de sucursales
    $sucursales = $this->db->query("SELECT id_sucursal, nombre_sucursal FROM sucursales")->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'clientes' => $clientes, 
        'productos' => $productos,
        'sucursales' => $sucursales
    ];
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // INICIAMOS TRANSACCIÓN
                $this->db->beginTransaction();

                // 1. Insertar Cabecera del Pedido
                $queryPedido = "INSERT INTO pedidos (id_cliente, id_sucursal, id_usuario, fecha_entrega, total_pedido, monto_abonado, saldo_pendiente, estado_pago, observaciones_generales) 
                                VALUES (:id_c, :id_s, :id_u, :fecha, :total, :abono, :saldo, :est_pago, :obs)";
                
                $stmt = $this->db->prepare($queryPedido);
                // Si el formulario no envió sucursal (por ser admin o error), usamos la de la sesión
                $id_sucursal = $_POST['id_sucursal'] ?: $_SESSION['id_sucursal'];

                $stmt->execute([
                    ':id_c'      => $_POST['id_cliente'],
                    ':id_s'      => $_POST['id_sucursal'],
                    ':id_u'      => $_SESSION['user_id'],
                    ':fecha'     => $_POST['fecha_entrega'],
                    ':total'     => $total_final,
                    ':abono'     => $monto_abonado,
                    ':saldo'     => $saldo_pendiente,
                    ':est_pago'  => $tipo_pago,
                    ':obs'       => $_POST['observaciones']
                ]);
                $id_pedido = $this->db->lastInsertId();

                $total_final = $_POST['total_final'];
                $tipo_pago = $_POST['tipo_pago'];
                $monto_abonado = 0;

                if ($tipo_pago == 'Pagado') {
                    $monto_abonado = $total_final;
                } elseif ($tipo_pago == 'Abonado') {
                    $monto_abonado = $_POST['monto_abono'];
                }

                $saldo_pendiente = $total_final - $monto_abonado;

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
            $query = "SELECT p.*, c.nombre_completo as nombre_cliente, c.telefono 
                    FROM pedidos p
                    INNER JOIN clientes c ON p.id_cliente = c.id_cliente";

            if ($estado !== 'Todos' && !empty($estado)) {
                $query .= " WHERE p.estado = :estado";
            }

            $query .= " ORDER BY p.fecha_entrega ASC";

            $stmt = $this->db->prepare($query);

            if ($estado !== 'Todos' && !empty($estado)) {
                $stmt->bindValue(':estado', $estado);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Esto te dirá si el error está en la base de datos
            die("Error en la consulta: " . $e->getMessage());
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

                header("Location: ../../public/index.php?view=pedidos-lista&msg=status_updated");
                exit();
            } catch (Exception $e) {
                die("Error al actualizar: " . $e->getMessage());
            }
        }
    }
    public function obtenerMetricasDashboard() {
        // Pedidos pendientes totales
        $pendientes = $this->db->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetchColumn();
        
        // Pedidos para hoy
        $hoy = date('Y-m-d');
        $paraHoy = $this->db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_entrega) = '$hoy' AND estado != 'Entregado'")->fetchColumn();
        
        // Total ventas (opcional)
        $ventas = $this->db->query("SELECT SUM(total_pedido) FROM pedidos WHERE estado = 'Entregado'")->fetchColumn();

        return [
            'pendientes' => $pendientes,
            'para_hoy'   => $paraHoy,
            'ventas'     => $ventas ?? 0
        ];
    }
    // app/controllers/PedidoController.php

    public function obtenerHistorialVentas($fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT p.*, c.nombre_completo as cliente, s.nombre_sucursal 
                FROM pedidos p
                INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                INNER JOIN sucursales s ON p.id_sucursal = s.id_sucursal
                WHERE p.estado = 'Entregado'";

        // Filtro por fecha si el usuario lo solicita
        if ($fechaInicio && $fechaFin) {
            $sql .= " AND DATE(p.fecha_registro) BETWEEN :inicio AND :fin";
        }

        $sql .= " ORDER BY p.fecha_registro DESC";
        
        $stmt = $this->db->prepare($sql);
        
        if ($fechaInicio && $fechaFin) {
            $stmt->bindParam(':inicio', $fechaInicio);
            $stmt->bindParam(':fin', $fechaFin);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Enrutador del controlador
if (isset($_GET['action']) && $_GET['action'] == 'crear') {
    session_start();
    $controller = new PedidoController();
    $controller->crear();
}
if (isset($_GET['action']) && $_GET['action'] == 'actualizar_estado') {
    $controller = new PedidoController();
    $controller->actualizarEstado();
}