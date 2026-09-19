<div class="container-fluid app-page p-2 p-md-4 management-page">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-address-book"></i></span>
            <div class="app-page-header__copy">
            <h2 class="app-page-header__title">Clientes</h2>
            <p class="app-page-header__subtitle">Datos de contacto y estado de los clientes.</p>
            </div>
        </div>
        <div class="app-page-header__actions"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clientModal"><i class="fa-solid fa-user-plus me-2"></i>Nuevo cliente</button></div>
    </header>

    <div class="table-responsive app-panel">
        <table class="table table-hover align-middle mb-0 management-table">
            <thead><tr><th>Nombre</th><th>Telefono</th><th>Correo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td data-label="Nombre" class="fw-semibold"><?= e($cliente['nombre_completo']) ?></td>
                    <td data-label="Telefono"><?= e($cliente['telefono'] ?: 'Sin telefono') ?></td>
                    <td data-label="Correo"><?= e($cliente['email'] ?: 'Sin correo') ?></td>
                    <td data-label="Estado"><span class="badge <?= $cliente['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($cliente['estado']) ?></span></td>
                    <td data-label="Acciones" class="text-end"><div class="management-actions">
                        <button class="btn btn-sm btn-outline-primary js-edit-client" type="button"
                            data-id="<?= (int) $cliente['id_cliente'] ?>" data-name="<?= e($cliente['nombre_completo']) ?>"
                            data-phone="<?= e($cliente['telefono']) ?>" data-email="<?= e($cliente['email']) ?>" title="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary js-state-client" type="button"
                            data-id="<?= (int) $cliente['id_cliente'] ?>" data-state="<?= $cliente['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>"
                            title="<?= $cliente['estado'] === 'Activo' ? 'Desactivar' : 'Activar' ?>">
                            <i class="fa-solid <?= $cliente['estado'] === 'Activo' ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                        </button>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down"><div class="modal-content">
        <form id="clientForm">
            <div class="modal-header"><h5 class="modal-title">Cliente</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id_cliente" id="clientId">
                <div class="mb-3"><label class="form-label" for="clientName">Nombre completo</label><input class="form-control" id="clientName" name="nombre" maxlength="150" required></div>
                <div class="mb-3"><label class="form-label" for="clientPhone">Telefono</label><input class="form-control" id="clientPhone" name="telefono" maxlength="20" inputmode="tel"></div>
                <div><label class="form-label" for="clientEmail">Correo</label><input class="form-control" id="clientEmail" name="email" maxlength="100" type="email"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
        </form>
    </div></div>
</div>
<link rel="stylesheet" href="css/views/management.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/management.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/clientes.js"></script>
