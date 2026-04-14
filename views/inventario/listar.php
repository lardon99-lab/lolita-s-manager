<?php
require_once '../app/controllers/InventarioController.php';
$controller = new InventarioController();
$productos = $controller->listar();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Inventario: <?php echo $_SESSION['role'] == 'Admin' ? 'Global' : 'Mi Sucursal'; ?></h2>
    </div>

    <div class="custom-table p-4 bg-white shadow-sm" style="border-radius: 20px;">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                    <th>Precio</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($productos as $p): ?>
                <tr>
                    <td><strong><?php echo $p['nombre_producto']; ?></strong></td>
                    <td><?php echo $p['nombre_categoria']; ?></td>
                    <td><?php echo $p['stock_actual']; ?> unid.</td>
                    <td>$<?php echo number_format($p['precio_base'], 2); ?></td>
                    <td>
                        <?php if($p['stock_actual'] <= 0): ?>
                            <span class="badge-stock bg-out">AGOTADO</span>
                        <?php elseif($p['stock_actual'] <= $p['stock_minimo']): ?>
                            <span class="badge-stock bg-low">STOCK BAJO</span>
                        <?php else: ?>
                            <span class="badge-stock bg-ok">EN STOCK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>