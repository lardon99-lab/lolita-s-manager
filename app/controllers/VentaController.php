<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Http\Response;
use App\Http\Validator;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;

final class VentaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function procesarVenta(): void
    {
        try {
            $input = json_decode((string) file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($input)) throw new InvalidArgumentException('La solicitud no es valida.');

            $branchId = Validator::positiveInt($input['id_sucursal'] ?? ($_SESSION['id_sucursal'] ?? null), 'sucursal');
            Auth::requirePermission('sales.create', $branchId);
            $paymentMethod = Validator::enum($input['metodo_pago'] ?? '', ['Efectivo', 'Transferencia', 'Tarjeta', 'Otro'], 'metodo de pago');
            $cart = $input['productos'] ?? null;
            if (!is_array($cart) || $cart === [] || count($cart) > 100) {
                throw new InvalidArgumentException('El carrito debe contener entre 1 y 100 productos.');
            }

            $quantities = [];
            foreach ($cart as $item) {
                if (!is_array($item)) throw new InvalidArgumentException('El carrito contiene un producto invalido.');
                $productId = Validator::positiveInt($item['id'] ?? null, 'producto');
                $quantity = Validator::positiveInt($item['cantidad'] ?? null, 'cantidad');
                $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
                if ($quantities[$productId] > 1000) throw new InvalidArgumentException('La cantidad por producto excede el limite permitido.');
            }

            $this->db->beginTransaction();
            $stockQuery = $this->db->prepare(
                "SELECT p.precio_base, i.stock_actual, i.id_inventario
                 FROM productos p
                 JOIN inventario i ON i.id_producto = p.id_producto
                 WHERE p.id_producto = :product AND i.id_sucursal = :branch AND p.estado = 'Activo'
                 FOR UPDATE"
            );
            $items = [];
            $total = 0.0;
            foreach ($quantities as $productId => $quantity) {
                $stockQuery->execute([':product' => $productId, ':branch' => $branchId]);
                $stock = $stockQuery->fetch(PDO::FETCH_ASSOC);
                if (!$stock) throw new InvalidArgumentException("El producto {$productId} no esta activo en la sucursal seleccionada.");
                $stockBefore = (int) $stock['stock_actual'];
                if ($stockBefore < $quantity) throw new InvalidArgumentException("Stock insuficiente para el producto {$productId}.");

                $price = round((float) $stock['precio_base'], 2);
                $subtotal = round($price * $quantity, 2);
                $total = round($total + $subtotal, 2);
                $items[] = [
                    'product_id' => $productId,
                    'inventory_id' => (int) $stock['id_inventario'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'stock_before' => $stockBefore,
                ];
            }
            if ($total <= 0 || $total > 99999999.99) throw new InvalidArgumentException('El total de la venta no es valido.');

            $sale = $this->db->prepare('INSERT INTO ventas_directas (id_usuario, id_sucursal, total, metodo_pago) VALUES (?, ?, ?, ?)');
            $sale->execute([(int) $_SESSION['id_usuario'], $branchId, $total, $paymentMethod]);
            $saleId = (int) $this->db->lastInsertId();

            $insertItem = $this->db->prepare('INSERT INTO venta_items (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
            $updateStock = $this->db->prepare('UPDATE inventario SET stock_actual = stock_actual - ? WHERE id_inventario = ? AND stock_actual >= ?');
            $movement = $this->db->prepare(
                "INSERT INTO movimientos_inventario
                 (id_inventario, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, referencia_tipo, referencia_id)
                 VALUES (?, ?, 'Venta', ?, ?, ?, 'ventas_directas', ?)"
            );
            foreach ($items as $item) {
                $insertItem->execute([$saleId, $item['product_id'], $item['quantity'], $item['price'], $item['subtotal']]);
                $updateStock->execute([$item['quantity'], $item['inventory_id'], $item['quantity']]);
                if ($updateStock->rowCount() !== 1) throw new RuntimeException('El inventario cambio durante la venta. Intenta nuevamente.');
                $movement->execute([
                    $item['inventory_id'],
                    (int) $_SESSION['id_usuario'],
                    -$item['quantity'],
                    $item['stock_before'],
                    $item['stock_before'] - $item['quantity'],
                    $saleId,
                ]);
            }

            (new AuditService($this->db))->record('sale.created', 'ventas_directas', $saleId, $branchId, [
                'total' => $total,
                'metodo_pago' => $paymentMethod,
                'productos' => count($items),
            ]);
            $this->db->commit();
            Response::json(['status' => 'success', 'message' => 'Venta registrada con exito.', 'id_venta' => $saleId]);
        } catch (JsonException|InvalidArgumentException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            App\Support\Logger::error($e);
            Response::json(['status' => 'error', 'message' => 'No fue posible registrar la venta.'], 500);
        }
    }
}

if (isset($_GET['action'])) {
    Auth::requireLogin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    Csrf::validateRequest();
    if ($_GET['action'] !== 'procesarVenta') Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404);
    (new VentaController())->procesarVenta();
}
