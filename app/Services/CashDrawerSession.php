<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;

final class CashDrawerSession
{
    public const FUND_CENTS = 100000;
    public const CLOSING_TIME = '17:50';

    public function __construct(private PDO $db) {}

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('America/Tegucigalpa'));
    }

    public function lockBranch(int $branch): string
    {
        $stmt = $this->db->prepare('SELECT estado FROM sucursales WHERE id_sucursal = ? FOR UPDATE');
        $stmt->execute([$branch]);
        $state = $stmt->fetchColumn();
        if (!$state) throw new InvalidArgumentException('La sucursal no existe.');
        return $state;
    }

    public function latest(int $branch, bool $lock = false): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM jornadas_caja WHERE id_sucursal = ? ORDER BY fecha_operativa DESC LIMIT 1'
            . ($lock ? ' FOR UPDATE' : '')
        );
        $stmt->execute([$branch]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function forCashMovement(int $branch): int
    {
        if ($this->lockBranch($branch) !== 'Activa') {
            throw new InvalidArgumentException('La sucursal está inactiva.');
        }
        $session = $this->latest($branch, true);
        if (($session['estado'] ?? null) === 'En conteo') {
            throw new InvalidArgumentException('La caja está en conteo. Cancela el conteo en Caja antes de registrar efectivo.');
        }
        if (($session['estado'] ?? null) !== 'Abierta' || $session['fecha_operativa'] !== self::now()->format('Y-m-d')) {
            throw new InvalidArgumentException('Abre la jornada de hoy en Caja antes de registrar efectivo. Si hay un corte anterior pendiente, finalízalo primero.');
        }
        return (int) $session['id_caja'];
    }
}
