<?php
<<<<<<< HEAD
use App\Security\Auth;
use App\Security\Csrf;
use App\Http\Validator;

=======
// app/controllers/UsuarioController.php
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
require_once __DIR__ . '/../core/Database.php';

class UsuarioController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

<<<<<<< HEAD
    public function listar() {
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.estado_usuario, u.id_rol,
                         r.nombre_rol, s.nombre_sucursal,
                         (SELECT GROUP_CONCAT(s2.nombre_sucursal SEPARATOR ', ') 
                          FROM usuario_sucursales us 
                          JOIN sucursales s2 ON us.id_sucursal = s2.id_sucursal 
                          WHERE us.id_usuario = u.id_usuario) as sucursales_admin
                  FROM usuarios u
                  INNER JOIN roles r ON u.id_rol = r.id_rol
                  LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
=======
    // Para mostrar la lista en la vista de Admin
    public function listar() {
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.rol, u.estado_usuario, s.nombre_sucursal 
                  FROM usuarios u 
                  LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal 
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
                  ORDER BY u.id_usuario DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

<<<<<<< HEAD
=======
    // Para llenar el select de sucursales en el modal de nuevo usuario
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
    public function obtenerSucursales() {
        $query = "SELECT id_sucursal, nombre_sucursal FROM sucursales";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarAjax() {
<<<<<<< HEAD
        header('Content-Type: application/json');
        try {
            $this->db->beginTransaction();

            $nombre = Validator::text($_POST['nombre_usuario'] ?? '', 'usuario', 80);
            if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $nombre)) throw new InvalidArgumentException('El usuario contiene caracteres no permitidos.');
            $password = Validator::text($_POST['password'] ?? '', 'contrasena', 4096);
            if (mb_strlen($password) < 10) throw new InvalidArgumentException('La contrasena debe tener al menos 10 caracteres.');
            $pass = password_hash($password, PASSWORD_DEFAULT);
            $id_rol = Validator::positiveInt($_POST['id_rol'] ?? null, 'rol');
            if (!in_array($id_rol, [1, 2, 3], true)) throw new InvalidArgumentException('El rol no es valido.');
            if ((int) $_SESSION['id_rol'] !== Auth::SUPERUSER && $id_rol === Auth::SUPERUSER) throw new InvalidArgumentException('No puedes asignar ese rol.');
            $sucs = array_values(array_unique(array_map('intval', (array) ($_POST['id_sucursal'] ?? []))));
            foreach ($sucs as $branchId) Auth::requireBranch($branchId);
            $nombre_real = Validator::text($_POST['nombre_real'] ?? '', 'nombre real', 150);

            // Sucursal directa solo si es Empleado (Rol 2)
            $suc_directa = ($id_rol == 2 && !empty($sucs)) ? (is_array($sucs) ? $sucs[0] : $sucs) : null;

            $sql = "INSERT INTO usuarios (nombre_usuario, password_hash, id_rol, id_sucursal, nombre_real, estado_usuario) 
                    VALUES (:nom, :pass, :rol, :suc, :nom_real, 'Activo')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom'  => $nombre,
                ':pass' => $pass,
                ':nom_real' => $nombre_real,
                ':rol'  => $id_rol,
                ':suc'  => $suc_directa
            ]);

            $id_nuevo = $this->db->lastInsertId();

            // Si es Admin (Rol 1), guardamos sus múltiples sucursales
            if ($id_rol == 1 && is_array($sucs)) {
                $sqlInt = "INSERT INTO usuario_sucursales (id_usuario, id_sucursal) VALUES (:u, :s)";
                $stmtInt = $this->db->prepare($sqlInt);
                foreach ($sucs as $s_id) {
                    $stmtInt->execute([':u' => $id_nuevo, ':s' => $s_id]);
                }
            }

            $this->db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Usuario registrado con éxito']);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            \App\Support\Logger::error($e);
            echo json_encode(['status' => 'error', 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible registrar el usuario.']);
        }
        exit;
    }

    public function obtenerAjax() {
        header('Content-Type: application/json');
        $id = Validator::positiveInt($_GET['id'] ?? null, 'usuario');
        try {
            // Datos generales
            $stmt = $this->db->prepare("SELECT id_usuario, nombre_usuario, nombre_real, id_rol, id_sucursal, estado_usuario FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) \App\Http\Response::json(['status' => 'error', 'message' => 'Usuario no encontrado.'], 404);

            // Obtener array de sucursales (ya sea la principal o las múltiples de admin)
            $sucursales = [];
            if ($usuario['id_rol'] == 1) {
                $stmtSuc = $this->db->prepare("SELECT id_sucursal FROM usuario_sucursales WHERE id_usuario = ?");
                $stmtSuc->execute([$id]);
                $sucursales = $stmtSuc->fetchAll(PDO::FETCH_COLUMN);
            } else if ($usuario['id_sucursal']) {
                $sucursales[] = $usuario['id_sucursal'];
            }

            echo json_encode(['status' => 'success', 'usuario' => $usuario, 'sucursales' => $sucursales]);
        } catch (Exception $e) {
            \App\Support\Logger::error($e);
            echo json_encode(['status' => 'error', 'message' => 'No fue posible consultar el usuario.']);
        }
        exit;
    }

    public function editarAjax() {
        header('Content-Type: application/json');
        try {
            $this->db->beginTransaction();

            $id = Validator::positiveInt($_POST['id_usuario'] ?? null, 'usuario');
            $nombre = Validator::text($_POST['nombre_usuario'] ?? '', 'usuario', 80);
            if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $nombre)) throw new InvalidArgumentException('El usuario contiene caracteres no permitidos.');
            $nombre_real = Validator::text($_POST['nombre_real'] ?? '', 'nombre real', 150);
            $estado = Validator::enum($_POST['estado_usuario'] ?? '', ['Activo', 'Inactivo'], 'estado');
            $id_rol = Validator::positiveInt($_POST['id_rol'] ?? null, 'rol');
            if (!in_array($id_rol, [1, 2, 3], true)) throw new InvalidArgumentException('El rol no es valido.');
            if ((int) $_SESSION['id_rol'] !== Auth::SUPERUSER && $id_rol === Auth::SUPERUSER) throw new InvalidArgumentException('No puedes asignar ese rol.');
            $sucs = array_values(array_unique(array_map('intval', (array) ($_POST['id_sucursal'] ?? []))));
            foreach ($sucs as $branchId) Auth::requireBranch($branchId);

            $suc_directa = ($id_rol == 2 && !empty($sucs)) ? (is_array($sucs) ? $sucs[0] : $sucs) : null;

            $sql = "UPDATE usuarios SET nombre_usuario = :nom, nombre_real = :nom_real, id_rol = :rol, id_sucursal = :suc, estado_usuario = :est WHERE id_usuario = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom' => $nombre,
                ':nom_real' => $nombre_real,
                ':rol' => $id_rol,
                ':suc' => $suc_directa,
                ':est' => $estado,
                ':id' => $id
            ]);

            // Limpiar sucursales múltiples anteriores
            $stmtDel = $this->db->prepare("DELETE FROM usuario_sucursales WHERE id_usuario = ?");
            $stmtDel->execute([$id]);

            // Re-insertar si es Admin
            if ($id_rol == 1 && is_array($sucs)) {
                $sqlInt = "INSERT INTO usuario_sucursales (id_usuario, id_sucursal) VALUES (:u, :s)";
                $stmtInt = $this->db->prepare($sqlInt);
                foreach ($sucs as $s_id) {
                    $stmtInt->execute([':u' => $id, ':s' => $s_id]);
                }
            }

            $this->db->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            \App\Support\Logger::error($e);
            echo json_encode(['status' => 'error', 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible editar el usuario.']);
        }
        exit;
    }

    public function cambiarPasswordAjax() {
        header('Content-Type: application/json');
        try {
            $id = Validator::positiveInt($_POST['id_usuario'] ?? null, 'usuario');
            $password = Validator::text($_POST['nueva_password'] ?? '', 'contrasena', 4096);
            if (mb_strlen($password) < 10) throw new InvalidArgumentException('La contrasena debe tener al menos 10 caracteres.');
            $nueva_pass = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?");
            $stmt->execute([$nueva_pass, $id]);

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al cambiar contraseña']);
        }
        exit;
    }


}

if (isset($_GET['action'])) {
    Auth::requireRoles([Auth::ADMIN, Auth::SUPERUSER]);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Csrf::validateRequest();
    } elseif ($_GET['action'] !== 'obtenerAjax') {
        \App\Http\Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    }
    $ctrl = new UsuarioController();
    switch ($_GET['action']) {
        case 'registrarAjax':
            $ctrl->registrarAjax();
            break;
        case 'obtenerAjax':
            $ctrl->obtenerAjax();
            break;
        case 'editarAjax':
            $ctrl->editarAjax();
            break;
        case 'cambiarPasswordAjax':
            $ctrl->cambiarPasswordAjax();
            break;
    }
}
=======
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
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
