<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\Logger;
use PDO;
use Throwable;

final class AuditService
{
    public function __construct(private PDO $db) {}

    public function record(string $action, string $entity, ?int $entityId = null, ?int $branchId = null, array $details = []): void
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO auditoria (id_usuario, accion, entidad, entidad_id, id_sucursal, detalles, direccion_ip)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null,
                $action,
                $entity,
                $entityId,
                $branchId,
                $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            ]);
        } catch (Throwable $error) {
            Logger::error($error);
        }
    }
}
