<?php
// views/dashboard/index.php

require_once '../app/core/Database.php';      // Nos aseguramos de tener la clase de conexión
require_once '../app/controllers/PedidoController.php';
require_once '../app/models/Producto.php';

// 1. Creamos la conexión localmente para los modelos del dashboard
$database = new Database();
$db = $database->getConnection();

// 2. Instanciamos el controlador de pedidos y obtenemos métricas
$pedidosCtrl = new PedidoController();
$metricas = $pedidosCtrl->obtenerMetricasDashboard();

// 3. Instanciamos el modelo de productos para las alertas de stock
$prodModel = new Producto($db); 
$alertas = $prodModel->obtenerAlertasStock();
?>

<div class="container-fluid p-4">
    <h2 class="fw-bold mb-4">Resumen de Lolita's 🍰</h2>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Pedidos Pendientes</h6>
                    <h2 class="display-5 fw-bold"><?= $metricas['pendientes'] ?></h2>
                    <i class="fa-solid fa-clock position-absolute end-0 bottom-0 m-3 opacity-25 fa-3x"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-warning text-dark">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Entregas para Hoy</h6>
                    <h2 class="display-5 fw-bold"><?= $metricas['para_hoy'] ?></h2>
                    <i class="fa-solid fa-calendar-day position-absolute end-0 bottom-0 m-3 opacity-25 fa-3x"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-success text-white">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Ventas Completadas</h6>
                    <h2 class="display-5 fw-bold">$<?= number_format($metricas['ventas'], 2) ?></h2>
                    <i class="fa-solid fa-money-bill-trend-up position-absolute end-0 bottom-0 m-3 opacity-25 fa-3x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> 
                    Alertas de Inventario Crítico
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if(count($alertas) > 0): ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead class="table-light">
                                <tr class="small text-muted">
                                    <th>Producto</th>
                                    <th>Sucursal</th>
                                    <th>Stock Actual</th>
                                    <th>Mínimo</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($alertas as $a): ?>
                                <tr class="border-start border-4 <?= ($a['stock_actual'] == 0) ? 'border-danger' : 'border-warning' ?>">
                                    <td>
                                        <span class="fw-bold"><?= $a['nombre_producto'] ?></span>
                                        <?= ($a['stock_actual'] == 0) ? '<span class="badge bg-danger ms-2">AGOTADO</span>' : '' ?>
                                    </td>
                                    <td><i class="fa-solid fa-store me-1 small"></i> <?= $a['nombre_sucursal'] ?></td>
                                    <td class="fw-bold <?= ($a['stock_actual'] == 0) ? 'text-danger' : 'text-warning' ?>">
                                        <?= $a['stock_actual'] ?> unid.
                                    </td>
                                    <td class="text-muted"><?= $a['stock_minimo'] ?></td>
                                    <td>
                                        <a href="index.php?view=inventario" class="btn btn-sm btn-outline-secondary rounded-pill">
                                            Abastecer
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-circle-check text-success fa-3x mb-3"></i>
                        <p class="text-muted">¡Todo en orden! No hay productos con stock bajo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

    <div class="mt-5">
        <h5 class="fw-bold mb-3">Acciones Rápidas</h5>
        <div class="d-flex gap-2">
            <a href="index.php?view=pedidos-nuevo" class="btn btn-white shadow-sm rounded-pill px-4 border-0">
                <i class="fa-solid fa-plus text-primary me-2"></i> Nuevo Pedido
            </a>
            <a href="index.php?view=inventario" class="btn btn-white shadow-sm rounded-pill px-4 border-0">
                <i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Ver Inventario
            </a>
        </div>
    </div>
</div>