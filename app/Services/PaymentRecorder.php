<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Input\PaymentReceiptInput;
use InvalidArgumentException;
use LogicException;
use PDO;

final class PaymentRecorder
{
    public function __construct(private PDO $db) {}

    /**
     * Reserva la referencia dentro de la misma transacción que guarda el pago.
     * Un reintento idéntico recupera los identificadores del pago ya registrado.
     *
     * @return array{id_comprobante:int,id_venta:?int,id_pago:?int,id_pedido:?int}
     */
    public function claim(PaymentReceiptInput $payment, int $branchId, int $userId, string $operation, array $payload): array
    {
        $table = $this->receiptTable($payment->method);
        $card = $payment->method === 'Tarjeta';
        $columns = $card ? 'id_sucursal, terminal, fecha_operacion, referencia' : 'id_sucursal, referencia';
        $placeholders = $card ? '?, ?, ?, ?' : '?, ?';
        $receipt = $card
            ? [$branchId, $payment->terminal, $payment->date, $payment->reference]
            : [$branchId, $payment->reference];
        $attempt = hash('sha256', serialize([$operation, $userId, $payment->attempt]));
        $fingerprint = hash('sha256', serialize([$receipt, $payment->amount, $payload]));
        $insert = $this->db->prepare(
            "INSERT INTO $table ($columns, monto, intento_hash, solicitud_hash)
             VALUES ($placeholders, ?, ?, ?) ON DUPLICATE KEY UPDATE id_comprobante = id_comprobante"
        );
        $insert->execute([...$receipt, $payment->amount, $attempt, $fingerprint]);

        $select = $this->db->prepare(
            "SELECT c.id_comprobante, c.solicitud_hash, c.id_venta, c.id_pago, pp.id_pedido
             FROM $table c
             LEFT JOIN pagos_pedido pp ON pp.id_pago = c.id_pago
             WHERE c.intento_hash = ?
             FOR UPDATE"
        );
        $select->execute([$attempt]);
        $record = $select->fetch(PDO::FETCH_ASSOC);
        if (!$record || !hash_equals($record['solicitud_hash'], $fingerprint)) {
            throw new InvalidArgumentException('El comprobante o intento ya está registrado. Revisa el historial antes de intentar cobrar de nuevo.');
        }

        return [
            'id_comprobante' => (int) $record['id_comprobante'],
            'id_venta' => $record['id_venta'] === null ? null : (int) $record['id_venta'],
            'id_pago' => $record['id_pago'] === null ? null : (int) $record['id_pago'],
            'id_pedido' => $record['id_pedido'] === null ? null : (int) $record['id_pedido'],
        ];
    }

    public function linkSale(string $method, int $receiptId, int $saleId): void
    {
        $table = $this->receiptTable($method);
        $this->db->prepare("UPDATE $table SET id_venta = ? WHERE id_comprobante = ?")
            ->execute([$saleId, $receiptId]);
    }

    public function recordOrderPayment(
        int $orderId,
        int $userId,
        float $amount,
        string $method,
        ?PaymentReceiptInput $payment,
        ?int $receiptId,
        ?int $cashId
    ): void {
        $statement = $this->db->prepare(
            'INSERT INTO pagos_pedido (id_pedido, id_usuario, monto, metodo_pago, referencia, id_caja) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$orderId, $userId, $amount, $method, $payment?->reference, $cashId]);
        if ($receiptId !== null) {
            $paymentId = (int) $this->db->lastInsertId();
            $table = $this->receiptTable($method);
            $this->db->prepare("UPDATE $table SET id_pago = ? WHERE id_comprobante = ?")
                ->execute([$paymentId, $receiptId]);
        }
    }

    private function receiptTable(string $method): string
    {
        return match ($method) {
            'Tarjeta' => 'comprobantes_tarjeta',
            'Transferencia' => 'comprobantes_transferencia',
            default => throw new LogicException('Método sin comprobante electrónico.'),
        };
    }
}
