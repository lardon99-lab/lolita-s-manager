<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Input\CardPaymentInput;
use InvalidArgumentException;
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
    public function claimCard(CardPaymentInput $card, int $branchId, int $userId, string $operation, array $payload): array
    {
        $receipt = [$branchId, $card->terminal, $card->date, $card->reference];
        $attempt = hash('sha256', serialize([$operation, $userId, $card->attempt]));
        $fingerprint = hash('sha256', serialize([$receipt, $card->amount, $payload]));
        $insert = $this->db->prepare(
            'INSERT INTO comprobantes_tarjeta (id_sucursal, terminal, fecha_operacion, referencia, monto, intento_hash, solicitud_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id_comprobante = id_comprobante'
        );
        $insert->execute([...$receipt, $card->amount, $attempt, $fingerprint]);

        $select = $this->db->prepare(
            'SELECT ct.id_comprobante, ct.solicitud_hash, ct.id_venta, ct.id_pago, pp.id_pedido
             FROM comprobantes_tarjeta ct
             LEFT JOIN pagos_pedido pp ON pp.id_pago = ct.id_pago
             WHERE ct.intento_hash = ?
             FOR UPDATE'
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

    public function linkSale(int $receiptId, int $saleId): void
    {
        $this->db->prepare('UPDATE comprobantes_tarjeta SET id_venta = ? WHERE id_comprobante = ?')
            ->execute([$saleId, $receiptId]);
    }

    public function recordOrderPayment(
        int $orderId,
        int $userId,
        float $amount,
        string $method,
        ?CardPaymentInput $card,
        ?int $receiptId
    ): void {
        $statement = $this->db->prepare(
            'INSERT INTO pagos_pedido (id_pedido, id_usuario, monto, metodo_pago, referencia) VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$orderId, $userId, $amount, $method, $card?->reference]);
        if ($receiptId !== null) {
            $paymentId = (int) $this->db->lastInsertId();
            $this->db->prepare('UPDATE comprobantes_tarjeta SET id_pago = ? WHERE id_comprobante = ?')
                ->execute([$paymentId, $receiptId]);
        }
    }
}
