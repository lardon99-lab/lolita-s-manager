<?php
// app/controllers/AuthController.php
session_start();
require_once '../core/Database.php';
require_once '../models/Usuario.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new Usuario($this->db);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $userData = $this->user->login($username, $password);

            if ($userData) {
                $_SESSION['id_usuario']   = $userData['id_usuario']; 
                $_SESSION['username']     = $userData['nombre_usuario'];
                $_SESSION['role']         = $userData['rol']; 
                $_SESSION['id_sucursal']  = $userData['id_sucursal'];

                header("Location: ../../public/index.php?view=dashboard");
                exit();
            }else {
                header("Location: ../../views/auth/login.php?error=1");
                exit();
            }
        }
    }

    public function logout() {
        session_destroy();
        header("Location: ../../views/auth/login.php");
        exit();
    }
}

// Lógica de enrutamiento simple para el controlador
$auth = new AuthController();
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    $auth->logout();
} else {
    $auth->login();
}