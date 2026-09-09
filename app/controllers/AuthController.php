<?php
declare(strict_types=1);

use App\Http\Response;
use App\Security\Csrf;
use App\Security\LoginRateLimiter;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    private Usuario $userModel;

    public function __construct()
    {
        $this->userModel = new Usuario((new Database())->getConnection());
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
        Csrf::validateRequest();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '' || strlen($username) > 100 || strlen($password) > 4096) {
            Response::redirect('login.php?error=1');
        }
        if (LoginRateLimiter::tooManyAttempts($username)) {
            Response::redirect('login.php?error=blocked');
        }
        $userData = $this->userModel->login($username, $password);
        if (!$userData) {
            LoginRateLimiter::recordFailure($username);
            usleep(250000);
            Response::redirect('login.php?error=1');
        }
        LoginRateLimiter::clear($username);
        session_regenerate_id(true);
        $_SESSION = [
            'id_usuario' => (int) $userData['id_usuario'],
            'username' => (string) $userData['nombre_usuario'],
            'id_rol' => (int) $userData['id_rol'],
            'ultimo_acceso' => time(),
        ];
        if ((int) $userData['id_rol'] === 3) {
            $_SESSION['scope'] = 'all';
        } elseif ((int) $userData['id_rol'] === 1) {
            $_SESSION['scope'] = 'restricted';
            $_SESSION['sucursales'] = array_map('intval', $userData['sucursales_asignadas'] ?? []);
        } else {
            $_SESSION['scope'] = 'single';
            $_SESSION['id_sucursal'] = (int) $userData['id_sucursal'];
        }
        Csrf::token();
        Response::redirect('index.php?view=dashboard');
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
        Csrf::validateRequest();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        Response::redirect('login.php');
    }
}

$controller = new AuthController();
$action = $_GET['action'] ?? 'login';
if ($action === 'logout') $controller->logout();
if ($action === 'login') $controller->login();
Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404);
