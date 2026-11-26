<?php
declare(strict_types=1);

namespace App\Http\Input;

use App\Http\Validator;
use InvalidArgumentException;

final class PaymentReceiptInput
{
    public function __construct(
        public string $method,
        public ?string $terminal,
        public ?string $date,
        public string $reference,
        public string $amount,
        public string $attempt
    ) {}

    public static function from(string $method, array $input): ?self
    {
        if (!in_array($method, ['Tarjeta', 'Transferencia'], true)) return null;
        $card = $method === 'Tarjeta';
        $suffix = $card ? 'tarjeta' : 'transferencia';
        $terminal = $date = null;
        if ($card) {
            $rawDate = $input['fecha_tarjeta'] ?? null;
            $date = Validator::date(is_string($rawDate) ? $rawDate : '', 'fecha del comprobante');
            if ($date > date('Y-m-d')) {
                throw new InvalidArgumentException('La fecha del comprobante no puede ser futura.');
            }
            $terminal = self::code($input['terminal_tarjeta'] ?? null, 'identificador de terminal', 32, true);
        }

        $rawAmount = $input['monto_' . $suffix] ?? null;
        $amount = is_string($rawAmount) ? str_replace(',', '.', trim($rawAmount)) : '';
        if (!preg_match('/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/', $amount) || (float) $amount <= 0) {
            throw new InvalidArgumentException('Ingresa un importe válido en lempiras para el comprobante.');
        }
        $attempt = $input['intento_' . $suffix] ?? null;
        if (!is_string($attempt) || !preg_match('/\A[a-f0-9]{32}\z/', $attempt)) {
            throw new InvalidArgumentException('El intento de registro no es válido. Vuelve a abrir el formulario.');
        }

        return new self(
            $method,
            $terminal,
            $date,
            self::code($input['referencia_' . $suffix] ?? null, 'referencia de la operación', 64, $card),
            number_format((float) $amount, 2, '.', ''),
            $attempt
        );
    }

    public function assertAmount(float $expected): void
    {
        if ($this->amount !== number_format($expected, 2, '.', '')) {
            throw new InvalidArgumentException('El importe del comprobante debe coincidir con el monto a cobrar. Revisa la operación antes de volver a cobrar.');
        }
    }

    private static function code(mixed $value, string $field, int $maxLength, bool $card): string
    {
        $code = is_string($value) ? strtoupper(trim($value)) : '';
        if (strlen($code) > $maxLength || !preg_match('~\A[A-Z0-9][A-Z0-9._/-]*\z~', $code)) {
            throw new InvalidArgumentException("Ingresa un {$field} válido tal como aparece en el comprobante.");
        }
        if ($card && self::looksLikeCardNumber($code)) {
            throw new InvalidArgumentException("Usa el {$field} del comprobante. Evita ingresar el número de la tarjeta.");
        }
        return $code;
    }

    private static function looksLikeCardNumber(string $code): bool
    {
        $digits = str_replace(['.', '_', '/', '-'], '', $code);
        if (!preg_match('/\A[0-9]{13,19}\z/', $digits)) return false;

        $sum = 0;
        foreach (str_split(strrev($digits)) as $position => $digit) {
            $value = (int) $digit * ($position % 2 === 0 ? 1 : 2);
            $sum += $value > 9 ? $value - 9 : $value;
        }
        return $sum % 10 === 0;
    }
}
