<?php
declare(strict_types=1);

namespace App\Http\Input;

use App\Http\Validator;

final class BranchInput
{
    public static function from(array $input): array
    {
        $address = Validator::text($input['direccion'] ?? '', 'direccion', 500, false);
        return [
            'name' => Validator::text($input['nombre'] ?? '', 'nombre', 100),
            'address' => $address !== '' ? $address : null,
            'phone' => Validator::phone($input['telefono'] ?? ''),
        ];
    }
}
