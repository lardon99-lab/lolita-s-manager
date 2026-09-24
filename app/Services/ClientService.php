<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use App\Http\Input\ClientInput;
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
        $data = ClientInput::from($input);

        $stmt = $this->db->prepare('INSERT INTO clientes (nombre_completo, telefono, email) VALUES (?, ?, ?)');
        $stmt->execute([$data['name'], $data['phone'], $data['email']]);
        return [
            'id_cliente' => (int) $this->db->lastInsertId(),
            'nombre_completo' => $data['name'],
            'telefono' => $data['phone'],
        ];
    }
}
