<?php
// app/controllers/ClienteController.php
require_once __DIR__ . '/../core/Database.php';

class ClienteController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Guarda un nuevo cliente en la base de datos
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Limpieza básica de datos para seguridad
                $nombre = strip_tags(trim($_POST['nombre']));
                $telefono = strip_tags(trim($_POST['telefono']));
                $email = strip_tags(trim($_POST['email']));

                $query = "INSERT INTO clientes (nombre_completo, telefono, email) 
                          VALUES (:nombre, :tel, :email)";
                
                $stmt = $this->db->prepare($query);
                
                $resultado = $stmt->execute([
                    ':nombre' => $nombre,
                    ':tel'    => $telefono,
                    ':email'  => $email
                ]);

                if ($resultado) {
                    // Redireccionamos de vuelta al formulario de pedidos con un mensaje de éxito
                    header("Location: ../../public/index.php?view=pedidos-nuevo&status=client_ok");
                    exit();
                }

            } catch (PDOException $e) {
                // En caso de error (ej: email duplicado si fuera UNIQUE)
                header("Location: ../../public/index.php?view=pedidos-nuevo&status=error&msg=" . urlencode($e->getMessage()));
                exit();
            }
        }
    }

    /**
     * Opcional: Método para obtener todos los clientes (útil para el selector)
     */
    public function listarTodos() {
        $query = "SELECT * FROM clientes ORDER BY nombre_completo ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Lógica de enrutamiento del controlador
if (isset($_GET['action']) && $_GET['action'] == 'guardar') {
    $controller = new ClienteController();
    $controller->guardar();
}