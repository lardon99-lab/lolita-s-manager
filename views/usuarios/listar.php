<?php
$totalUsuarios = isset($usuarios) ? count($usuarios) : 0;
$usuariosActivos = 0;
$usuariosInactivos = 0;
$isSuperuser = (int) ($_SESSION['id_rol'] ?? 0) === \App\Security\Auth::SUPERUSER;

if (!empty($usuarios)) {
    foreach ($usuarios as $u) {
        if (($u['estado_usuario'] ?? '') === 'Activo') {
            $usuariosActivos++;
        } else {
            $usuariosInactivos++;
        }
    }
}
?>

<div class="container-fluid p-2 p-md-4">
    <div class="page-shell rounded-4 p-3 p-md-4 mb-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <h2 class="page-title fw-bold mb-1">
                    <i class="fa-solid fa-users-gear me-2 text-primary"></i>Gestión de Personal
                </h2>
                <p class="page-subtitle mb-0">Administra usuarios, roles y accesos de forma ordenada.</p>
            </div>
            <button class="btn btn-primary px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                <i class="fa-solid fa-user-plus me-2"></i>Nuevo usuario
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="small text-muted mb-1">Total usuarios</p>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalUsuarios ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2"><i class="fa-solid fa-users"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="small text-muted mb-1">Activos</p>
                            <h3 class="fw-bold mb-0 text-success"><?= $usuariosActivos ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-3 p-2"><i class="fa-solid fa-circle-check"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="small text-muted mb-1">Inactivos</p>
                            <h3 class="fw-bold mb-0 text-warning"><?= $usuariosInactivos ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-2"><i class="fa-solid fa-circle-xmark"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4 bg-light">
            <div class="d-none d-md-block table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 720px;">
                    <thead class="bg-white">
                        <tr class="text-muted small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                            <th class="ps-4 py-3">Usuario</th>
                            <th>Rol / Cargo</th>
                            <th>Sucursal(es)</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        <?php foreach($usuarios as $u): ?>
                        <tr>
                            <td class="ps-4 py-3" data-label="Usuario">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 38px; height: 38px; font-weight: bold;">
                                        <?= strtoupper(substr($u['nombre_usuario'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['nombre_usuario']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($u['nombre_real'] ?? '') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Rol / Cargo">
                                <?php 
                                    $badgeClass = ($u['id_rol'] == 3) ? 'bg-danger' : (($u['id_rol'] == 1) ? 'bg-info text-dark' : 'bg-primary');
                                ?>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2 shadow-sm fw-normal">
                                    <?= strtoupper($u['nombre_rol']) ?>
                                </span>
                            </td>
                            <td data-label="Sucursal(es)">
                                <div class="bg-white p-2 rounded-3 border border-light text-muted text-truncate" style="max-width: 260px;">
                                    <i class="fa-solid fa-store fa-xs me-1"></i>
                                    <?php 
                                        if($u['id_rol'] == 3) echo "<strong>Acceso Total</strong>";
                                        else echo $u['id_rol'] == \App\Security\Auth::EMPLOYEE ? e($u['nombre_sucursal']) : e($u['sucursales_admin']);
                                    ?>
                                </div>
                            </td>
                            <td data-label="Estado">
                                <span class="badge <?= $u['estado_usuario'] === 'Activo' ? 'bg-success' : 'bg-secondary' ?> px-3 py-2 rounded-pill shadow-sm">
                                    <?= e($u['estado_usuario']) ?>
                                </span>
                            </td>
                            <td class="text-center" data-label="Acciones">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-light text-primary border shadow-sm" onclick="editarUsuario(<?= $u['id_usuario'] ?>)" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light text-warning border shadow-sm" onclick='abrirModalPassword(<?= (int) $u['id_usuario'] ?>, <?= json_encode($u['nombre_usuario'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Clave">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-md-none">
                <?php foreach($usuarios as $u): ?>
                <div class="user-card-card rounded-4 p-3 mb-3 shadow-sm border bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px; font-weight: bold;">
                                <?= strtoupper(substr($u['nombre_usuario'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($u['nombre_usuario']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($u['nombre_real'] ?? '') ?></small>
                            </div>
                        </div>
                        <span class="badge <?= ($u['estado_usuario'] === 'Activo' ? 'bg-success' : 'bg-secondary') ?> rounded-pill px-3 py-2">
                            <?= e($u['estado_usuario']) ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <?php 
                            $badgeClass = ($u['id_rol'] == 3) ? 'bg-danger' : (($u['id_rol'] == 1) ? 'bg-info text-dark' : 'bg-primary');
                        ?>
                        <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2 shadow-sm fw-normal">
                            <?= strtoupper($u['nombre_rol']) ?>
                        </span>
                    </div>
                    <div class="bg-light p-2 rounded-3 text-muted small mb-3">
                        <i class="fa-solid fa-store fa-xs me-1"></i>
                        <?php 
                            if($u['id_rol'] == 3) echo "<strong>Acceso Total</strong>";
                            else echo $u['id_rol'] == \App\Security\Auth::EMPLOYEE ? e($u['nombre_sucursal']) : e($u['sucursales_admin']);
                        ?>
                    </div>
                    <div class="d-flex gap-2 user-actions">
                        <button class="btn btn-sm btn-outline-primary flex-fill" onclick="editarUsuario(<?= $u['id_usuario'] ?>)">
                            <i class="fa-solid fa-pen me-1"></i>Editar
                        </button>
                        <button class="btn btn-sm btn-outline-warning flex-fill" onclick='abrirModalPassword(<?= (int) $u['id_usuario'] ?>, <?= json_encode($u['nombre_usuario'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                            <i class="fa-solid fa-key me-1"></i>Clave
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNuevoUsuario" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <form id="formNuevoUsuario" class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4">
                    <h5 class="fw-bold mb-0">Nuevo Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1">Nombre completo</label>
                        <input type="text" name="nombre_real" class="form-control bg-light border-0 py-2" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Usuario</label>
                            <input type="text" name="nombre_usuario" class="form-control bg-light border-0 py-2" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Password</label>
                            <input type="password" name="password" class="form-control bg-light border-0 py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1">Rol</label>
                        <select name="id_rol" id="select_rol" class="form-select bg-light border-0 py-2">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id_rol'] ?>"><?= e($role['nombre_rol']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="div_suc">
                        <label id="lbl_suc" class="small fw-bold text-muted text-uppercase mb-1">Sucursal</label>
                        <select name="id_sucursal[]" id="select_suc" class="form-select bg-light border-0 py-2">
                            <?php foreach($sucursales as $s): ?>
                                <option value="<?= (int) $s['id_sucursal'] ?>"><?= e($s['nombre_sucursal']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($isSuperuser): ?>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="inventory_view_all" value="1" id="inventory_view_all">
                        <label class="form-check-label" for="inventory_view_all">Ver inventario de todas las sucursales</label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalEditarUsuario" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <form id="formEditarUsuario" class="modal-content shadow-lg border-0 rounded-4">
                <input type="hidden" name="id_usuario" id="edit_id_usuario">
                <div class="modal-header border-0 bg-light rounded-top-4">
                    <h5 class="modal-title fw-bold">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1">Nombre completo</label>
                        <input type="text" name="nombre_real" id="edit_nombre_real" class="form-control bg-light border-0 py-2" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Usuario</label>
                            <input type="text" name="nombre_usuario" id="edit_nombre_usuario" class="form-control bg-light border-0 py-2" required>
                        </div>
                        <div class="col">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Estado</label>
                            <select name="estado_usuario" id="edit_estado" class="form-select bg-light border-0 py-2">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1">Rol</label>
                        <select name="id_rol" id="edit_select_rol" class="form-select bg-light border-0 py-2">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id_rol'] ?>"><?= e($role['nombre_rol']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="edit_div_suc">
                        <label id="lbl_suc_edit" class="small fw-bold text-muted text-uppercase mb-1">Sucursal</label>
                        <select name="id_sucursal[]" id="edit_select_suc" class="form-select bg-light border-0 py-2">
                            <?php foreach($sucursales as $s): ?>
                                <option value="<?= (int) $s['id_sucursal'] ?>"><?= e($s['nombre_sucursal']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($isSuperuser): ?>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="inventory_view_all" value="1" id="edit_inventory_view_all">
                        <label class="form-check-label" for="edit_inventory_view_all">Ver inventario de todas las sucursales</label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">Actualizar Datos</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalCambiarPassword" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered modal-fullscreen-sm-down">
            <form id="formCambiarPassword" class="modal-content shadow-lg border-0 rounded-4">
                <input type="hidden" name="id_usuario" id="pass_id_usuario">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title fs-6 fw-bold"><i class="fa-solid fa-key me-2"></i>Nueva Clave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="small text-muted mb-2">Usuario: <strong id="pass_nombre_usuario"></strong></p>
                    <input type="password" name="nueva_password" class="form-control text-center shadow-sm border-0 bg-light py-2" placeholder="Escriba la nueva clave" required minlength="10">
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold shadow-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/views/usuarios.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/usuarios.css') ?>">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/usuarios.js"></script>
