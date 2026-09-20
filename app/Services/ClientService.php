<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use InvalidArgumentException;
use PDO;

final class ClientService
{
    public function __construct(private PDO $db) {}

    public function search(string $term, int $limit = 10): array
    {
        $term = trim($term);
        if (mb_strlen($term) > 100) throw new InvalidArgumentException('La busqueda es demasiado larga.');
        $limit = max(1, min($limit, 10));
        $pattern = '%' . $term . '%';
        $stmt = $this->db->prepare(
            "SELECT id_cliente, nombre_completo, telefono
             FROM clientes
             WHERE estado = 'Activo'
               AND (nombre_completo LIKE :name_term OR telefono LIKE :phone_term)
             ORDER BY CASE WHEN nombre_completo LIKE :prefix THEN 0 ELSE 1 END,
                      nombre_completo
             LIMIT {$limit}"
        );
        $stmt->execute([
            ':name_term' => $pattern,
            ':phone_term' => $pattern,
            ':prefix' => $term . '%',
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $input): array
    {
        $name = Validator::text($input['nombre'] ?? '', 'nombre', 150);
        $phone = Validator::text($input['telefono'] ?? '', 'telefono', 20, false);
        if ($phone !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $phone)) {
            throw new InvalidArgumentException('El telefono no es valido.');
        }
        $email = trim((string) ($input['email'] ?? ''));
        if ($email !== '' && (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            throw new InvalidArgumentException('El correo no es valido.');
        }

        $stmt = $this->db->prepare('INSERT INTO clientes (nombre_completo, telefono, email) VALUES (?, ?, ?)');
        $stmt->execute([$name, $phone !== '' ? $phone : null, $email !== '' ? $email : null]);
        return [
            'id_cliente' => (int) $this->db->lastInsertId(),
            'nombre_completo' => $name,
            'telefono' => $phone,
        ];
    }
}
