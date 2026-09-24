<?php
declare(strict_types=1);

namespace App\Services;

use App\Security\Auth;
use PDO;

final class InventarioPageService
{
    public function __construct(private PDO $db) {}

    public function data(?int $requestedBranch): array
    {
        require_once dirname(__DIR__) . '/models/Producto.php';
        $role = (int) ($_SESSION['id_rol'] ?? 0);
        $userBranch = (int) ($_SESSION['id_sucursal'] ?? 0);
        Auth::requirePermission('inventory.view');
        $allowed = Auth::allowedBranches('inventory.view');
        $branches = $this->branches($allowed);

        $branchId = $role === Auth::EMPLOYEE ? $userBranch : $requestedBranch;
        if ($branchId && !Auth::canAccessBranch($branchId, 'inventory.view')) $branchId = null;
        if (!$branchId && count($branches) === 1) $branchId = (int) $branches[0]['id_sucursal'];
        $products = $branchId ? (new \Producto($this->db))->obtenerPorSucursal($branchId) : [];
        $supplies = [];
        if ($branchId) {
            $supplyStmt = $this->db->prepare(
                "SELECT ii.id_inventario_insumo, ii.id_sucursal, ii.id_insumo, ii.stock_actual, ii.stock_minimo,
                        i.codigo, i.nombre, i.unidad_medida, s.nombre_sucursal
                 FROM inventario_insumos ii
                 JOIN insumos i ON i.id_insumo = ii.id_insumo
                 JOIN sucursales s ON s.id_sucursal = ii.id_sucursal
                 WHERE ii.id_sucursal = ? AND i.estado = 'Activo'
                 ORDER BY i.nombre"
            );
            $supplyStmt->execute([$branchId]);
            $supplies = $supplyStmt->fetchAll();
        }
        $supplyCatalog = $this->db->query("SELECT id_insumo, codigo, nombre FROM insumos WHERE estado = 'Activo' ORDER BY nombre")->fetchAll();

        $categories = $this->db->query('SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria')->fetchAll();
        $branchName = 'No asignada';
        if ($userBranch > 0) {
            $stmt = $this->db->prepare('SELECT nombre_sucursal FROM sucursales WHERE id_sucursal = ?');
            $stmt->execute([$userBranch]);
            $branchName = (string) ($stmt->fetchColumn() ?: $branchName);
        }

        return [
            'categorias' => $categories,
            'sucursales_modal' => $branches,
            'sucursales' => $branches,
            'id_rol' => $role,
            'id_sucursal_user' => $userBranch ?: null,
            'nombre_sucursal_user' => $branchName,
            'id_sucursal_filtro' => $branchId,
            'productos' => $products,
            'insumos' => $supplies,
            'insumos_catalogo' => $supplyCatalog,
        ];
    }

    private function branches(?array $allowed): array
    {
        if ($allowed === null) return $this->db->query('SELECT id_sucursal, nombre_sucursal FROM sucursales ORDER BY nombre_sucursal')->fetchAll();
        if ($allowed === []) return [];
        $holders = implode(',', array_fill(0, count($allowed), '?'));
        $stmt = $this->db->prepare("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE id_sucursal IN ($holders) ORDER BY nombre_sucursal");
        $stmt->execute($allowed);
        return $stmt->fetchAll();
    }
}
