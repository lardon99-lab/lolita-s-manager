<?php if (($item['tipo_item'] ?? 'producto') === 'insumo'): ?>
<a class="btn btn-sm btn-outline-primary critical-stock-action"
   href="index.php?view=inventario&amp;sucursal_id=<?= (int) $item['id_sucursal'] ?>"
   aria-label="Gestionar <?= e($item['nombre_producto']) ?>">
    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span>Gestionar</span>
</a>
<?php elseif (\App\Security\Auth::canAccessBranch((int) $item['id_sucursal'], 'inventory.adjust')): ?>
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
<?php else: ?>
<span class="text-muted small">Solo lectura</span>
<?php endif; ?>
