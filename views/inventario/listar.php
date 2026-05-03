<?php
require_once '../app/controllers/InventarioController.php';
require_once '../app/core/Database.php';

$controller = new InventarioController();
$productos = $controller->listar();

// Solo para el Admin: Obtener lista de sucursales para el filtro
if ($_SESSION['role'] == 'Admin') {
    $db = (new Database())->getConnection();
    $sucursales = $db->query("SELECT * FROM sucursales")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Inventario de Productos</h2>
            <p class="text-muted small">Consulta y abastecimiento de existencias.</p>
        </div>

        <?php if ($_SESSION['role'] == 'Admin'): ?>
        <div class="d-flex align-items-center">
            <span class="me-2 fw-bold small">Filtrar por Sucursal:</span>
            <select class="form-select border-0 shadow-sm rounded-pill" onchange="location.href='index.php?view=inventario&sucursal_id=' + this.value">
                <option value="">-- Todas las Sucursales --</option>
                <?php foreach($sucursales as $s): ?>
                    <option value="<?= $s['id_sucursal'] ?>" <?= (isset($_GET['sucursal_id']) && $_GET['sucursal_id'] == $s['id_sucursal']) ? 'selected' : '' ?>>
                        <?= $s['nombre_sucursal'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <div class="custom-table p-4 bg-white shadow-sm" style="border-radius: 20px;">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Sucursal</th> 
                    <th>Categoría</th>
                    <th>Stock</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($productos as $p): ?>
                <tr>
                    <td><strong><?php echo $p['nombre_producto']; ?></strong></td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= $p['nombre_sucursal'] ?? 'N/A'; ?>
                        </span>
                    </td>
                    <td><?php echo $p['nombre_categoria']; ?></td>
                    <td><?php echo $p['stock_actual']; ?> unid.</td>
                    <td>L. <?php echo number_format($p['precio_base'], 2); ?></td>
                    <td>
                        <?php if($p['stock_actual'] <= 0): ?>
                            <span class="badge-stock bg-out">AGOTADO</span>
                        <?php elseif($p['stock_actual'] <= $p['stock_minimo']): ?>
                            <span class="badge-stock bg-low">STOCK BAJO</span>
                        <?php else: ?>
                            <span class="badge-stock bg-ok">EN STOCK</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAbastecer<?= $p['id_inventario'] ?>">
                            <i class="fa-solid fa-plus me-1"></i> Abastecer
                        </button>
                    </td>

                    <div class="modal fade" id="modalAbastecer<?= $p['id_inventario'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-sm modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header border-0">
                                    <h6 class="fw-bold m-0">Abastecer: <?= $p['nombre_producto'] ?></h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="../app/controllers/InventarioController.php?action=abastecer" method="POST">
                                    <div class="modal-body text-center">
                                        <input type="hidden" name="id_inventario" value="<?= $p['id_inventario'] ?>">
                                        <label class="small text-muted mb-2">Cantidad a sumar al stock:</label>
                                        <input type="number" name="cantidad" class="form-control form-control-lg text-center border-0 bg-light rounded-4" value="10" min="1" required>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="submit" class="btn btn-primary w-100 rounded-pill">Confirmar Entrada</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>