<?php
declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function money(int|float|string|null $value, bool $group = true): string
    {
        $amount = round((float) $value, 2);
        return number_format($amount, floor($amount) === $amount ? 0 : 2, '.', $group ? ',' : '');
    }
}
