<?php
// app/controllers/ClienteController.php
use App\Security\Auth;
use App\Security\Csrf;

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
                $nombre = \App\Http\Validator::text($_POST['nombre'] ?? '', 'nombre', 150);
                $telefono = \App\Http\Validator::text($_POST['telefono'] ?? '', 'telefono', 30);
                if (!preg_match('/^[0-9+() -]{7,30}$/', $telefono)) throw new InvalidArgumentException('El telefono no es valido.');
                $email = trim((string) ($_POST['email'] ?? ''));
                if ($email !== '' && (mb_strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL))) throw new InvalidArgumentException('El correo no es valido.');

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
                    header("Location: index.php?view=pedidos-nuevo&status=client_ok", true, 303);
                    exit();
                }

            } catch (PDOException $e) {
                // En caso de error (ej: email duplicado si fuera UNIQUE)
                \App\Support\Logger::error($e);
                header("Location: index.php?view=pedidos-nuevo&status=error", true, 303);
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
    Auth::requireLogin(false);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        \App\Http\Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    }
    Csrf::validateRequest();
    $controller = new ClienteController();
    $controller->guardar();
}
