<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Http\Response;
use App\Http\Validator;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;
use App\Services\InventoryStockService;
use App\Services\ProductOptionPricingService;
use App\Services\SupplyStockService;

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

            $normalized = [];
            foreach ($cart as $item) {
                if (!is_array($item)) throw new InvalidArgumentException('El carrito contiene un producto invalido.');
                $productId = Validator::positiveInt($item['id'] ?? null, 'producto');
                $quantity = Validator::positiveInt($item['cantidad'] ?? null, 'cantidad');
                if ($quantity > 1000) throw new InvalidArgumentException('La cantidad por producto excede el limite permitido.');
                $normalized[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'options' => is_array($item['opciones'] ?? null) ? array_values($item['opciones']) : [],
                ];
            }

            $this->db->beginTransaction();
            $stockService = new InventoryStockService($this->db);
            $supplyStockService = new SupplyStockService($this->db);
            $optionPricing = new ProductOptionPricingService($this->db);
            $productQuery = $this->db->prepare(
                "SELECT p.nombre_producto, p.precio_base, p.control_inventario
                 FROM productos p
                 JOIN producto_sucursales ps ON ps.id_producto = p.id_producto
                    AND ps.id_sucursal = ? AND ps.estado = 'Activo'
                 WHERE p.id_producto = ? AND p.estado = 'Activo' AND p.disponible_venta_directa = 1"
            );
            $items = [];
            $productQuantities = [];
            $supplyProductQuantities = [];
            $total = 0.0;
            foreach ($normalized as $line) {
                $productId = $line['product_id'];
                $quantity = $line['quantity'];
                $productQuery->execute([$branchId, $productId]);
                $product = $productQuery->fetch(PDO::FETCH_ASSOC);
                if (!$product) throw new InvalidArgumentException('Uno de los productos no esta disponible para venta directa.');
                $configuration = $optionPricing->price($productId, $line['options']);
                $price = round((float) $product['precio_base'] + $configuration['surcharge'], 2);
                $subtotal = round($price * $quantity, 2);
                $total = round($total + $subtotal, 2);
                $items[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'options' => $configuration['options'],
                ];
                if ($product['control_inventario'] === 'producto') {
                    $productQuantities[$productId] = ($productQuantities[$productId] ?? 0) + $quantity;
                    if ($productQuantities[$productId] > 1000) throw new InvalidArgumentException('La cantidad acumulada excede el limite permitido.');
                } elseif ($product['control_inventario'] === 'insumos') {
                    $supplyProductQuantities[$productId] = ($supplyProductQuantities[$productId] ?? 0) + $quantity;
                }
            }
            if ($total <= 0 || $total > 99999999.99) throw new InvalidArgumentException('El total de la venta no es valido.');

            $productAllocations = [];
            foreach ($productQuantities as $productId => $quantity) {
                $locked = $stockService->lockForSale($productId, $branchId, $quantity);
                array_push($productAllocations, ...$locked['allocations']);
            }
            $supplyAllocations = $supplyStockService->lockForSale($supplyProductQuantities, $branchId);

            $sale = $this->db->prepare('INSERT INTO ventas_directas (id_usuario, id_sucursal, total, metodo_pago) VALUES (?, ?, ?, ?)');
            $sale->execute([(int) $_SESSION['id_usuario'], $branchId, $total, $paymentMethod]);
            $saleId = (int) $this->db->lastInsertId();

            $insertItem = $this->db->prepare('INSERT INTO venta_items (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
            $insertOption = $this->db->prepare(
                'INSERT INTO venta_item_opciones (id_item, id_opcion, grupo_nombre, opcion_nombre, recargo_unitario)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $userId = (int) $_SESSION['id_usuario'];
            foreach ($items as $item) {
                $insertItem->execute([$saleId, $item['product_id'], $item['quantity'], $item['price'], $item['subtotal']]);
                $itemId = (int) $this->db->lastInsertId();
                foreach ($item['options'] as $option) {
                    $insertOption->execute([$itemId, $option['id_opcion'], $option['grupo_nombre'], $option['opcion_nombre'], $option['recargo']]);
                }
            }
            $stockService->deductForSale($productAllocations, $userId, $saleId);
            $supplyStockService->deductForSale($supplyAllocations, $userId, $saleId);

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
