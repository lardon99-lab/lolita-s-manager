<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use InvalidArgumentException;
use PDO;
use Throwable;

final class SupplyCatalogService
{
    public function __construct(private PDO $db) {}

    public function create(array $input): array
    {
        $name = Validator::singleLineText($input['nombre'] ?? '', 'nombre del vaso', 100);
        $usage = Validator::enum($input['tipo_uso'] ?? '', ['bebida', 'batido'], 'tipo de uso');
        $minimum = Validator::intRange($input['stock_minimo'] ?? 10, 'stock minimo', 0, 100000);

        $this->db->beginTransaction();
        try {
            $code = $this->availableCode($name);
            $insert = $this->db->prepare(
                "INSERT INTO insumos (codigo, nombre, unidad_medida, tipo_uso, stock_minimo, estado)
                 VALUES (?, ?, 'unidad', ?, ?, 'Activo')"
            );
            $insert->execute([$code, $name, $usage, $minimum]);
            $supplyId = (int) $this->db->lastInsertId();

            $stock = $this->db->prepare(
                "INSERT INTO inventario_insumos (id_sucursal, id_insumo, stock_actual, stock_minimo)
                 SELECT id_sucursal, ?, 0, ? FROM sucursales WHERE estado = 'Activa'"
            );
            $stock->execute([$supplyId, $minimum]);

            (new AuditService($this->db))->record('supply.created', 'insumos', $supplyId, null, [
                'nombre' => $name,
                'tipo_uso' => $usage,
            ]);
            $this->db->commit();

            return ['id_insumo' => $supplyId, 'codigo' => $code, 'nombre' => $name, 'tipo_uso' => $usage, 'estado' => 'Activo'];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function update(int $supplyId, array $input): array
    {
        $name = Validator::singleLineText($input['nombre'] ?? '', 'nombre del vaso', 100);
        $usage = Validator::enum($input['tipo_uso'] ?? '', ['bebida', 'batido'], 'tipo de uso');
        $minimum = Validator::intRange($input['stock_minimo'] ?? 10, 'stock minimo', 0, 100000);

        $this->db->beginTransaction();
        try {
            $current = $this->lock($supplyId);
            if ($current['tipo_uso'] !== $usage && $this->hasIncompatibleProducts($supplyId, $usage)) {
                throw new InvalidArgumentException('No puedes cambiar el tipo de uso porque el vaso esta vinculado a productos incompatibles.');
            }
            $this->db->prepare('UPDATE insumos SET nombre = ?, tipo_uso = ?, stock_minimo = ? WHERE id_insumo = ?')
                ->execute([$name, $usage, $minimum, $supplyId]);
            $this->db->prepare('UPDATE inventario_insumos SET stock_minimo = ? WHERE id_insumo = ?')
                ->execute([$minimum, $supplyId]);
            (new AuditService($this->db))->record('supply.updated', 'insumos', $supplyId, null, [
                'nombre' => $name,
                'tipo_uso' => $usage,
                'stock_minimo' => $minimum,
            ]);
            $this->db->commit();
            return [
                'id_insumo' => $supplyId,
                'codigo' => $current['codigo'],
                'nombre' => $name,
                'tipo_uso' => $usage,
                'stock_minimo' => $minimum,
                'estado' => $current['estado'],
            ];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function setStatus(int $supplyId, string $status): void
    {
        $status = Validator::enum($status, ['Activo', 'Inactivo'], 'estado');
        $this->db->beginTransaction();
        try {
            $this->lock($supplyId);
            if ($status === 'Inactivo' && $this->hasActiveProducts($supplyId)) {
                throw new InvalidArgumentException('No puedes desactivar un vaso vinculado a productos activos.');
            }
            $this->db->prepare('UPDATE insumos SET estado = ? WHERE id_insumo = ?')->execute([$status, $supplyId]);
            (new AuditService($this->db))->record('supply.status_changed', 'insumos', $supplyId, null, ['estado' => $status]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function delete(int $supplyId): void
    {
        $this->db->beginTransaction();
        try {
            $this->lock($supplyId);
            $linked = (int) $this->scalar('SELECT COUNT(*) FROM producto_insumos WHERE id_insumo = ?', $supplyId);
            $stock = (float) $this->scalar('SELECT COALESCE(SUM(stock_actual), 0) FROM inventario_insumos WHERE id_insumo = ?', $supplyId);
            $movements = (int) $this->scalar(
                'SELECT COUNT(*) FROM movimientos_insumos mi JOIN inventario_insumos ii ON ii.id_inventario_insumo = mi.id_inventario_insumo WHERE ii.id_insumo = ?',
                $supplyId
            );
            if ($linked > 0 || $stock > 0 || $movements > 0) {
                throw new InvalidArgumentException('Este vaso tiene productos, existencias o movimientos asociados. Desactivalo en lugar de eliminarlo.');
            }
            $this->db->prepare('DELETE FROM insumos WHERE id_insumo = ?')->execute([$supplyId]);
            (new AuditService($this->db))->record('supply.deleted', 'insumos', $supplyId);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function lock(int $supplyId): array
    {
        $suffix = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $stmt = $this->db->prepare('SELECT id_insumo, codigo, nombre, tipo_uso, estado FROM insumos WHERE id_insumo = ?' . $suffix);
        $stmt->execute([$supplyId]);
        $supply = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$supply) throw new InvalidArgumentException('El vaso seleccionado no existe.');
        return $supply;
    }

    private function hasActiveProducts(int $supplyId): bool
    {
        return (int) $this->scalar(
            "SELECT COUNT(*) FROM producto_insumos pi JOIN productos p ON p.id_producto = pi.id_producto
             WHERE pi.id_insumo = ? AND p.estado = 'Activo'",
            $supplyId
        ) > 0;
    }

    private function hasIncompatibleProducts(int $supplyId, string $usage): bool
    {
        return (int) $this->scalar(
            'SELECT COUNT(*) FROM producto_insumos pi JOIN productos p ON p.id_producto = pi.id_producto
             WHERE pi.id_insumo = ? AND p.tipo_producto <> ?',
            $supplyId,
            $usage
        ) > 0;
    }

    private function scalar(string $sql, mixed ...$parameters): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);
        return $stmt->fetchColumn();
    }

    private function availableCode(string $name): string
    {
        $normalized = strtr(mb_strtolower($name), [
            'a' => 'a', 'e' => 'e', 'i' => 'i', 'o' => 'o', 'u' => 'u',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '_', $normalized), '_');
        $slug = substr($slug !== '' ? $slug : 'vaso', 0, 40);
        $base = 'cup_' . $slug;
        $candidate = $base;
        for ($suffix = 2; $this->codeExists($candidate); $suffix++) {
            $candidate = substr($base, 0, 46) . '_' . $suffix;
        }
        return $candidate;
    }

    private function codeExists(string $code): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM insumos WHERE codigo = ?');
        $stmt->execute([$code]);
        return (bool) $stmt->fetchColumn();
    }
}
