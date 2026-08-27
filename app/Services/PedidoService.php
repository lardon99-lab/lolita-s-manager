<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Response;
use App\Http\Validator;
use App\Security\Auth;
use App\Support\Logger;
use InvalidArgumentException;
use PDO;
use Throwable;

final class PedidoService
{
    public function __construct(private PDO $db) {}

    public function crear(array $input): void
    {
        try {
            $clientId = Validator::positiveInt($input['id_cliente'] ?? null, 'cliente');
            $branchId = Validator::positiveInt($input['id_sucursal'] ?? ($_SESSION['id_sucursal'] ?? null), 'sucursal');
            Auth::requireBranch($branchId);
            $deliveryDate = Validator::date($input['fecha_entrega'] ?? null, 'fecha de entrega');
            $notes = Validator::text($input['observaciones'] ?? '', 'observaciones', 1000, false);
            $paymentType = Validator::enum($input['tipo_pago'] ?? '', ['Pendiente', 'Abonado', 'Pagado'], 'tipo de pago');
            $products = is_array($input['productos'] ?? null) ? $input['productos'] : [];
            $quantities = is_array($input['cantidades'] ?? null) ? $input['cantidades'] : [];
            $customizations = is_array($input['personalizacion'] ?? null) ? $input['personalizacion'] : [];
            $extras = is_array($input['costos_extras'] ?? null) ? $input['costos_extras'] : [];
            if ($products === [] || count($products) > 100 || count($products) !== count($quantities)) throw new InvalidArgumentException('El pedido no contiene productos validos.');

            $priceQuery = $this->db->prepare('SELECT precio_base FROM productos WHERE id_producto = ?');
            $items = [];
            $total = 0.0;
            foreach ($products as $index => $productValue) {
                $productId = Validator::positiveInt($productValue, 'producto');
                $quantity = Validator::positiveInt($quantities[$index] ?? null, 'cantidad');
                if ($quantity > 1000) throw new InvalidArgumentException('La cantidad excede el limite permitido.');
                $extra = Validator::money($extras[$index] ?? 0, 'costo extra', 100000);
                $customization = Validator::text($customizations[$index] ?? '', 'personalizacion', 1000, false);
                $priceQuery->execute([$productId]);
                $basePrice = $priceQuery->fetchColumn();
                if ($basePrice === false) throw new InvalidArgumentException('Uno de los productos ya no esta disponible.');
                $unitPrice = round((float) $basePrice + $extra, 2);
                $subtotal = round($unitPrice * $quantity, 2);
                $total += $subtotal;
                $items[] = [$productId, $quantity, $unitPrice, $customization, $subtotal];
            }
            $total = round($total, 2);
            $paid = $paymentType === 'Pagado' ? $total : ($paymentType === 'Abonado' ? Validator::money($input['monto_abono'] ?? 0, 'abono', $total) : 0.0);
            if ($paid > $total) throw new InvalidArgumentException('El abono no puede superar el total.');
            $balance = round($total - $paid, 2);
            $paymentStatus = $balance <= 0 ? 'Pagado' : ($paid > 0 ? 'Abonado' : 'Pendiente');

            $this->db->beginTransaction();
            $order = $this->db->prepare("INSERT INTO pedidos (id_cliente, id_sucursal, id_usuario, fecha_entrega, total_pedido, monto_abonado, saldo_pendiente, estado_pago, observaciones_generales, estado) VALUES (:cliente, :sucursal, :usuario, :fecha, :total, :abono, :saldo, :pago, :observaciones, 'Pendiente')");
            $order->execute([':cliente' => $clientId, ':sucursal' => $branchId, ':usuario' => (int) $_SESSION['id_usuario'], ':fecha' => $deliveryDate, ':total' => $total, ':abono' => $paid, ':saldo' => $balance, ':pago' => $paymentStatus, ':observaciones' => $notes]);
            $orderId = (int) $this->db->lastInsertId();
            $detail = $this->db->prepare('INSERT INTO pedido_detalles (id_pedido, id_producto, cantidad, precio_unitario, detalles_personalizacion, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($items as $item) $detail->execute([$orderId, ...$item]);
            $this->db->commit();
            Response::redirect('index.php?view=pedidos-lista&msg=pedido_ok');
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            Logger::error($error);
            $_SESSION['flash_error'] = $error instanceof InvalidArgumentException ? $error->getMessage() : 'No fue posible guardar el pedido.';
            Response::redirect('index.php?view=pedidos-nuevo&status=error');
        }
    }
}
