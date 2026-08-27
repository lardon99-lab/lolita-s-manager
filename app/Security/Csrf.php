<?php
declare(strict_types=1);

namespace App\Security;

use App\Http\Response;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validateRequest(): void
    {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($provided) || $expected === '' || !hash_equals($expected, $provided)) {
            Response::json(['status' => 'error', 'message' => 'La solicitud expiro. Recarga la pagina e intenta nuevamente.'], 403);
        }
    }
}
