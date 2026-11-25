<?php
declare(strict_types=1);

namespace App\Services;

use App\Security\Auth;
use PDO;

final class SaleReceiptService
{
    public function __construct(private PDO $db) {}

    public function find(int $saleId): ?array
    {
        $header = $this->db->prepare(
            'SELECT v.id_venta, v.id_sucursal, s.nombre_sucursal, v.fecha_venta, v.metodo_pago, v.total, v.id_usuario
             FROM ventas_directas v JOIN sucursales s ON s.id_sucursal = v.id_sucursal
             WHERE v.id_venta = ?'
        );
        $header->execute([$saleId]);
        $sale = $header->fetch();
        if (!$sale) return null;

        $branchId = (int) $sale['id_sucursal'];
        $ownSale = (int) $sale['id_usuario'] === (int) ($_SESSION['id_usuario'] ?? 0);
        if (!Auth::canAccessBranch($branchId, 'reports.view')
            && !($ownSale && Auth::canAccessBranch($branchId, 'sales.create'))) {
            return null;
        }

        $details = $this->db->prepare(
            'SELECT vi.id_item, p.nombre_producto, vi.cantidad, vi.precio_unitario, vi.subtotal,
                    o.grupo_nombre, o.opcion_nombre, o.recargo_unitario
             FROM venta_items vi
             JOIN productos p ON p.id_producto = vi.id_producto
             LEFT JOIN venta_item_opciones o ON o.id_item = vi.id_item
             WHERE vi.id_venta = ?
             ORDER BY vi.id_item, o.id_item_opcion'
        );
        $details->execute([$saleId]);
        $items = [];
        foreach ($details as $row) {
            $id = (int) $row['id_item'];
            if (!isset($items[$id])) {
                $items[$id] = [
                    'nombre' => $row['nombre_producto'],
                    'cantidad' => (int) $row['cantidad'],
                    'precio' => (string) $row['precio_unitario'],
                    'subtotal' => (string) $row['subtotal'],
                    'opciones' => [],
                ];
            }
            if ($row['opcion_nombre'] !== null) {
                $items[$id]['opciones'][] = [
                    'grupo' => $row['grupo_nombre'],
                    'nombre' => $row['opcion_nombre'],
                    'recargo' => (string) $row['recargo_unitario'],
                ];
            }
        }

        return [
            'id' => $saleId,
            'sucursal_id' => $branchId,
            'sucursal' => $sale['nombre_sucursal'],
            'fecha' => $sale['fecha_venta'] === null ? null : date('d/m/Y h:i a', strtotime($sale['fecha_venta'])),
            'metodo_pago' => $sale['metodo_pago'],
            'total' => (string) $sale['total'],
            'productos' => array_values($items),
        ];
    }
}
