<?php
// app/controllers/InventarioController.php
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;
use App\Services\ProductImageStorage;
use App\Services\ProductCustomizationService;
use App\Services\ProductDesignService;
use App\Services\InventoryWasteService;
use App\Services\InventoryRestockService;

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Producto.php';

class InventarioController {
    private $db;
    private $producto;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection(); 
        
        // Aquí le pasas la conexión al modelo
        $this->producto = new Producto($this->db);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function listar() {
        Auth::requirePermission('inventory.view');
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
            if (!Auth::canAccessBranch($branchId, 'inventory.view')) return [];
            return $this->producto->obtenerPorSucursal($branchId);
        }

        // 3. Admin o SuperUser sin filtros: Ven todo el inventario global
        $allowed = Auth::allowedBranches('inventory.view');
        return $allowed === null ? $this->producto->obtenerTodoElInventario() : $this->producto->obtenerPorSucursales($allowed);
    }

    public function listarPorSucursal($id_sucursal) {
        if (!$id_sucursal) return [];
        if (!Auth::canAccessBranch((int) $id_sucursal, 'inventory.view')) return [];
        return $this->producto->obtenerPorSucursal($id_sucursal);
    }

    public function abastecer(): void
    {
        try {
            $branchId = \App\Http\Validator::positiveInt($_POST['id_sucursal'] ?? null, 'sucursal');
            $userId = \App\Http\Validator::positiveInt($_SESSION['id_usuario'] ?? null, 'usuario');
            $notes = \App\Http\Validator::text($_POST['observaciones'] ?? '', 'observaciones', 500, false);
            $idempotencyKey = \App\Http\Validator::text($_POST['idempotency_key'] ?? '', 'identificador de recepcion', 120);

            if (isset($_POST['items'])) {
                $decoded = json_decode((string) $_POST['items'], true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($decoded)) throw new InvalidArgumentException('El detalle del abastecimiento no es valido.');
                $items = array_values($decoded);
            } else {
                $items = [[
                    'product_id' => $_POST['id_producto'] ?? null,
                    'quantity' => $_POST['cantidad'] ?? null,
                    'expiry' => $_POST['fecha_caducidad'] ?? null,
                ]];
            }

            Auth::requirePermission('inventory.adjust', $branchId);
            $result = (new InventoryRestockService($this->db))->restock(
                $branchId,
                $userId,
                $items,
                $idempotencyKey,
                $notes
            );
            $message = $result['duplicate']
                ? 'Esta recepcion ya habia sido registrada; no se duplico el stock.'
                : "Se ingresaron {$result['units']} unidades de {$result['products']} productos.";
            \App\Http\Response::json(['status' => 'success', 'message' => $message, 'receipt' => $result]);
        } catch (InvalidArgumentException | JsonException $error) {
            \App\Http\Response::json(['status' => 'error', 'message' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            \App\Support\Logger::error($error);
            \App\Http\Response::json(['status' => 'error', 'message' => 'No fue posible registrar el abastecimiento.'], 500);
        }
    }

    public function listarProductosDisponibles($id_sucursal_forzado = null) {
        Auth::requirePermission('sales.create');
        // 1. Detectar el contexto del usuario
        $id_sucursal_session = $_SESSION['id_sucursal'] ?? null;
        $id_rol = (int)($_SESSION['id_rol'] ?? 0);

        // 2. Determinar qué sucursal filtrar:
        $id_s_final = $id_sucursal_forzado ?? $id_sucursal_session;
        if ($id_s_final && !Auth::canAccessBranch((int) $id_s_final, 'sales.create')) return [];

        // La consulta base
        $query = "SELECT p.id_producto, p.nombre_producto, p.precio_base,
                         SUM(i.stock_actual) AS stock, MIN(i.id_inventario) AS id_inventario,
                         s.nombre_sucursal, i.id_sucursal
                FROM inventario i
                INNER JOIN productos p ON i.id_producto = p.id_producto
                INNER JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                WHERE i.stock_actual > 0
                  AND (i.fecha_caducidad IS NULL OR i.fecha_caducidad >= CURRENT_DATE)
                  AND p.estado = 'Activo'";
        
        // 3. Aplicar el filtro siempre que tengamos un ID de sucursal
        if ($id_s_final) {
            $query .= " AND i.id_sucursal = :id_s";
        } else {
            if ($id_rol !== 1 && $id_rol !== 3) return []; 
        }

        $query .= " GROUP BY p.id_producto, p.nombre_producto, p.precio_base,
                            s.nombre_sucursal, i.id_sucursal
                    ORDER BY p.nombre_producto ASC";

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

            $imageStorage = new ProductImageStorage(dirname(__DIR__, 2) . '/public/img/productos');
            $imagen_url = 'default_product.png';
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

                $tamano = \App\Http\Validator::text($_POST['tamano'] ?? '', 'tamano', 80, false);
                $cantidad_tortas = filter_var($_POST['cantidad_tortas'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
                if ($tipo_producto === 'pastel' && $cantidad_tortas === false) throw new InvalidArgumentException('La cantidad de tortas no es valida.');
                $observaciones = \App\Http\Validator::text($_POST['observaciones'] ?? '', 'observaciones', 1000, false);
                $detalle_producto = [
                    'tipo_producto' => $tipo_producto,
                    'tamano' => $tamano,
                    'cantidad_tortas' => $cantidad_tortas,
                    'descripcion_general' => $descripcion_base
                ];

                if ($tipo_producto === 'pastel') {
                    $descripcion = json_encode($detalle_producto, JSON_UNESCAPED_UNICODE);
                } else {
                    $descripcion = $observaciones !== '' ? $observaciones : ($descripcion_base !== '' ? $descripcion_base : 'Producto de panadería / bebidas / otros.');
                }

                if (empty($sucursales)) throw new Exception("Debe seleccionar al menos una sucursal.");

                $imagen_url = $imageStorage->store(isset($_FILES['imagen']) && is_array($_FILES['imagen']) ? $_FILES['imagen'] : null);

                $stmtProd = $this->db->prepare("INSERT INTO productos (id_categoria, tipo_producto, nombre_producto, descripcion, precio_base, imagen_url, dias_vida_util) VALUES (:id_cat, :tipo, :nom, :desc, :pre, :img, :dias)");
                $stmtProd->execute([
                    ':id_cat' => $id_categoria,
                    ':tipo'   => $tipo_producto,
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
                    $inventoryId = (int) $this->db->lastInsertId();
                    $movement = $this->db->prepare("INSERT INTO movimientos_inventario (id_inventario, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, referencia_tipo, referencia_id) VALUES (?, ?, 'Inventario inicial', ?, 0, ?, 'productos', ?)");
                    $movement->execute([$inventoryId, (int) $_SESSION['id_usuario'], $stock_inicial, $stock_inicial, (int) $id_nuevo_p]);
                }

                (new ProductCustomizationService($this->db))->save([
                    'id_producto' => $id_nuevo_p,
                    'configuracion' => $tipo_producto === 'pastel' ? ($_POST['configuracion'] ?? '[]') : '[]',
                ]);
                (new ProductDesignService($this->db))->save((int) $id_nuevo_p, [
                    'permite_diseno' => $tipo_producto === 'pastel' ? ($_POST['permite_diseno'] ?? false) : false,
                    'permite_imagen' => $_POST['permite_imagen'] ?? false,
                    'recargo_diseno' => $_POST['recargo_diseno'] ?? 0,
                ]);

                (new AuditService($this->db))->record('product.created', 'productos', (int) $id_nuevo_p, null, ['sucursales' => $sucursales]);

                $this->db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Producto registrado con éxito.']);

            } catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                $imageStorage->remove($imagen_url);
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

            try {
                if (!isset($_SESSION['id_usuario'])) {
                    throw new Exception("Error de sesión: No se detecta un usuario activo.");
                }

                $cantidad = \App\Http\Validator::positiveInt($_POST['cantidad_merma'] ?? null, 'cantidad');
                $motivo = \App\Http\Validator::text($_POST['motivo_merma'] ?? '', 'motivo', 250);
                $id_usuario = (int)$_SESSION['id_usuario'];

                $this->db->beginTransaction();
                $wasteService = new InventoryWasteService($this->db);
                if (!empty($_POST['id_inventario_merma'])) {
                    $stock = $wasteService->lockInventoryLot(
                        \App\Http\Validator::positiveInt($_POST['id_inventario_merma'], 'inventario'),
                        $cantidad
                    );
                } else {
                    $productId = \App\Http\Validator::positiveInt($_POST['id_producto_merma'] ?? null, 'producto');
                    $branchId = \App\Http\Validator::positiveInt($_POST['id_sucursal_merma'] ?? null, 'sucursal');
                    Auth::requirePermission('inventory.adjust', $branchId);
                    $stock = $wasteService->lockProductStock($productId, $branchId, $cantidad);
                }

                Auth::requirePermission('inventory.adjust', $stock['branch_id']);
                $wasteService->deduct($stock['allocations'], $id_usuario, $motivo);
                $referenceId = $stock['allocations'][0]['inventory_id'];
                (new AuditService($this->db))->record('inventory.waste_recorded', 'inventario', $referenceId, $stock['branch_id'], ['cantidad' => $cantidad, 'motivo' => $motivo]);

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
            try {
                $id_inventario = (int)$_POST['id_inventario'];
                $id_usuario = \App\Http\Validator::positiveInt($_SESSION['id_usuario'] ?? null, 'usuario');

                $this->db->beginTransaction();

                $stmtCheck = $this->db->prepare("SELECT stock_actual, id_sucursal FROM inventario WHERE id_inventario = ? FOR UPDATE");
                $stmtCheck->execute([$id_inventario]);
                $inventory = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (!$inventory) {
                    throw new Exception("El registro de inventario no existe.");
                }
                $stock_perdido = (int) $inventory['stock_actual'];
                Auth::requirePermission('inventory.adjust', (int) $inventory['id_sucursal']);

                if ($stock_perdido > 0) {
                    $stmtUpdate = $this->db->prepare("UPDATE inventario SET stock_actual = 0 WHERE id_inventario = ?");
                    $stmtUpdate->execute([$id_inventario]);

                    $stmtMerma = $this->db->prepare("INSERT INTO mermas (id_inventario, id_usuario, cantidad, motivo) VALUES (?, ?, ?, 'Producto Caducado')");
                    $stmtMerma->execute([$id_inventario, $id_usuario, $stock_perdido]);
                    $movement = $this->db->prepare("INSERT INTO movimientos_inventario (id_inventario, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, motivo) VALUES (?, ?, 'Merma', ?, ?, 0, 'Producto Caducado')");
                    $movement->execute([$id_inventario, $id_usuario, -$stock_perdido, $stock_perdido]);
                    (new AuditService($this->db))->record('inventory.expired_discarded', 'inventario', $id_inventario, (int) $inventory['id_sucursal'], ['cantidad' => $stock_perdido]);
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
        Auth::requirePermission('products.manage');
        foreach ((array) ($_POST['id_sucursal'] ?? []) as $branchId) Auth::requirePermission('products.manage', (int) $branchId);
    } elseif (in_array($action, ['abastecer', 'abastecer_producto'], true)) {
        Auth::requirePermission('inventory.adjust', (int) ($_POST['id_sucursal'] ?? 0));
    } elseif ($action === 'registrarMerma' && !empty($_POST['id_sucursal_merma'])) {
        Auth::requirePermission('inventory.adjust', (int) $_POST['id_sucursal_merma']);
    } elseif ($action === 'registrar_merma') {
        $inventoryId = (int) ($_POST['id_inventario'] ?? 0);
        $accessDb = (new Database())->getConnection();
        $accessStmt = $accessDb->prepare('SELECT id_sucursal FROM inventario WHERE id_inventario = ?');
        $accessStmt->execute([$inventoryId]);
        Auth::requirePermission('inventory.adjust', (int) $accessStmt->fetchColumn());
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
