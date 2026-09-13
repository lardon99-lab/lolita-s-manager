<div class="container-fluid p-2 p-md-4 management-page">
    <header class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-address-book text-primary me-2"></i>Clientes</h2>
            <p class="text-muted mb-0">Datos de contacto y estado de los clientes.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clientModal"><i class="fa-solid fa-user-plus me-2"></i>Nuevo cliente</button>
    </header>

    <div class="table-responsive bg-white border rounded-3">
        <table class="table table-hover align-middle mb-0 management-table">
            <thead><tr><th>Nombre</th><th>Telefono</th><th>Correo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td data-label="Nombre" class="fw-semibold"><?= e($cliente['nombre_completo']) ?></td>
                    <td data-label="Telefono"><?= e($cliente['telefono'] ?: 'Sin telefono') ?></td>
                    <td data-label="Correo"><?= e($cliente['email'] ?: 'Sin correo') ?></td>
                    <td data-label="Estado"><span class="badge <?= $cliente['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($cliente['estado']) ?></span></td>
                    <td data-label="Acciones" class="text-end">
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
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down"><div class="modal-content">
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
