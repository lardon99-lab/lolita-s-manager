<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use App\Security\Auth;
use InvalidArgumentException;
use PDO;

final class CatalogService
{
    public function __construct(private PDO $db) {}

    public function data(): array
    {
        Auth::requirePermission('products.manage');
        $products = $this->db->query(
            "SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio_base, p.dias_vida_util, p.estado,
                    p.id_categoria, c.nombre_categoria,
                    GROUP_CONCAT(DISTINCT ps.id_sucursal ORDER BY ps.id_sucursal) AS branch_ids,
                    GROUP_CONCAT(DISTINCT s.nombre_sucursal ORDER BY s.nombre_sucursal SEPARATOR ', ') AS sucursales,
                    (SELECT COUNT(*) FROM producto_personalizacion_grupos ppg WHERE ppg.id_producto = p.id_producto) AS grupos_personalizacion
             FROM productos p
             JOIN categorias c ON c.id_categoria = p.id_categoria
             LEFT JOIN producto_sucursales ps ON ps.id_producto = p.id_producto AND ps.estado = 'Activo'
             LEFT JOIN sucursales s ON s.id_sucursal = ps.id_sucursal
             GROUP BY p.id_producto, c.nombre_categoria
             ORDER BY p.nombre_producto"
        )->fetchAll(PDO::FETCH_ASSOC);
        $products = array_values(array_filter($products, fn (array $product): bool => $this->canManageBranches($this->ids($product['branch_ids'] ?? ''))));
        $categories = $this->db->query('SELECT id_categoria, nombre_categoria, estado FROM categorias ORDER BY nombre_categoria')->fetchAll(PDO::FETCH_ASSOC);
        return ['productos' => $products, 'categorias' => $categories];
    }

    public function updateProduct(array $input): int
    {
        Auth::requirePermission('products.manage');
        $id = Validator::positiveInt($input['id_producto'] ?? null, 'producto');
        $this->requireProductAccess($id);
        $categoryId = Validator::positiveInt($input['id_categoria'] ?? null, 'categoria');
        $name = Validator::singleLineText($input['nombre_producto'] ?? '', 'producto', 100);
        (new ProductNameGuard($this->db))->assertAvailable($name, $id);
        $description = Validator::text($input['descripcion'] ?? '', 'descripcion', 2000, false);
        $price = Validator::money($input['precio_base'] ?? null, 'precio', 1000000);
        $shelfLife = filter_var($input['dias_vida_util'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 3650]]);
        if ($shelfLife === false) throw new InvalidArgumentException('La vida util no es valida.');
        $category = $this->db->prepare("SELECT 1 FROM categorias WHERE id_categoria = ? AND estado = 'Activo'");
        $category->execute([$categoryId]);
        if (!$category->fetchColumn()) throw new InvalidArgumentException('La categoria no existe o esta inactiva.');
        $stmt = $this->db->prepare('UPDATE productos SET id_categoria = ?, nombre_producto = ?, descripcion = ?, precio_base = ?, dias_vida_util = ? WHERE id_producto = ?');
        $stmt->execute([$categoryId, $name, $description !== '' ? $description : null, $price, $shelfLife, $id]);
        return $id;
    }

    public function setProductState(array $input): int
    {
        Auth::requirePermission('products.manage');
        $id = Validator::positiveInt($input['id_producto'] ?? null, 'producto');
        $this->requireProductAccess($id);
        $state = Validator::enum($input['estado'] ?? '', ['Activo', 'Inactivo'], 'estado');
        $this->db->prepare('UPDATE productos SET estado = ? WHERE id_producto = ?')->execute([$state, $id]);
        return $id;
    }

    public function saveCategory(array $input): int
    {
        Auth::requirePermission('categories.manage');
        $name = Validator::text($input['nombre_categoria'] ?? '', 'categoria', 50);
        if (!empty($input['id_categoria'])) {
            $id = Validator::positiveInt($input['id_categoria'], 'categoria');
            $this->db->prepare('UPDATE categorias SET nombre_categoria = ? WHERE id_categoria = ?')->execute([$name, $id]);
            return $id;
        }
        $this->db->prepare('INSERT INTO categorias (nombre_categoria) VALUES (?)')->execute([$name]);
        return (int) $this->db->lastInsertId();
    }

    public function setCategoryState(array $input): int
    {
        Auth::requirePermission('categories.manage');
        $id = Validator::positiveInt($input['id_categoria'] ?? null, 'categoria');
        $state = Validator::enum($input['estado'] ?? '', ['Activo', 'Inactivo'], 'estado');
        if ($state === 'Inactivo') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM productos WHERE id_categoria = ? AND estado = 'Activo'");
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) throw new InvalidArgumentException('No puedes desactivar una categoria con productos activos.');
        }
        $this->db->prepare('UPDATE categorias SET estado = ? WHERE id_categoria = ?')->execute([$state, $id]);
        return $id;
    }

    private function requireProductAccess(int $productId): void
    {
        $stmt = $this->db->prepare("SELECT id_sucursal FROM producto_sucursales WHERE id_producto = ? AND estado = 'Activo'");
        $stmt->execute([$productId]);
        $branches = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if ($branches === [] || !$this->canManageBranches($branches)) throw new InvalidArgumentException('No puedes modificar este producto.');
    }

    private function canManageBranches(array $branches): bool
    {
        $allowed = Auth::allowedBranches('products.manage');
        return $allowed === null || ($branches !== [] && array_diff($branches, $allowed) === []);
    }

    private function ids(string $csv): array
    {
        return $csv === '' ? [] : array_map('intval', explode(',', $csv));
    }
}
