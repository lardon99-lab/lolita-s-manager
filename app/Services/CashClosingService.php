<?php
declare(strict_types=1);

namespace App\Services;

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
            "SELECT id_caja, fecha_operativa, fecha_cierre, fecha_recepcion
             FROM jornadas_caja WHERE id_sucursal = ? AND estado = 'Cerrada' ORDER BY fecha_operativa DESC LIMIT 30"
        );
        $stmt->execute([$branch]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $branch, int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT j.*, COALESCE(a.nombre_real, a.nombre_usuario) AS nombre_apertura,
                    COALESCE(t.nombre_real, t.nombre_usuario) AS nombre_conteo,
                    COALESCE(c.nombre_real, c.nombre_usuario) AS nombre_cierre,
                    COALESCE(r.nombre_real, r.nombre_usuario) AS nombre_recepcion
             FROM jornadas_caja j JOIN usuarios a ON a.id_usuario = j.abierto_por
             LEFT JOIN usuarios t ON t.id_usuario = j.contado_por
             LEFT JOIN usuarios c ON c.id_usuario = j.cerrado_por
             LEFT JOIN usuarios r ON r.id_usuario = j.recibido_por
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

            if ($action === 'abrir') {
                if ($branchState !== 'Activa') throw new InvalidArgumentException('La sucursal está inactiva.');
                if ($session !== null && ($session['estado'] !== 'Cerrada' || $session['fecha_operativa'] === $date)) {
                    throw new InvalidArgumentException('Solo puedes abrir una jornada por día. Finaliza cualquier corte anterior.');
                }
                if (($input['confirmar_fondo'] ?? null) !== '1') {
                    throw new InvalidArgumentException('Confirma que hay L. 1,000 de fondo en la gaveta.');
                }
                $stmt = $this->db->prepare(
                    'INSERT INTO jornadas_caja (id_sucursal, fecha_operativa, abierto_por, fecha_apertura) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$branch, $date, $user, $at]);
                $id = (int) $this->db->lastInsertId();
                $message = 'Caja abierta con L. 1,000 de fondo.';
            } else {
                $id = \App\Http\Validator::positiveInt($input['id_caja'] ?? null, 'jornada de caja');
                if ($action === 'recibir') {
                    $session = $this->find($branch, $id);
                } elseif ((int) ($session['id_caja'] ?? 0) !== $id) {
                    throw new InvalidArgumentException('La jornada cambió. Recarga Caja.');
                }
                if (!$session) throw new InvalidArgumentException('La jornada no está disponible.');
                $message = match ($action) {
                    'contar' => $this->startCount($session, $branch, $user, $date, $now->format('H:i'), $at),
                    'cancelar' => $this->cancelCount($session),
                    'cerrar' => $this->close($session, $user, $at, $input),
                    'recibir' => $this->receive($session, $user, $at, $input),
                    default => throw new InvalidArgumentException('Acción de caja no válida.'),
                };
            }
            $event = $action;
            if ($action === 'cerrar' && $this->sessions->latest($branch, true)['estado'] === 'En conteo') {
                $event = 'incidencia';
            }
            (new AuditService($this->db))->record('cash.' . $event, 'jornadas_caja', $id, $branch);
            $this->db->commit();
            return $message;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function startCount(array $session, int $branch, int $user, string $date, string $time, string $at): string
    {
        if ($session['estado'] !== 'Abierta'
            || ($session['fecha_operativa'] === $date && $time < CashDrawerSession::CLOSING_TIME)) {
            throw new InvalidArgumentException('El conteo está disponible a partir de las 5:50 p. m., hora de Honduras.');
        }
        $report = (new DailyCashReport($this->db))->data([$branch], $session['fecha_operativa'], (int) $session['id_caja']);
        $branchName = $this->db->prepare('SELECT nombre_sucursal FROM sucursales WHERE id_sucursal = ?');
        $branchName->execute([$branch]);
        $report['sucursal'] = $branchName->fetchColumn();
        $report['capturado_en'] = $at;
        $report['categorias'][0] ??= ['nombre' => 'Sin categoría', 'cobrado' => 0];
        $report['fondo'] = CashDrawerSession::FUND_CENTS;
        $report['esperado'] = $report['fondo'] + array_sum(array_column($report['categorias'], 'cobrado')) - $report['gastos_centavos'];
        $this->db->prepare(
            "UPDATE jornadas_caja SET estado = 'En conteo', contado_por = ?, fecha_conteo = ?, informe = ? WHERE id_caja = ?"
        )->execute([$user, $at, $this->json($report), $session['id_caja']]);
        return 'Conteo iniciado. Consulta el PDF y registra los sobres.';
    }

    private function cancelCount(array $session): string
    {
        $this->requireCount($session);
        $this->db->prepare(
            "UPDATE jornadas_caja SET estado = 'Abierta', contado_por = NULL, fecha_conteo = NULL, informe = NULL WHERE id_caja = ?"
        )->execute([$session['id_caja']]);
        return 'Conteo cancelado. La caja vuelve a aceptar efectivo.';
    }

    private function close(array $session, int $user, string $at, array $input): string
    {
        $this->requireCount($session);
        $report = $session['informe'];
        $allowCents = self::allowsCents($report);
        $count = $this->amount($input['contado'] ?? null, 'efectivo contado', $allowCents);
        $note = $this->note($input['nota'] ?? '');
        $countedBy = (int) $session['contado_por'];
        if ($count < CashDrawerSession::FUND_CENTS) {
            if ($note === '') throw new InvalidArgumentException('Explica la incidencia del fondo antes de registrarla.');
            $report['incidencia'] = ['contado' => $count, 'nota' => $note, 'fecha' => $at, 'usuario' => $user];
            $this->db->prepare('UPDATE jornadas_caja SET informe = ? WHERE id_caja = ?')
                ->execute([$this->json($report), $session['id_caja']]);
            return 'Incidencia registrada: la gaveta no alcanza el fondo de L. 1,000. La jornada sigue en conteo.';
        }
        $envelopes = $input['sobres'] ?? [];
        if (!is_array($envelopes) || array_diff_key($envelopes, $report['categorias']) !== []) {
            throw new InvalidArgumentException('Las categorías de los sobres no coinciden con el corte.');
        }
        $distributed = 0;
        $changed = false;
        foreach ($report['categorias'] as $id => &$category) {
            $category['entregado'] = $this->amount($envelopes[$id] ?? null, 'sobre de ' . $category['nombre'], $allowCents);
            $distributed += $category['entregado'];
            $changed = $changed || $category['entregado'] !== $category['cobrado'];
        }
        unset($category);
        $withdrawal = $count - CashDrawerSession::FUND_CENTS;
        if ($distributed !== $withdrawal) {
            throw new InvalidArgumentException('La suma de los sobres debe coincidir con el efectivo contado menos el fondo de L. 1,000.');
        }
        $difference = $count - $report['esperado'];
        if (($difference !== 0 || $changed) && $note === '') {
            throw new InvalidArgumentException('Explica la diferencia del conteo o cómo aplicaste los gastos a los sobres.');
        }
        $report['corte'] = [
            'contado' => $count, 'retiro' => $withdrawal, 'diferencia' => $difference,
            'nota' => $note, 'contado_por' => $countedBy, 'cerrado_en' => $at,
        ];
        $this->db->prepare(
            "UPDATE jornadas_caja SET estado = 'Cerrada', cerrado_por = ?, fecha_cierre = ?, informe = ? WHERE id_caja = ?"
        )->execute([$user, $at, $this->json($report), $session['id_caja']]);
        return 'Corte #' . $session['id_caja'] . ' guardado. Entrega los sobres y conserva el fondo en la gaveta.';
    }

    private function receive(array $session, int $user, string $at, array $input): string
    {
        if ($session['estado'] !== 'Cerrada' || $session['recibido_por'] !== null || (int) $session['cerrado_por'] === $user) {
            throw new InvalidArgumentException('La recepción debe confirmarla otra persona autorizada y solo una vez.');
        }
        $amount = $this->amount($input['recibido'] ?? null, 'efectivo recibido', self::allowsCents($session['informe']));
        $note = $this->note($input['nota'] ?? '');
        if ($amount !== $session['informe']['corte']['retiro'] && $note === '') {
            throw new InvalidArgumentException('Indica el motivo de la diferencia en la recepción.');
        }
        $this->db->prepare(
            'UPDATE jornadas_caja SET recibido_por = ?, fecha_recepcion = ?, monto_recibido = ?, nota_recepcion = ? WHERE id_caja = ?'
        )->execute([$user, $at, number_format($amount / 100, 2, '.', ''), $note, $session['id_caja']]);
        return 'Recepción del corte registrada.';
    }

    private function requireCount(array $session): void
    {
        if ($session['estado'] !== 'En conteo') throw new InvalidArgumentException('La jornada no está en conteo.');
    }

    private function unpack(?array $session): ?array
    {
        if ($session !== null && $session['informe'] !== null) {
            $session['informe'] = json_decode($session['informe'], true, 512, JSON_THROW_ON_ERROR);
        }
        return $session;
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function allowsCents(array $report): bool
    {
        $amounts = [$report['gastos_centavos'], $report['esperado'], $report['corte']['retiro'] ?? 0];
        foreach ($report['categorias'] as $category) {
            $amounts[] = $category['cobrado'];
        }
        foreach ($amounts as $cents) {
            if ($cents % 100 !== 0) return true;
        }
        return false;
    }

    private function amount(mixed $value, string $label, bool $allowCents): int
    {
        if (!$allowCents) {
            return \App\Http\Validator::intRange($value, $label, 0, 9999999999) * 100;
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
