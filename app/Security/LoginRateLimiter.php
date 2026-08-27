<?php
declare(strict_types=1);

namespace App\Security;

final class LoginRateLimiter
{
    private const WINDOW_SECONDS = 900;
    private const MAX_ATTEMPTS = 5;

    private static function path(string $username): string
    {
        $key = hash('sha256', strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        $directory = dirname(__DIR__, 2) . '/storage/rate-limits';
        if (!is_dir($directory)) @mkdir($directory, 0770, true);
        return $directory . '/' . $key . '.json';
    }

    public static function tooManyAttempts(string $username): bool
    {
        $attempts = self::read($username);
        return count($attempts) >= self::MAX_ATTEMPTS;
    }

    public static function recordFailure(string $username): void
    {
        $attempts = self::read($username);
        $attempts[] = time();
        file_put_contents(self::path($username), json_encode($attempts), LOCK_EX);
    }

    public static function clear(string $username): void
    {
        $path = self::path($username);
        if (is_file($path)) @unlink($path);
    }

    private static function read(string $username): array
    {
        $path = self::path($username);
        $values = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        $cutoff = time() - self::WINDOW_SECONDS;
        return array_values(array_filter(is_array($values) ? $values : [], static fn ($time) => is_int($time) && $time >= $cutoff));
    }
}
