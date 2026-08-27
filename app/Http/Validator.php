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

    public static function date(mixed $value, string $field): string
    {
        $value = (string) $value;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException("El campo {$field} no es valido.");
        return $value;
    }
}
