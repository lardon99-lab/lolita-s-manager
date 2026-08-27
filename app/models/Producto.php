<?php
// app/models/Producto.php
class Producto {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Listar productos filtrados por sucursal (AGRUPADO)
    public function obtenerPorSucursal($id_sucursal) {
        $query = "SELECT 
                    p.id_producto, i.id_sucursal, p.nombre_producto, p.precio_base,
                    c.nombre_categoria, SUM(i.stock_actual) as stock_actual, MAX(i.stock_minimo) as stock_minimo,
                    s.nombre_sucursal
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto
                INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                INNER JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                WHERE i.id_sucursal = :id_sucursal
                GROUP BY p.id_producto, i.id_sucursal, p.nombre_producto, p.precio_base, c.nombre_categoria, s.nombre_sucursal";

        $stmt = $this->conn->prepare($query); 
        $stmt->bindValue(':id_sucursal', (int)$id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Alertas de stock (AGRUPADO CON HAVING)
    public function obtenerAlertasStock() {
        $query = "SELECT 
                    p.id_producto, i.id_sucursal, p.nombre_producto, 
                    SUM(i.stock_actual) as stock_actual, MAX(i.stock_minimo) as stock_minimo, 
                    s.nombre_sucursal
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto
                INNER JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                GROUP BY p.id_producto, i.id_sucursal, p.nombre_producto, s.nombre_sucursal
                HAVING SUM(i.stock_actual) <= MAX(i.stock_minimo)
                ORDER BY stock_actual ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Listar todo el inventario (AGRUPADO)
    public function obtenerTodoElInventario() {
        $query = "SELECT 
                    p.id_producto, i.id_sucursal, p.nombre_producto, c.nombre_categoria, 
                    SUM(i.stock_actual) as stock_actual, MAX(i.stock_minimo) as stock_minimo, p.precio_base, s.nombre_sucursal
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto
                INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                INNER JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                GROUP BY p.id_producto, i.id_sucursal, p.nombre_producto, c.nombre_categoria, p.precio_base, s.nombre_sucursal
                ORDER BY s.nombre_sucursal, p.nombre_producto ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorSucursales(array $ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT p.id_producto, i.id_sucursal, p.nombre_producto, c.nombre_categoria,
                         SUM(i.stock_actual) AS stock_actual, MAX(i.stock_minimo) AS stock_minimo,
                         p.precio_base, s.nombre_sucursal
                  FROM inventario i
                  INNER JOIN productos p ON i.id_producto = p.id_producto
                  INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                  INNER JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                  WHERE i.id_sucursal IN ($placeholders)
                  GROUP BY p.id_producto, i.id_sucursal, p.nombre_producto, c.nombre_categoria, p.precio_base, s.nombre_sucursal
                  ORDER BY s.nombre_sucursal, p.nombre_producto ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
