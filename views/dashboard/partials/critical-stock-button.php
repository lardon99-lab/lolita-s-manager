<button type="button"
        class="btn btn-sm btn-outline-primary critical-stock-action btn-abastecer-directo"
        data-id="<?= (int) ($item['id_inventario'] ?? 0) ?>"
        data-producto="<?= (int) ($item['id_producto'] ?? 0) ?>"
        data-sucursal="<?= (int) $item['id_sucursal'] ?>"
        data-nombre="<?= e($item['nombre_producto']) ?>"
        data-sucursal-nombre="<?= e($item['nombre_sucursal']) ?>"
        aria-label="Abastecer <?= e($item['nombre_producto']) ?>"
        title="Abastecer <?= e($item['nombre_producto']) ?>">
    <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Abastecer</span>
</button>
