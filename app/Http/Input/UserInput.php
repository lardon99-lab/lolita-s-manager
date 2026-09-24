<?php
declare(strict_types=1);

namespace App\Http\Input;

use App\Http\Validator;

final class UserInput
{
    public static function create(array $input): array
    {
        return [
            'username' => Validator::username($input['nombre_usuario'] ?? ''),
            'real_name' => Validator::text($input['nombre_real'] ?? '', 'nombre real', 100),
            'password' => Validator::password($input['password'] ?? ''),
            'role_id' => Validator::positiveInt($input['id_rol'] ?? null, 'rol'),
            'branch_ids' => Validator::idList($input['id_sucursal'] ?? [], 'sucursales', false),
        ];
    }

    public static function update(array $input): array
    {
        return [
            'id' => Validator::positiveInt($input['id_usuario'] ?? null, 'usuario'),
            'username' => Validator::username($input['nombre_usuario'] ?? ''),
            'real_name' => Validator::text($input['nombre_real'] ?? '', 'nombre real', 100),
            'role_id' => Validator::positiveInt($input['id_rol'] ?? null, 'rol'),
            'branch_ids' => Validator::idList($input['id_sucursal'] ?? [], 'sucursales', false),
            'status' => Validator::enum($input['estado_usuario'] ?? '', ['Activo', 'Inactivo'], 'estado'),
        ];
    }
}
