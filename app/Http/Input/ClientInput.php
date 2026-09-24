<?php
declare(strict_types=1);

namespace App\Http\Input;

use App\Http\Validator;

final class ClientInput
{
    public static function from(array $input): array
    {
        return [
            'name' => Validator::text($input['nombre'] ?? '', 'nombre', 150),
            'phone' => Validator::phone($input['telefono'] ?? ''),
            'email' => Validator::email($input['email'] ?? ''),
        ];
    }
}
