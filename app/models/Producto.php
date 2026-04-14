<?php
// app/models/Producto.php
class Producto {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Listar productos filtrados por sucursal
    public function obtenerPorSucursal($id_sucursal) {
        $query = "SELECT p.nombre_producto, p.id_producto, c.nombre_categoria, 
                         i.stock_actual, i.stock_minimo, p.precio_base
                  FROM inventario i
                  INNER JOIN productos p ON i.id_producto = p.id_producto
                  INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                  WHERE i.id_sucursal = :id_sucursal";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // app/models/Producto.php
    public function obtenerAlertasStock() {
        $query = "SELECT p.nombre_producto, i.stock_actual, i.stock_minimo, s.nombre_sucursal
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto
                INNER JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                WHERE i.stock_actual <= i.stock_minimo
                ORDER BY i.stock_actual ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}
?>