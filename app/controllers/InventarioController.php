<?php
// app/controllers/InventarioController.php
use App\Security\Auth;
use App\Security\Csrf;

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Producto.php';

class InventarioController {
    private $db;
    private $producto;

    public function __construct() {
        $database = new Database();
<<<<<<< HEAD
        $this->db = $database->getConnection(); 
        
        // Aquí le pasas la conexión al modelo
        $this->producto = new Producto($this->db);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function listar() {
        // Normalizamos los valores de sesión
        $rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
        $sucursal_user = isset($_SESSION['id_sucursal']) ? (int)$_SESSION['id_sucursal'] : null;

        // 1. Si es Empleado (Rol 2), forzamos su sucursal de sesión
        if ($rol === 2) {
            if (!$sucursal_user) return []; 
            return $this->producto->obtenerPorSucursal($sucursal_user);
        }

        // 2. Si es Admin (1) o SuperUser (3) y usa el filtro de la URL
        if (isset($_GET['sucursal_id']) && $_GET['sucursal_id'] !== "") {
            $branchId = (int) $_GET['sucursal_id'];
            if (!Auth::canAccessBranch($branchId)) return [];
            return $this->producto->obtenerPorSucursal($branchId);
        }

        // 3. Admin o SuperUser sin filtros: Ven todo el inventario global
        $allowed = Auth::allowedBranches();
        return $allowed === null ? $this->producto->obtenerTodoElInventario() : $this->producto->obtenerPorSucursales($allowed);
    }

    public function listarPorSucursal($id_sucursal) {
        if (!$id_sucursal) return [];
        return $this->producto->obtenerPorSucursal($id_sucursal);
=======
        // ESTA LÍNEA ES LA CLAVE: Asignamos la conexión a la propiedad de la clase
        $this->db = $database->getConnection(); 
        $this->producto = new Producto($this->db);
    }

    public function listar() {
        // 1. Si se pasó un ID por la URL y no está vacío...
        if (isset($_GET['sucursal_id']) && $_GET['sucursal_id'] !== "") {
            return $this->producto->obtenerPorSucursal($_GET['sucursal_id']);
        } 
        
        // 2. Si el usuario es empleado, forzamos su sucursal (seguridad)
        if ($_SESSION['role'] !== 'Admin') {
            return $this->producto->obtenerPorSucursal($_SESSION['id_sucursal']);
        }

        // 3. Si es Admin y el filtro está vacío, mostramos TODO
        return $this->producto->obtenerTodoElInventario();
    }

    public function abastecer() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_inventario = $_POST['id_inventario'];
            $cantidad_nueva = $_POST['cantidad'];

            try {
                // Ahora $this->db ya no será NULL
                $query = "UPDATE inventario SET stock_actual = stock_actual + :cantidad WHERE id_inventario = :id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':cantidad' => $cantidad_nueva,
                    ':id'       => $id_inventario
                ]);

                header("Location: ../../public/index.php?view=inventario&status=abastecido");
                exit();
            } catch (Exception $e) {
                die("Error al abastecer: " . $e->getMessage());
            }
        }
    }

    public function listarProductosDisponibles() {
        // Obtenemos la sucursal del usuario de la sesión
        $id_sucursal = $_SESSION['id_sucursal'] ?? null;

        // Si no hay sucursal (y no es admin), no debería poder vender nada
        if (!$id_sucursal && $_SESSION['role'] !== 'Admin') {
            return [];
        }

        // La consulta une 'inventario' con 'productos' para obtener nombres y precios
        // Usamos 'stock_actual' que es el nombre que usas en el método abastecer
        $query = "SELECT p.id_producto, p.nombre_producto, p.precio_base, i.stock_actual as stock, i.id_inventario
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto";
        
        // Si no es Admin, solo mostramos lo de SU sucursal
        if ($_SESSION['role'] !== 'Admin') {
            $query .= " WHERE i.id_sucursal = :id_s AND i.stock_actual > 0";
        } else {
            $query .= " WHERE i.stock_actual > 0";
        }

        $query .= " ORDER BY p.nombre_producto ASC";

        try {
            $stmt = $this->db->prepare($query);
            if ($_SESSION['role'] !== 'Admin') {
                $stmt->bindValue(':id_s', $id_sucursal);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return []; // Retorna vacío si hay error para no romper la vista
        }
    }
}

// Enrutador del controlador
if (isset($_GET['action'])) {
    if (!isset($_SESSION)) { session_start(); }
    $controller = new InventarioController();
    
    if ($_GET['action'] == 'abastecer') {
        $controller->abastecer();
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
    }

    public function abastecer() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // 1. Limpiamos buffer y declaramos JSON para evitar errores de conexión en Fetch/AJAX
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');

            try {
            // Recibimos los datos enviados desde la vista
            $id_producto = \App\Http\Validator::positiveInt($_POST['id_producto'] ?? null, 'producto');
            $id_sucursal = \App\Http\Validator::positiveInt($_POST['id_sucursal'] ?? null, 'sucursal');
            $cantidad_nueva = \App\Http\Validator::positiveInt($_POST['cantidad'] ?? null, 'cantidad');
            if ($cantidad_nueva > 100000) throw new InvalidArgumentException('La cantidad excede el limite permitido.');

            if (!$id_producto || !$id_sucursal || !$cantidad_nueva) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios.']);
                exit();
            }

                $this->db->beginTransaction();
                // Obtenemos producto, sucursal y vida útil usando id_producto e id_sucursal
                $sqlInfo = "SELECT i.id_inventario, i.id_producto, i.id_sucursal, i.stock_minimo, p.dias_vida_util 
                            FROM inventario i 
                            JOIN productos p ON i.id_producto = p.id_producto 
                            WHERE i.id_producto = :id_p AND i.id_sucursal = :id_s LIMIT 1";
                
                $stmtInfo = $this->db->prepare($sqlInfo);
                $stmtInfo->execute([':id_p' => $id_producto, ':id_s' => $id_sucursal]);
                $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if (!$info) {
                    throw new Exception("El registro de inventario no existe para esta sucursal.");
                }

                // Calculamos la nueva fecha de caducidad
                $nueva_fecha = null; 
                if ($info['dias_vida_util'] > 0) {
                    date_default_timezone_set('America/Tegucigalpa');
                    $nueva_fecha = date('Y-m-d', strtotime("+" . $info['dias_vida_util'] . " days"));
                }

                // Verificamos si YA EXISTE un lote exacto
                $queryCheck = "SELECT id_inventario FROM inventario 
                               WHERE id_producto = :id_producto 
                               AND id_sucursal = :id_sucursal 
                               AND (fecha_caducidad = :fecha1 OR (fecha_caducidad IS NULL AND :fecha2 IS NULL))";
                
                $stmtCheck = $this->db->prepare($queryCheck);
                $stmtCheck->execute([
                    ':id_producto' => $info['id_producto'],
                    ':id_sucursal' => $info['id_sucursal'],
                    ':fecha1'      => $nueva_fecha,
                    ':fecha2'      => $nueva_fecha
                ]);
                
                $loteExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($loteExistente) {
                    // SI EXISTE: Actualizamos el stock
                    $queryUpdate = "UPDATE inventario 
                                    SET stock_actual = stock_actual + :cantidad 
                                    WHERE id_inventario = :id_lote";
                    $stmtUpdate = $this->db->prepare($queryUpdate);
                    $stmtUpdate->execute([
                        ':cantidad' => $cantidad_nueva,
                        ':id_lote'  => $loteExistente['id_inventario']
                    ]);
                } else {
                    // NO EXISTE: Insertamos un NUEVO LOTE
                    $queryInsert = "INSERT INTO inventario (id_sucursal, id_producto, stock_actual, stock_minimo, fecha_caducidad) 
                                    VALUES (:id_sucursal, :id_producto, :cantidad, :minimo, :fecha)";
                    $stmtInsert = $this->db->prepare($queryInsert);
                    $stmtInsert->execute([
                        ':id_sucursal' => $info['id_sucursal'],
                        ':id_producto' => $info['id_producto'],
                        ':cantidad'    => $cantidad_nueva,
                        ':minimo'      => $info['stock_minimo'],
                        ':fecha'       => $nueva_fecha
                    ]);
                }

                // 2. Respondemos con éxito en formato JSON en lugar del header()
                $this->db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Inventario abastecido correctamente.']);
                exit();
                
            } catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                // 3. Atrapamos errores y los enviamos en JSON
                \App\Support\Logger::error($e);
                echo json_encode(['status' => 'error', 'message' => 'No fue posible abastecer el inventario.']);
                exit();
            }
        }
    }

    public function listarProductosDisponibles($id_sucursal_forzado = null) {
        // 1. Detectar el contexto del usuario
        $id_sucursal_session = $_SESSION['id_sucursal'] ?? null;
        $id_rol = (int)($_SESSION['id_rol'] ?? 0);

        // 2. Determinar qué sucursal filtrar:
        $id_s_final = $id_sucursal_forzado ?? $id_sucursal_session;
        if ($id_s_final && !Auth::canAccessBranch((int) $id_s_final)) return [];

        // La consulta base
        $query = "SELECT p.id_producto, p.nombre_producto, p.precio_base, i.stock_actual as stock, i.id_inventario, s.nombre_sucursal
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto
                INNER JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                WHERE i.stock_actual > 0";
        
        // 3. Aplicar el filtro siempre que tengamos un ID de sucursal
        if ($id_s_final) {
            $query .= " AND i.id_sucursal = :id_s";
        } else {
            if ($id_rol !== 1 && $id_rol !== 3) return []; 
        }

        $query .= " ORDER BY p.nombre_producto ASC";

        try {
            $stmt = $this->db->prepare($query);
            
            if ($id_s_final) {
                $stmt->bindValue(':id_s', $id_s_final, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return []; 
        }
    }

    public function registrarProducto() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');

            try {
                $this->db->beginTransaction();

                $id_categoria = !empty($_POST['id_categoria']) ? \App\Http\Validator::positiveInt($_POST['id_categoria'], 'categoria') : null;
                if (!empty($_POST['nueva_categoria_nombre'])) {
                    $newCategory = \App\Http\Validator::text($_POST['nueva_categoria_nombre'], 'categoria', 100);
                    $stmtCat = $this->db->prepare("INSERT INTO categorias (nombre_categoria) VALUES (:nom)");
                    $stmtCat->execute([':nom' => $newCategory]);
                    $id_categoria = $this->db->lastInsertId();
                }

                if (!$id_categoria) throw new Exception("Debe seleccionar o crear una categoría.");

                $nombre = \App\Http\Validator::text($_POST['nombre_producto'] ?? '', 'producto', 150);
                $precio = \App\Http\Validator::money($_POST['precio_base'] ?? null, 'precio', 1000000);
                $tipo_producto = \App\Http\Validator::enum($_POST['tipo_producto'] ?? 'panaderia', ['pastel', 'panaderia'], 'tipo de producto');
                $descripcion_base = \App\Http\Validator::text($_POST['descripcion'] ?? '', 'descripcion', 2000, false);
                $sucursales = array_values(array_unique(array_map('intval', (array) ($_POST['id_sucursal'] ?? []))));
                $stock_inicial = isset($_POST['stock_inicial']) && $_POST['stock_inicial'] !== '' ? (int) $_POST['stock_inicial'] : 0;
                if ($stock_inicial < 0 || $stock_inicial > 100000) throw new InvalidArgumentException('El stock inicial no es valido.');
                $dias_vida_util = !empty($_POST['dias_vida_util']) ? (int) $_POST['dias_vida_util'] : 0;
                if ($dias_vida_util < 0 || $dias_vida_util > 3650) throw new InvalidArgumentException('La vida util no es valida.');

                $detalle_producto = [
                    'tipo_producto' => $tipo_producto,
                    'tamano' => $_POST['tamano'] ?? null,
                    'cantidad_tortas' => $_POST['cantidad_tortas'] ?? null,
                    'rellenos' => $_POST['rellenos'] ?? null,
                    'coberturas' => $_POST['coberturas'] ?? null,
                    'observaciones' => $_POST['observaciones'] ?? null,
                    'descripcion_general' => $descripcion_base
                ];

                if ($tipo_producto === 'pastel') {
                    $descripcion = json_encode($detalle_producto, JSON_UNESCAPED_UNICODE);
                } else {
                    $descripcion = !empty($descripcion_base) ? $descripcion_base : 'Producto de panadería / bebidas / otros.';
                }

                if (empty($sucursales)) throw new Exception("Debe seleccionar al menos una sucursal.");

                $imagen_url = 'default_product.png';
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['imagen'];
                    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
                        throw new InvalidArgumentException('La imagen no es valida o supera 5 MB.');
                    }
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($extensions[$mime])) throw new InvalidArgumentException('Solo se permiten imagenes JPG, PNG o WebP.');
                    $dir = dirname(__DIR__, 2) . '/public/img/productos';
                    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) throw new RuntimeException('No se pudo preparar el directorio de imagenes.');
                    $nombre_archivo = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nombre_archivo)) throw new RuntimeException('No se pudo guardar la imagen.');
                    $imagen_url = $nombre_archivo;
                }

                $stmtProd = $this->db->prepare("INSERT INTO productos (id_categoria, nombre_producto, descripcion, precio_base, imagen_url, dias_vida_util) VALUES (:id_cat, :nom, :desc, :pre, :img, :dias)");
                $stmtProd->execute([
                    ':id_cat' => $id_categoria,
                    ':nom'    => $nombre,
                    ':desc'   => $descripcion,
                    ':pre'    => $precio,
                    ':img'    => $imagen_url,
                    ':dias'   => $dias_vida_util
                ]);
                $id_nuevo_p = $this->db->lastInsertId();

                $stmtInv = $this->db->prepare("INSERT INTO inventario (id_sucursal, id_producto, stock_actual, stock_minimo) VALUES (:id_s, :id_p, :stock, 5)");
                foreach ($sucursales as $id_s) {
                    $stmtInv->execute([
                        ':id_s' => $id_s,
                        ':id_p' => $id_nuevo_p,
                        ':stock' => $stock_inicial
                    ]);
                }

                $this->db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Producto registrado con éxito.']);

            } catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                \App\Support\Logger::error($e);
                echo json_encode(['status' => 'error', 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible registrar el producto.']);
            }
            exit;
        }
    }

    public function registrarMerma() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (ob_get_length()) ob_clean(); 
            header('Content-Type: application/json');
            date_default_timezone_set('America/Tegucigalpa');
            
            try {
                if (!isset($_SESSION['id_usuario'])) {
                    throw new Exception("Error de sesión: No se detecta un usuario activo.");
                }

                $this->db->beginTransaction();

                $id_inventario = !empty($_POST['id_inventario_merma']) ? (int)$_POST['id_inventario_merma'] : null;
                if (empty($id_inventario) && !empty($_POST['id_producto_merma']) && !empty($_POST['id_sucursal_merma'])) {
                    $stmtInventario = $this->db->prepare("SELECT id_inventario FROM inventario WHERE id_producto = ? AND id_sucursal = ? LIMIT 1");
                    $stmtInventario->execute([(int)$_POST['id_producto_merma'], (int)$_POST['id_sucursal_merma']]);
                    $id_inventario = (int)$stmtInventario->fetchColumn();
                }

                if (empty($id_inventario)) {
                    throw new Exception("No se encontró el registro de inventario para registrar la merma.");
                }

                $cantidad = \App\Http\Validator::positiveInt($_POST['cantidad_merma'] ?? null, 'cantidad');
                $motivo = \App\Http\Validator::text($_POST['motivo_merma'] ?? '', 'motivo', 250);
                $id_usuario = (int)$_SESSION['id_usuario'];

                $stmtCheck = $this->db->prepare("SELECT stock_actual FROM inventario WHERE id_inventario = ? FOR UPDATE");
                $stmtCheck->execute([$id_inventario]);
                $stock = $stmtCheck->fetchColumn();

                if ($stock === false) {
                    throw new Exception("El producto no existe en el inventario.");
                }

                if ($stock < $cantidad) {
                    throw new Exception("No puedes mermar más del stock actual ($stock).");
                }

                $stmtUpdate = $this->db->prepare("UPDATE inventario SET stock_actual = stock_actual - ? WHERE id_inventario = ?");
                $stmtUpdate->execute([$cantidad, $id_inventario]);

                $stmtMerma = $this->db->prepare("INSERT INTO mermas (id_inventario, id_usuario, cantidad, motivo) VALUES (?, ?, ?, ?)");
                $stmtMerma->execute([$id_inventario, $id_usuario, $cantidad, $motivo]);

                $this->db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Merma registrada y stock actualizado.']);

            } catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                \App\Support\Logger::error($e);
                echo json_encode(['status' => 'error', 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible registrar la merma.']);
            }
            exit;
        }
    }

    public function procesarMermaCaducado() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            date_default_timezone_set('America/Tegucigalpa');
            
            try {
                $id_inventario = (int)$_POST['id_inventario'];
                $id_usuario = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 1; 

                $this->db->beginTransaction();

                $stmtCheck = $this->db->prepare("SELECT stock_actual FROM inventario WHERE id_inventario = ? FOR UPDATE");
                $stmtCheck->execute([$id_inventario]);
                $stock_perdido = $stmtCheck->fetchColumn();

                if ($stock_perdido === false) {
                    throw new Exception("El registro de inventario no existe.");
                }

                if ($stock_perdido > 0) {
                    $stmtUpdate = $this->db->prepare("UPDATE inventario SET stock_actual = 0 WHERE id_inventario = ?");
                    $stmtUpdate->execute([$id_inventario]);

                    $stmtMerma = $this->db->prepare("INSERT INTO mermas (id_inventario, id_usuario, cantidad, motivo) VALUES (?, ?, ?, 'Producto Caducado')");
                    $stmtMerma->execute([$id_inventario, $id_usuario, $stock_perdido]);
                }

                $this->db->commit();
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'El lote caducado ha sido retirado y registrado en mermas correctamente.'
                ]);
                exit();

            } catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'No fue posible procesar el lote.'
                ]);
                exit();
            }
        }
    }
}

// =========================================================================
// ENRUTADOR AJAX INTELIGENTE: Solo responde si explícitamente se solicita una acción.
// =========================================================================
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action !== null) {
    Auth::requireLogin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        \App\Http\Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    }
    Csrf::validateRequest();
    if ($action === 'registrar') {
        Auth::requireRoles([Auth::ADMIN, Auth::SUPERUSER]);
        foreach ((array) ($_POST['id_sucursal'] ?? []) as $branchId) Auth::requireBranch((int) $branchId);
    } elseif (in_array($action, ['abastecer', 'abastecer_producto'], true)) {
        Auth::requireBranch((int) ($_POST['id_sucursal'] ?? 0));
    } elseif ($action === 'registrarMerma' && !empty($_POST['id_sucursal_merma'])) {
        Auth::requireBranch((int) $_POST['id_sucursal_merma']);
    } elseif ($action === 'registrar_merma') {
        $inventoryId = (int) ($_POST['id_inventario'] ?? 0);
        $accessDb = (new Database())->getConnection();
        $accessStmt = $accessDb->prepare('SELECT id_sucursal FROM inventario WHERE id_inventario = ?');
        $accessStmt->execute([$inventoryId]);
        Auth::requireBranch((int) $accessStmt->fetchColumn());
    }
    // Si hay una acción, limpiamos el buffer para asegurar un JSON impecable
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    $controller = new InventarioController();
    
    if ($action == 'abastecer' || $action == 'abastecer_producto') {
        $controller->abastecer();
    } elseif ($action == 'registrar') {
        $controller->registrarProducto();
    } elseif ($action == 'registrarMerma') {
        $controller->registrarMerma();
    } elseif ($action == 'registrar_merma') { 
        $controller->procesarMermaCaducado();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida: ' . $action]);
        exit();
    }
}
// Si $action es null, PHP continuará silenciosamente permitiendo que la vista 
// use las funciones de la clase sin imprimir mensajes extraños en pantalla.
