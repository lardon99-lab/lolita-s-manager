<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use App\Reports\DailyCashReport;
use InvalidArgumentException;
use PDO;
use Throwable;

final class CashClosingService
{
    private CashDrawerSession $sessions;

    public function __construct(private PDO $db)
    {
        $this->sessions = new CashDrawerSession($db);
    }

    public function latest(int $branch): ?array
    {
        return $this->unpack($this->sessions->latest($branch));
    }

    public function history(int $branch): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_caja, fecha_operativa, fecha_cierre
             FROM jornadas_caja WHERE id_sucursal = ? AND estado = 'Cerrada'
             ORDER BY fecha_operativa DESC, id_caja DESC LIMIT 30"
        );
        $stmt->execute([$branch]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $branch, int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT j.*, COALESCE(a.nombre_real, a.nombre_usuario) AS nombre_apertura,
                    COALESCE(c.nombre_real, c.nombre_usuario) AS nombre_cierre
             FROM jornadas_caja j JOIN usuarios a ON a.id_usuario = j.abierto_por
             LEFT JOIN usuarios c ON c.id_usuario = j.cerrado_por
             WHERE j.id_sucursal = ? AND j.id_caja = ?'
        );
        $stmt->execute([$branch, $id]);
        return $this->unpack($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
    }

    public function change(string $action, int $branch, int $user, array $input): string
    {
        $this->db->beginTransaction();
        try {
            $branchState = $this->sessions->lockBranch($branch);
            $session = $this->unpack($this->sessions->latest($branch, true));
            $now = CashDrawerSession::now();
            $date = $now->format('Y-m-d');
            $at = $now->format('Y-m-d H:i:s');
            $latestId = (int) ($session['id_caja'] ?? 0);
            $id = Validator::intRange($input['id_caja'] ?? null, 'jornada de caja', 0, PHP_INT_MAX);
            if ($latestId !== $id) throw new InvalidArgumentException('La jornada cambió. Recarga Caja.');
            $event = $action;

            if ($action === 'abrir') {
                if ($branchState !== 'Activa') throw new InvalidArgumentException('La sucursal está inactiva.');
                if ($session !== null && $session['estado'] !== 'Cerrada') {
                    throw new InvalidArgumentException('Ya hay una caja abierta o en conteo para esta sucursal.');
                }
                $reopening = ($session['fecha_operativa'] ?? null) === $date;
                $stmt = $this->db->prepare(
                    'INSERT INTO jornadas_caja (id_sucursal, fecha_operativa, abierto_por, fecha_apertura) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$branch, $date, $user, $at]);
                $id = (int) $this->db->lastInsertId();
                $event = $reopening ? 'reabrir' : 'abrir';
                $message = $reopening ? 'Caja reabierta. Ya puedes registrar más cobros en efectivo.' : 'Caja abierta.';
            } else {
                if (!$session) throw new InvalidArgumentException('La jornada no está disponible.');
                if (in_array($action, ['contar', 'cerrar'], true)
                    && !CashDrawerSession::closingIsAvailable($session['fecha_operativa'], $now)) {
                    throw new InvalidArgumentException('El corte se habilita a las ' . CashDrawerSession::closingTimeLabel() . ', hora de Honduras.');
                }
                $message = match ($action) {
                    'contar' => $this->startCount($session, $branch, $user, $at),
                    'cancelar' => $this->cancelCount($session, $date),
                    'cerrar' => $this->close($session, $user, $at, $input),
                    default => throw new InvalidArgumentException('Acción de caja no válida.'),
                };
            }
            (new AuditService($this->db))->record('cash.' . $event, 'jornadas_caja', $id, $branch);
            $this->db->commit();
            return $message;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function startCount(array $session, int $branch, int $user, string $at): string
    {
        if ($session['estado'] !== 'Abierta') {
            throw new InvalidArgumentException('La caja debe estar abierta para iniciar el conteo.');
        }
        $report = (new DailyCashReport($this->db))->data([$branch], $session['fecha_operativa'], (int) $session['id_caja']);
        $report['capturado_en'] = $at;
        $this->db->prepare(
            "UPDATE jornadas_caja SET estado = 'En conteo', contado_por = ?, fecha_conteo = ?, informe = ? WHERE id_caja = ?"
        )->execute([$user, $at, $this->json($report), $session['id_caja']]);
        return 'Conteo iniciado. Registra el efectivo de esta apertura para guardar el corte.';
    }

    private function cancelCount(array $session, string $date): string
    {
        $this->requireCount($session);
        $this->db->prepare(
            "UPDATE jornadas_caja SET estado = 'Abierta', contado_por = NULL, fecha_conteo = NULL, informe = NULL WHERE id_caja = ?"
        )->execute([$session['id_caja']]);
        return $session['fecha_operativa'] === $date
            ? 'Conteo cancelado. La caja vuelve a aceptar efectivo.'
            : 'Conteo cancelado. Finaliza el corte anterior para abrir la caja de hoy.';
    }

    private function close(array $session, int $user, string $at, array $input): string
    {
        $this->requireCount($session);
        $report = $session['informe'];
        $allowCents = self::allowsCents($report);
        $count = $this->amount($input['contado'] ?? null, 'efectivo contado', $allowCents);
        $note = $this->note($input['nota'] ?? '');
        $difference = $count - $report['esperado'];
        if ($difference !== 0 && $note === '') {
            throw new InvalidArgumentException('Explica la diferencia entre el efectivo esperado y el contado.');
        }
        $report['corte'] = [
            'contado' => $count, 'diferencia' => $difference, 'nota' => $note,
        ];
        $this->db->prepare(
            "UPDATE jornadas_caja SET estado = 'Cerrada', cerrado_por = ?, fecha_cierre = ?, informe = ? WHERE id_caja = ?"
        )->execute([$user, $at, $this->json($report), $session['id_caja']]);
        return 'Corte #' . $session['id_caja'] . ' guardado.';
    }

    private function requireCount(array $session): void
    {
        if ($session['estado'] !== 'En conteo') throw new InvalidArgumentException('La jornada no está en conteo.');
    }

    private function unpack(?array $session): ?array
    {
        if ($session !== null && $session['informe'] !== null) {
            $report = json_decode($session['informe'], true, 512, JSON_THROW_ON_ERROR);
            $report['efectivo_centavos'] ??= array_sum(array_column($report['categorias'], 'cobrado'));
            if (isset($report['fondo'])) {
                // El retiro de un corte anterior ya representa el efectivo contado de esa apertura.
                $report['esperado'] = $report['efectivo_centavos'] - $report['gastos_centavos'];
                if (isset($report['corte']['retiro'])) {
                    $report['corte']['contado'] = $report['corte']['retiro'];
                    unset($report['corte']['retiro']);
                }
                unset($report['fondo'], $report['incidencia']);
            }
            $session['informe'] = $report;
        }
        return $session;
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function allowsCents(array $report): bool
    {
        return $report['gastos_centavos'] % 100 !== 0 || $report['efectivo_centavos'] % 100 !== 0;
    }

    private function amount(mixed $value, string $label, bool $allowCents): int
    {
        if (!$allowCents) {
            return Validator::intRange($value, $label, 0, 9999999999) * 100;
        }
        $value = is_string($value) ? str_replace(',', '.', trim($value)) : '';
        if (!preg_match('/\A([0-9]{1,10})(?:\.([0-9]{1,2}))?\z/', $value, $parts)) {
            throw new InvalidArgumentException("Ingresa un importe válido para {$label}.");
        }
        return (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
    }

    private function note(mixed $value): string
    {
        if (!is_string($value) || mb_strlen($value) > 500) throw new InvalidArgumentException('La observación admite hasta 500 caracteres.');
        return trim($value);
    }
}
