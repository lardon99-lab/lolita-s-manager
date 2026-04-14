<?php
require_once '../app/controllers/PedidoController.php';
$pedidosCtrl = new PedidoController();
$listado = $pedidosCtrl->listarTodos();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Gestión de Pedidos Especiales</h2>
        <a href="index.php?view=pedidos-nuevo" class="btn btn-primary rounded-pill">
            <i class="fa-solid fa-plus me-2"></i>Nuevo Pedido
        </a>
    </div>

    <div class="bg-white shadow-sm rounded-4 p-4">
        <table class="table align-middle">
            <thead>
                <tr class="text-muted">
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Entrega</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($listado as $ped): ?>
                <tr>
                    <td>#<?= $ped['id_pedido'] ?></td>
                    <td>
                        <div class="fw-bold"><?= $ped['nombre_cliente'] ?></div>
                        <small class="text-muted"><?= $ped['telefono'] ?></small>
                    </td>
                    <td>
                        <i class="fa-regular fa-calendar me-1"></i>
                        <?= date('d/m/Y H:i', strtotime($ped['fecha_entrega'])) ?>
                    </td>
                    <td class="fw-bold text-success">$<?= number_format($ped['total_pedido'], 2) ?></td>
                    <td>
                        <?php 
                        $statusClass = [
                            'Pendiente' => 'bg-warning text-dark',
                            'En Proceso' => 'bg-info text-white',
                            'Listo' => 'bg-success text-white',
                            'Entregado' => 'bg-secondary text-white'
                        ];
                        ?>
                        <span class="badge rounded-pill <?= $statusClass[$ped['estado']] ?? 'bg-light' ?>">
                            <?= $ped['estado'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Gestionar
                            </button>
                            <ul class="dropdown-menu shadow border-0">
                                <li><a class="dropdown-item" href="../app/controllers/PedidoController.php?action=actualizar_estado&id=<?= $ped['id_pedido'] ?>&nuevo_estado=En Proceso">👩‍🍳 En Proceso</a></li>
                                <li><a class="dropdown-item" href="../app/controllers/PedidoController.php?action=actualizar_estado&id=<?= $ped['id_pedido'] ?>&nuevo_estado=Listo">✅ Listo para entrega</a></li>
                                <li><a class="dropdown-item" href="../app/controllers/PedidoController.php?action=actualizar_estado&id=<?= $ped['id_pedido'] ?>&nuevo_estado=Entregado">🎂 Entregado</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../app/controllers/PedidoController.php?action=actualizar_estado&id=<?= $ped['id_pedido'] ?>&nuevo_estado=Cancelado">❌ Cancelar</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>