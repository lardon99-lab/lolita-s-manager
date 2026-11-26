<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;

final class CashDrawerSession
{
    public const CLOSING_TIME = '18:30';
    public const OPERATING_FUND_CENTS = 200000;

    public function __construct(private PDO $db) {}

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('America/Tegucigalpa'));
    }

    public static function closingIsAvailable(string $businessDate, DateTimeImmutable $now): bool
    {
        $today = $now->format('Y-m-d');
        return $businessDate < $today
            || ($businessDate === $today && $now->format('H:i') >= self::CLOSING_TIME);
    }

    public static function closingTimeLabel(): string
    {
        $time = self::now()->modify(self::CLOSING_TIME)->format('g:i a');
        return str_replace([' am', ' pm'], [' a. m.', ' p. m.'], $time);
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
            'SELECT * FROM jornadas_caja WHERE id_sucursal = ? ORDER BY fecha_operativa DESC, id_caja DESC LIMIT 1'
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
        if ($session === null || $session['fecha_operativa'] !== self::now()->format('Y-m-d')) {
            throw new InvalidArgumentException('Abre la caja de hoy antes de registrar efectivo. Si hay un corte anterior pendiente, finalízalo primero.');
        }
        if ($session['estado'] === 'En conteo') {
            throw new InvalidArgumentException('La caja está en conteo. Usa Volver a vender en Caja para registrar efectivo.');
        }
        if ($session['estado'] === 'Cerrada') {
            throw new InvalidArgumentException('Usa Reabrir caja para registrar más cobros o gastos hoy.');
        }
        return (int) $session['id_caja'];
    }
}
