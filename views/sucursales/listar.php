<div class="container-fluid app-page p-2 p-md-4 management-page">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
            <div class="app-page-header__copy"><h2 class="app-page-header__title">Sucursales</h2><p class="app-page-header__subtitle">Configuración de los puntos de operación.</p></div>
        </div>
        <div class="app-page-header__actions"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#branchModal"><i class="fa-solid fa-plus me-2"></i>Nueva sucursal</button></div>
    </header>
    <div class="table-responsive app-panel">
        <table class="table table-hover align-middle mb-0 management-table">
            <thead><tr><th>Sucursal</th><th>Direccion</th><th>Telefono</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($sucursales as $sucursal): ?>
                <tr>
                    <td data-label="Sucursal" class="fw-semibold"><?= e($sucursal['nombre_sucursal']) ?></td>
                    <td data-label="Direccion"><?= e($sucursal['direccion'] ?: 'Sin direccion') ?></td>
                    <td data-label="Telefono"><?= e($sucursal['telefono'] ?: 'Sin telefono') ?></td>
                    <td data-label="Estado"><span class="badge <?= $sucursal['estado'] === 'Activa' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($sucursal['estado']) ?></span></td>
                    <td data-label="Acciones" class="text-end">
                        <button class="btn btn-sm btn-outline-primary js-edit-branch" type="button" data-id="<?= (int) $sucursal['id_sucursal'] ?>"
                            data-name="<?= e($sucursal['nombre_sucursal']) ?>" data-address="<?= e($sucursal['direccion']) ?>" data-phone="<?= e($sucursal['telefono']) ?>" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-secondary js-state-branch" type="button" data-id="<?= (int) $sucursal['id_sucursal'] ?>"
                            data-state="<?= $sucursal['estado'] === 'Activa' ? 'Inactiva' : 'Activa' ?>" title="<?= $sucursal['estado'] === 'Activa' ? 'Desactivar' : 'Activar' ?>"><i class="fa-solid fa-power-off"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="branchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down"><div class="modal-content"><form id="branchForm">
        <div class="modal-header"><h5 class="modal-title">Sucursal</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
            <input type="hidden" name="id_sucursal" id="branchId">
            <div class="mb-3"><label class="form-label" for="branchName">Nombre</label><input class="form-control" id="branchName" name="nombre" maxlength="100" required></div>
            <div class="mb-3"><label class="form-label" for="branchAddress">Direccion</label><textarea class="form-control" id="branchAddress" name="direccion" maxlength="500" rows="3"></textarea></div>
            <div><label class="form-label" for="branchPhone">Telefono</label><input class="form-control" id="branchPhone" name="telefono" maxlength="20" inputmode="tel"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
    </form></div></div>
</div>
<link rel="stylesheet" href="css/views/management.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/management.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/sucursales.js"></script>
