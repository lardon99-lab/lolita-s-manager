<?php
// app/models/Producto.php
class Producto {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Listar productos filtrados por sucursal (AGRUPADO)
    public function obtenerPorSucursal($id_sucursal) {
        $query = $this->inventoryQuery('WHERE ps.id_sucursal = :id_sucursal');

        $stmt = $this->conn->prepare($query); 
        $stmt->bindValue(':id_sucursal', (int)$id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Alertas de stock (AGRUPADO CON HAVING)
    public function obtenerAlertasStock() {
        $query = "SELECT 
                    p.id_producto, i.id_sucursal, p.nombre_producto, p.dias_vida_util, p.estado,
                    SUM(CASE WHEN i.fecha_caducidad IS NULL OR i.fecha_caducidad >= CURRENT_DATE THEN i.stock_actual ELSE 0 END) as stock_actual,
                    MAX(i.stock_minimo) as stock_minimo,
                    s.nombre_sucursal
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto
                INNER JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                GROUP BY p.id_producto, i.id_sucursal, p.nombre_producto, p.dias_vida_util, p.estado, s.nombre_sucursal
                HAVING SUM(CASE WHEN i.fecha_caducidad IS NULL OR i.fecha_caducidad >= CURRENT_DATE THEN i.stock_actual ELSE 0 END) <= MAX(i.stock_minimo)
                ORDER BY stock_actual ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Listar todo el inventario (AGRUPADO)
    public function obtenerTodoElInventario() {
        $query = $this->inventoryQuery();

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorSucursales(array $ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = $this->inventoryQuery("WHERE ps.id_sucursal IN ($placeholders)");
        $stmt = $this->conn->prepare($query);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function inventoryQuery(string $where = ''): string {
        $scope = $where === '' ? "WHERE ps.estado = 'Activo'" : "$where AND ps.estado = 'Activo'";
        return "SELECT
                    p.id_producto, ps.id_sucursal, p.nombre_producto, p.precio_base,
                    p.dias_vida_util, p.estado, p.tipo_producto, p.control_inventario,
                    c.nombre_categoria, s.nombre_sucursal,
                    CASE p.control_inventario
                        WHEN 'producto' THEN COALESCE((
                            SELECT SUM(CASE
                                WHEN i.fecha_caducidad IS NULL OR i.fecha_caducidad >= CURRENT_DATE
                                THEN i.stock_actual ELSE 0 END)
                            FROM inventario i
                            WHERE i.id_producto = p.id_producto AND i.id_sucursal = ps.id_sucursal
                        ), 0)
                        WHEN 'insumos' THEN COALESCE((
                            SELECT MIN(FLOOR(COALESCE(ii.stock_actual, 0) / pi.cantidad))
                            FROM producto_insumos pi
                            LEFT JOIN inventario_insumos ii ON ii.id_insumo = pi.id_insumo
                                AND ii.id_sucursal = ps.id_sucursal
                            WHERE pi.id_producto = p.id_producto
                        ), 0)
                        ELSE 0
                    END AS stock_actual,
                    CASE p.control_inventario
                        WHEN 'producto' THEN COALESCE((
                            SELECT SUM(CASE WHEN i.fecha_caducidad < CURRENT_DATE THEN i.stock_actual ELSE 0 END)
                            FROM inventario i
                            WHERE i.id_producto = p.id_producto AND i.id_sucursal = ps.id_sucursal
                        ), 0)
                        ELSE 0
                    END AS stock_vencido,
                    CASE p.control_inventario
                        WHEN 'producto' THEN COALESCE((
                            SELECT MAX(i.stock_minimo) FROM inventario i
                            WHERE i.id_producto = p.id_producto AND i.id_sucursal = ps.id_sucursal
                        ), 0)
                        WHEN 'insumos' THEN COALESCE((
                            SELECT MIN(FLOOR(COALESCE(ii.stock_minimo, 0) / pi.cantidad))
                            FROM producto_insumos pi
                            LEFT JOIN inventario_insumos ii ON ii.id_insumo = pi.id_insumo
                                AND ii.id_sucursal = ps.id_sucursal
                            WHERE pi.id_producto = p.id_producto
                        ), 0)
                        ELSE 0
                    END AS stock_minimo
                FROM producto_sucursales ps
                INNER JOIN productos p ON p.id_producto = ps.id_producto
                INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                INNER JOIN sucursales s ON s.id_sucursal = ps.id_sucursal
                $scope
                ORDER BY s.nombre_sucursal, p.nombre_producto ASC";
    }
}
?>
