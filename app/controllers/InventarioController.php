<?php
// app/controllers/InventarioController.php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Producto.php';

class InventarioController {
    private $db;
    private $producto;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->producto = new Producto($db);
    }

    public function listar() {
        // Si es Admin y cambió la sucursal por GET, usamos esa. 
        // Si no, usamos la sucursal asignada en su sesión.
        $id_sucursal = $_SESSION['id_sucursal'];
        
        if ($_SESSION['role'] == 'Admin' && isset($_GET['sucursal_id'])) {
            $id_sucursal = $_GET['sucursal_id'];
        }

        return $this->producto->obtenerPorSucursal($id_sucursal);
    }
}