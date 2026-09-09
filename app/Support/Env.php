<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !is_file($path)) {
            self::$loaded = true;
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if ($name === '' || array_key_exists($name, $_ENV) || getenv($name) !== false) continue;
            $value = trim($value, "\"'");
            $_ENV[$name] = $value;
        }
        self::$loaded = true;
    }

    public static function get(string $name, ?string $default = null): ?string
    {
        if (array_key_exists($name, $_ENV)) {
            return (string) $_ENV[$name];
        }
        $value = getenv($name);
        return $value === false ? $default : (string) $value;
    }

    public static function require(array $names): void
    {
        $missing = [];
        foreach ($names as $name) {
            $value = self::get($name);
            if ($value === null || trim($value) === '') {
                $missing[] = $name;
            }
        }
        if ($missing !== []) {
            throw new RuntimeException('Faltan variables de entorno requeridas: ' . implode(', ', $missing));
        }
    }
}
