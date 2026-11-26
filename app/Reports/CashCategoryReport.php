<?php
declare(strict_types=1);

namespace App\Reports;

use PDO;

final class CashCategoryReport
{
    public function __construct(private PDO $db) {}

    public function totals(?array $branches, string $date, ?int $session): array
    {
        $categories = [];
        foreach ([
            ["ventas_directas v", "v.id_venta", "v.total", "v.id_sucursal",
                "v.fecha_venta", "v.id_caja", "v.metodo_pago = 'Efectivo'",
                "venta_items d", "d.id_venta", "v.id_venta"],
            ["pagos_pedido v JOIN pedidos p ON p.id_pedido = v.id_pedido", "v.id_pago", "v.monto", "p.id_sucursal",
                "v.fecha_registro", "v.id_caja", "v.metodo_pago = 'Efectivo' AND v.estado = 'Aplicado'",
                "pedido_detalles d", "d.id_pedido", "p.id_pedido"],
        ] as [$source, $key, $amount, $branch, $timestamp, $cashId, $conditions, $detail, $link, $parent]) {
            $scope = $session !== null ? "$cashId = ?" : "DATE($timestamp) = ?";
            $params = [$session ?? $date];
            if ($branches !== null) {
                if ($branches === []) continue;
                $scope .= " AND $branch IN (" . implode(',', array_fill(0, count($branches), '?')) . ')';
                array_push($params, ...$branches);
            }
            $stmt = $this->db->prepare(
                "SELECT $key AS operacion, $amount AS cobrado, COALESCE(c.id_categoria, 0) AS categoria,
                        COALESCE(c.nombre_categoria, 'Sin categoría') AS nombre, COALESCE(SUM(d.subtotal), 0) AS peso
                 FROM $source LEFT JOIN $detail ON $link = $parent
                 LEFT JOIN productos pr ON pr.id_producto = d.id_producto
                 LEFT JOIN categorias c ON c.id_categoria = pr.id_categoria
                 WHERE $conditions AND $scope
                 GROUP BY $key, $amount, c.id_categoria, c.nombre_categoria
                 ORDER BY $key, categoria"
            );
            $stmt->execute($params);
            $group = [];
            $operation = null;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($operation !== null && $operation !== $row['operacion']) {
                    $this->allocate($group, $categories);
                    $group = [];
                }
                $operation = $row['operacion'];
                $group[] = $row;
            }
            if ($group !== []) $this->allocate($group, $categories);
        }
        uasort($categories, static fn (array $a, array $b): int => strcasecmp($a['nombre'], $b['nombre']));
        return $categories;
    }

    private function allocate(array $lines, array &$categories): void
    {
        $amount = self::cents($lines[0]['cobrado']);
        $weights = array_map(static fn (array $line): int => max(0, self::cents($line['peso'])), $lines);
        $total = array_sum($weights);
        if ($total === 0) {
            $categories[0] ??= ['nombre' => 'Sin categoría', 'cobrado' => 0];
            $categories[0]['cobrado'] += $amount;
            return;
        }

        $unit = $amount % 100 === 0 ? 100 : 1;
        $units = intdiv($amount, $unit);
        $accumulated = 0;
        $allocated = 0;
        foreach ($lines as $index => $line) {
            $accumulated += $weights[$index];
            $target = $accumulated === $total ? $amount : (int) round($units * ($accumulated / $total)) * $unit;
            $id = (int) $line['categoria'];
            $categories[$id] ??= ['nombre' => $line['nombre'], 'cobrado' => 0];
            $categories[$id]['cobrado'] += $target - $allocated;
            $allocated = $target;
        }
    }

    public static function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
