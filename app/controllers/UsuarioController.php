<?php
// app/controllers/UsuarioController.php
require_once __DIR__ . '/../core/Database.php';

class UsuarioController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Para mostrar la lista en la vista de Admin
    public function listar() {
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.rol, u.estado_usuario, s.nombre_sucursal 
                  FROM usuarios u 
                  LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal 
                  ORDER BY u.id_usuario DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Para llenar el select de sucursales en el modal de nuevo usuario
    public function obtenerSucursales() {
        $query = "SELECT id_sucursal, nombre_sucursal FROM sucursales";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarAjax() {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $user = trim($_POST['nombre_usuario']);
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $rol  = $_POST['rol'];
        

        $sucursal = ($rol === 'Admin' || empty($_POST['id_sucursal'])) ? null : $_POST['id_sucursal'];

        try {
            $sql = "INSERT INTO usuarios (id_rol, nombre_usuario, password_hash, rol, id_sucursal, estado_usuario) 
                    VALUES (:r, :u, :p, :r, :s, 'Activo')";
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':u', $user);
            $stmt->bindValue(':p', $pass);
            $stmt->bindValue(':r', $rol);
            $stmt->bindValue(':s', $sucursal, $sucursal === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Usuario creado exitosamente']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
}

// Escuchador de acciones para el formulario
if (isset($_GET['action']) && $_GET['action'] == 'registrar') {
    $controller = new UsuarioController();
    $controller->registrar();
}

if (isset($_GET['action'])) {
    $controller = new UsuarioController();
    
    if ($_GET['action'] == 'registrarAjax') {
        $controller->registrarAjax();
    }
}