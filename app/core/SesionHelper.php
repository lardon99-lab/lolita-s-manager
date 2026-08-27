<?php

use App\Support\Env;

require_once __DIR__ . '/../bootstrap.php';

class SesionHelper {
    
    public static function protegerVista() {
        // Forzamos al navegador a no usar caché
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        if (!isset($_SESSION['id_usuario'])) {
            // Si estamos en public/index.php, el login está en esta ruta:
            header("Location: login.php");
            exit();
        }

        // Control de Inactividad (1.5 horas)
        $tiempo_maximo = max(300, (int) Env::get('SESSION_IDLE_TIMEOUT', '5400'));
        if (isset($_SESSION['ultimo_acceso'])) {
            $vida_sesion = time() - $_SESSION['ultimo_acceso'];
            if ($vida_sesion > $tiempo_maximo) {
                self::cerrarSesion();
            }
        }
        $_SESSION['ultimo_acceso'] = time();
    }

    public static function redireccionarSiLogueado() {
        if (isset($_SESSION['id_usuario'])) {
            // Si el usuario está en views/auth/login.php, el index está aquí:
            header("Location: index.php?view=dashboard");
            exit();
        }
    }

    public static function cerrarSesion() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión en el navegador
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        // Redirección limpia
        header("Location: login.php");
        exit();
    }
}
