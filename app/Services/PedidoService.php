<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Input\PaymentReceiptInput;
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

    public function crear(array $input, array $files = [], bool $json = false): void
    {
        $storedDesignFiles = [];
        $designStorage = new OrderDesignStorage(dirname(__DIR__, 2) . '/storage/order-designs');
        try {
            $submittedLines = is_array($input['lineas'] ?? null) ? $input['lineas'] : [];
            $legacyProducts = is_array($input['productos'] ?? null) ? $input['productos'] : [];
            if ($submittedLines === [] && $legacyProducts === []) {
                throw new InvalidArgumentException('Añade al menos un producto al pedido.');
            }
            $clientId = Validator::positiveInt($input['id_cliente'] ?? null, 'cliente');
            $branchId = Validator::positiveInt($input['id_sucursal'] ?? ($_SESSION['id_sucursal'] ?? null), 'sucursal');
            Auth::requirePermission('orders.create', $branchId);
            $userId = (int) $_SESSION['id_usuario'];
            $paymentType = Validator::enum($input['tipo_pago'] ?? '', ['Pendiente', 'Abonado', 'Pagado'], 'tipo de pago');
            $paymentMethod = $paymentType === 'Pendiente' ? 'Efectivo'
                : Validator::enum($input['metodo_pago'] ?? 'Efectivo', ['Efectivo', 'Transferencia', 'Tarjeta', 'Otro'], 'metodo de pago');
            $payment = PaymentReceiptInput::from($paymentMethod, $input);
            $deliveryDate = Validator::dateTimeLocal($input['fecha_entrega'] ?? null, 'fecha de entrega');
            $notes = Validator::text($input['observaciones'] ?? '', 'observaciones', 1000, false);
            $payload = [$clientId, $deliveryDate, $notes, $paymentType, $submittedLines, $legacyProducts];
            if ($paymentType === 'Abonado') $payload[] = $input['monto_abono'] ?? null;
            if ($payment !== null) $payload[] = $this->designFingerprints($files);

            $this->db->beginTransaction();
            $payments = new PaymentRecorder($this->db);
            $receipt = $payment ? $payments->claim($payment, $branchId, $userId, 'pedido.nuevo', $payload) : null;
            if (isset($receipt['id_pedido'])) {
                $this->db->commit();
                $this->created($receipt['id_pedido'], $json, true);
                return;
            }

            if (new \DateTimeImmutable($deliveryDate) < new \DateTimeImmutable('-5 minutes')) {
                throw new InvalidArgumentException('La fecha de entrega no puede estar en el pasado.');
            }
            $clientQuery = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE id_cliente = ? AND estado = 'Activo'");
            $clientQuery->execute([$clientId]);
            if (!(bool) $clientQuery->fetchColumn()) throw new InvalidArgumentException('El cliente no existe o esta inactivo.');

            $pricing = (new OrderPricingService($this->db))->price($branchId, $input);
            $items = $pricing['items'];
            $total = $pricing['total'];
            $paid = $paymentType === 'Pagado'
                ? $total
                : ($paymentType === 'Abonado'
                    ? Validator::intRange($input['monto_abono'] ?? null, 'abono', 1, (int) $total)
                    : 0.0);
            if ($paymentType === 'Abonado' && $paid >= $total) {
                throw new InvalidArgumentException('Para liquidar el total selecciona la opcion Pagado.');
            }
            $payment?->assertAmount($paid);
            $balance = round($total - $paid, 2);
            $paymentStatus = $balance <= 0 ? 'Pagado' : ($paid > 0 ? 'Abonado' : 'Pendiente');
            $cashId = $paid > 0 && $paymentMethod === 'Efectivo'
                ? (new CashDrawerSession($this->db))->forCashMovement($branchId)
                : null;

            $order = $this->db->prepare("INSERT INTO pedidos (id_cliente, id_sucursal, id_usuario, fecha_entrega, total_pedido, monto_abonado, saldo_pendiente, estado_pago, observaciones_generales, estado) VALUES (:cliente, :sucursal, :usuario, :fecha, :total, :abono, :saldo, :pago, :observaciones, 'Pendiente')");
            $order->execute([':cliente' => $clientId, ':sucursal' => $branchId, ':usuario' => $userId, ':fecha' => $deliveryDate, ':total' => $total, ':abono' => $paid, ':saldo' => $balance, ':pago' => $paymentStatus, ':observaciones' => $notes]);
            $orderId = (int) $this->db->lastInsertId();
            $detail = $this->db->prepare('INSERT INTO pedido_detalles (id_pedido, id_producto, cantidad, precio_unitario, detalles_personalizacion, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
            $detailOption = $this->db->prepare('INSERT INTO pedido_detalle_opciones (id_detalle, id_opcion, grupo_nombre, opcion_nombre, recargo_unitario) VALUES (?, ?, ?, ?, ?)');
            $detailDesign = $this->db->prepare(
                'INSERT INTO pedido_detalle_diseno
                    (id_detalle, color_descripcion, frase, instrucciones, recargo_unitario,
                     archivo_nombre_interno, archivo_nombre_original, archivo_mime, archivo_tamano)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $detail->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['notes'], $item['subtotal']]);
                $detailId = (int) $this->db->lastInsertId();
                foreach ($item['options'] as $option) {
                    $detailOption->execute([$detailId, $option['id_opcion'], $option['grupo_nombre'], $option['opcion_nombre'], $option['recargo']]);
                }
                $design = $item['design'];
                $upload = $this->designUpload($files, (string) ($design['upload_key'] ?? ''));
                if ($upload !== null && (!$design['enabled'] || !$design['allow_image'])) {
                    throw new InvalidArgumentException('Este producto no permite adjuntar una imagen de diseno.');
                }
                if ($design['enabled'] || $design['phrase'] !== '' || $design['color'] !== '') {
                    $stored = $designStorage->store($upload);
                    if ($stored !== null) $storedDesignFiles[] = $stored['internal_name'];
                    $detailDesign->execute([
                        $detailId,
                        $design['color'] !== '' ? $design['color'] : null,
                        $design['phrase'] !== '' ? $design['phrase'] : null,
                        $design['instructions'] !== '' ? $design['instructions'] : null,
                        $design['surcharge'],
                        $stored['internal_name'] ?? null,
                        $stored['original_name'] ?? null,
                        $stored['mime'] ?? null,
                        $stored['size'] ?? null,
                    ]);
                }
            }
            if ($paid > 0) {
                $payments->recordOrderPayment($orderId, $userId, $paid, $paymentMethod, $payment, $receipt['id_comprobante'] ?? null, $cashId);
            }
            (new AuditService($this->db))->record('create', 'pedido', $orderId, $branchId, ['total' => $total, 'paid' => $paid]);
            $this->db->commit();
            $this->created($orderId, $json, false, $cashId !== null ? $branchId : null);
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            foreach ($storedDesignFiles as $filename) $designStorage->remove($filename);
            $invalid = $error instanceof InvalidArgumentException;
            if (!$invalid) Logger::error($error);
            $message = $invalid ? $error->getMessage() : 'No fue posible guardar el pedido.';
            if ($json) Response::json(['status' => 'error', 'message' => $message], $invalid ? 422 : 500);
            $_SESSION['flash_error'] = $message;
            Response::redirect('index.php?view=pedidos-nuevo&status=error');
        }
    }

    private function created(int $orderId, bool $json, bool $replayed = false, ?int $drawerBranch = null): void
    {
        $url = 'index.php?view=pedidos-lista&msg=pedido_ok';
        if ($json) {
            Response::json([
                'status' => 'success',
                'message' => $replayed ? 'El pedido ya estaba registrado.' : 'Pedido registrado correctamente.',
                'id_pedido' => $orderId,
                'sucursal_gaveta' => $drawerBranch,
                'redirect' => $url,
            ]);
        }
        if ($drawerBranch !== null) {
            $_SESSION['pedido_gaveta'] = ['pedido' => $orderId, 'sucursal' => $drawerBranch];
            $url .= '&pedido=' . $orderId;
        }
        Response::redirect($url);
    }

    private function designFingerprints(array $files): array
    {
        $fingerprints = [];
        foreach ((array) ($files['design_files']['error'] ?? []) as $key => $error) {
            if ((int) $error === UPLOAD_ERR_NO_FILE) continue;
            $file = $this->designUpload($files, (string) $key);
            $path = $file['tmp_name'] ?? '';
            $fingerprints[$key] = [$error, $file['name'] ?? '', is_uploaded_file($path) ? hash_file('sha256', $path) : null];
        }
        return $fingerprints;
    }

    private function designUpload(array $files, string $key): ?array
    {
        $group = $files['design_files'] ?? null;
        if (!is_array($group) || $key === '' || !isset($group['error'][$key])) return null;
        return [
            'name' => $group['name'][$key] ?? '',
            'type' => $group['type'][$key] ?? '',
            'tmp_name' => $group['tmp_name'][$key] ?? '',
            'error' => $group['error'][$key],
            'size' => $group['size'][$key] ?? 0,
        ];
    }
}
