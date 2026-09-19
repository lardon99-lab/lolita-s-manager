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
            Auth::requirePermission('orders.create', $branchId);
            $deliveryDate = Validator::dateTimeLocal($input['fecha_entrega'] ?? null, 'fecha de entrega');
            if (new \DateTimeImmutable($deliveryDate) < new \DateTimeImmutable('-5 minutes')) {
                throw new InvalidArgumentException('La fecha de entrega no puede estar en el pasado.');
            }
            $notes = Validator::text($input['observaciones'] ?? '', 'observaciones', 1000, false);
            $paymentType = Validator::enum($input['tipo_pago'] ?? '', ['Pendiente', 'Abonado', 'Pagado'], 'tipo de pago');
            $clientQuery = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE id_cliente = ? AND estado = 'Activo'");
            $clientQuery->execute([$clientId]);
            if (!(bool) $clientQuery->fetchColumn()) throw new InvalidArgumentException('El cliente no existe o esta inactivo.');

            $this->db->beginTransaction();
            $pricing = (new OrderPricingService($this->db))->price($branchId, $input);
            $items = $pricing['items'];
            $total = $pricing['total'];
            $paid = $paymentType === 'Pagado' ? $total : ($paymentType === 'Abonado' ? Validator::money($input['monto_abono'] ?? 0, 'abono', $total) : 0.0);
            if ($paid > $total) throw new InvalidArgumentException('El abono no puede superar el total.');
            $balance = round($total - $paid, 2);
            $paymentStatus = $balance <= 0 ? 'Pagado' : ($paid > 0 ? 'Abonado' : 'Pendiente');

            $order = $this->db->prepare("INSERT INTO pedidos (id_cliente, id_sucursal, id_usuario, fecha_entrega, total_pedido, monto_abonado, saldo_pendiente, estado_pago, observaciones_generales, estado) VALUES (:cliente, :sucursal, :usuario, :fecha, :total, :abono, :saldo, :pago, :observaciones, 'Pendiente')");
            $order->execute([':cliente' => $clientId, ':sucursal' => $branchId, ':usuario' => (int) $_SESSION['id_usuario'], ':fecha' => $deliveryDate, ':total' => $total, ':abono' => $paid, ':saldo' => $balance, ':pago' => $paymentStatus, ':observaciones' => $notes]);
            $orderId = (int) $this->db->lastInsertId();
            $detail = $this->db->prepare('INSERT INTO pedido_detalles (id_pedido, id_producto, cantidad, precio_unitario, detalles_personalizacion, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
            $detailOption = $this->db->prepare('INSERT INTO pedido_detalle_opciones (id_detalle, id_opcion, grupo_nombre, opcion_nombre, recargo_unitario) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $item) {
                $detail->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['notes'], $item['subtotal']]);
                $detailId = (int) $this->db->lastInsertId();
                foreach ($item['options'] as $option) {
                    $detailOption->execute([$detailId, $option['id_opcion'], $option['grupo_nombre'], $option['opcion_nombre'], $option['recargo']]);
                }
            }
            if ($paid > 0) {
                $paymentMethod = Validator::enum($input['metodo_pago'] ?? 'Efectivo', ['Efectivo', 'Transferencia', 'Tarjeta', 'Otro'], 'metodo de pago');
                $payment = $this->db->prepare('INSERT INTO pagos_pedido (id_pedido, id_usuario, monto, metodo_pago) VALUES (?, ?, ?, ?)');
                $payment->execute([$orderId, (int) $_SESSION['id_usuario'], $paid, $paymentMethod]);
            }
            $this->db->commit();
            (new AuditService($this->db))->record('create', 'pedido', $orderId, $branchId, ['total' => $total, 'paid' => $paid]);
            Response::redirect('index.php?view=pedidos-lista&msg=pedido_ok');
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            Logger::error($error);
            $_SESSION['flash_error'] = $error instanceof InvalidArgumentException ? $error->getMessage() : 'No fue posible guardar el pedido.';
            Response::redirect('index.php?view=pedidos-nuevo&status=error');
        }
    }
}
