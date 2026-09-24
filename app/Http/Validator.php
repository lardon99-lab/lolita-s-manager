<?php
declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;

final class Validator
{
    public static function positiveInt(mixed $value, string $field): int
    {
        $result = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($result === false) throw new InvalidArgumentException("El campo {$field} no es valido.");
        return $result;
    }

    public static function intRange(mixed $value, string $field, int $min, int $max): int
    {
        $result = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max],
        ]);
        if ($result === false) throw new InvalidArgumentException("El campo {$field} no es valido.");
        return $result;
    }

    public static function idList(mixed $value, string $field, bool $required = true): array
    {
        if (!is_array($value)) {
            if (!$required && ($value === null || $value === '')) return [];
            throw new InvalidArgumentException("El campo {$field} no es valido.");
        }

        $ids = [];
        foreach ($value as $item) $ids[] = self::positiveInt($item, $field);
        $ids = array_values(array_unique($ids));
        if ($required && $ids === []) throw new InvalidArgumentException("Selecciona al menos una opcion en {$field}.");
        return $ids;
    }

    public static function text(mixed $value, string $field, int $maxLength, bool $required = true): string
    {
        $text = trim((string) $value);
        if (($required && $text === '') || mb_strlen($text) > $maxLength) throw new InvalidArgumentException("El campo {$field} no es valido.");
        return $text;
    }

    public static function enum(mixed $value, array $allowed, string $field): string
    {
        $value = (string) $value;
        if (!in_array($value, $allowed, true)) throw new InvalidArgumentException("El campo {$field} no es valido.");
        return $value;
    }

    public static function money(mixed $value, string $field, float $max = 1000000): float
    {
        if (!is_numeric($value)) throw new InvalidArgumentException("El campo {$field} no es valido.");
        $amount = round((float) $value, 2);
        if ($amount < 0 || $amount > $max) throw new InvalidArgumentException("El campo {$field} no es valido.");
        return $amount;
    }

    public static function positiveMoney(mixed $value, string $field, float $max = 1000000): float
    {
        $amount = self::money($value, $field, $max);
        if ($amount <= 0) throw new InvalidArgumentException("El campo {$field} debe ser mayor a cero.");
        return $amount;
    }

    public static function email(mixed $value, string $field = 'correo', int $maxLength = 100, bool $required = false): ?string
    {
        $email = trim((string) $value);
        if ($email === '') {
            if ($required) throw new InvalidArgumentException("El campo {$field} es obligatorio.");
            return null;
        }
        if (mb_strlen($email) > $maxLength || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("El campo {$field} no es valido.");
        }
        return mb_strtolower($email);
    }

    public static function phone(mixed $value, string $field = 'telefono', bool $required = false): ?string
    {
        $phone = self::text($value, $field, 20, $required);
        if ($phone === '') return null;
        if (!preg_match('/^[0-9+() -]{7,20}$/', $phone)) {
            throw new InvalidArgumentException("El campo {$field} no es valido.");
        }
        return $phone;
    }

    public static function username(mixed $value): string
    {
        $username = self::text($value, 'usuario', 50);
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            throw new InvalidArgumentException('El usuario debe tener entre 3 y 50 caracteres y solo puede contener letras, numeros, puntos, guiones o guion bajo.');
        }
        return $username;
    }

    public static function password(mixed $value, string $field = 'contrasena'): string
    {
        $password = self::text($value, $field, 4096);
        if (mb_strlen($password) < 10) throw new InvalidArgumentException('La contrasena debe tener al menos 10 caracteres.');
        return $password;
    }

    public static function date(mixed $value, string $field): string
    {
        $value = (string) $value;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException("El campo {$field} no es valido.");
        return $value;
    }

    public static function dateRange(mixed $from, mixed $to, int $maxDays = 366): array
    {
        $from = self::date($from, 'fecha desde');
        $to = self::date($to, 'fecha hasta');
        $start = new \DateTimeImmutable($from);
        $end = new \DateTimeImmutable($to);
        if ($start > $end) throw new InvalidArgumentException('La fecha desde no puede ser posterior a la fecha hasta.');
        if ((int) $start->diff($end)->format('%a') > $maxDays) {
            throw new InvalidArgumentException("El rango de fechas no puede superar {$maxDays} dias.");
        }
        return [$from, $to];
    }

    public static function dateTimeLocal(mixed $value, string $field): string
    {
        $value = (string) $value;
        foreach (['!Y-m-d\\TH:i', '!Y-m-d\\TH:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date && $date->format(str_replace('!', '', $format)) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        throw new InvalidArgumentException("El campo {$field} no es valido.");
    }
}
