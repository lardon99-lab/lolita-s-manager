<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fa-solid fa-users-gear me-2"></i>Gestión de Personal</h2>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
            <i class="fa-solid fa-user-plus me-2"></i>Nuevo Empleado
        </button>
    </div>

    <div class="bg-white shadow-sm rounded-4 overflow-hidden">
        <table class="table align-middle mb-0">
            <thead class="bg-light">
                <tr class="text-muted small">
                    <th class="ps-4">Usuario</th>
                    <th>Rol / Cargo</th>
                    <th>Sucursal Asignada</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-3 bg-soft-primary text-primary">
                                <?= strtoupper(substr($u['nombre_usuario'], 0, 1)) ?>
                            </div>
                            <span class="fw-bold"><?= $u['nombre_usuario'] ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if ($u['rol'] === 'Admin'): ?>
                            <span class="badge bg-soft-info text-info">
                                <i class="fa-solid fa-earth-americas me-1"></i> Acceso Global
                            </span>
                        <?php else: ?>
                            <?= $u['nombre_sucursal'] ?? '<span class="text-muted">No asignada</span>' ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['nombre_sucursal'] ?></td>
                    <td>
                        <span class="badge bg-success-soft text-success">
                            <i class="fa-solid fa-circle fst-normal small me-1"></i> <?= $u['estado_usuario'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-light rounded-pill"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn btn-sm btn-light rounded-pill text-danger"><i class="fa-solid fa-user-slash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light">
                    <h5 class="fw-bold m-0">Registrar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevoUsuario">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="small fw-bold">Nombre de Usuario</label>
                            <input type="text" name="nombre_usuario" class="form-control border-0 bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Contraseña</label>
                            <input type="password" name="password" class="form-control border-0 bg-light" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="small fw-bold">Rol</label>
                                <select name="rol" class="form-select border-0 bg-light" required>
                                    <option value="2">Empleado</option>
                                    <option value="1">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="small fw-bold">Sucursal</label>
                                <select name="id_sucursal" id="select_sucursal" class="form-select border-0 bg-light">
                                    <option value="">Sin sucursal (Acceso Global)</option>
                                    <?php foreach($sucursales as $s): ?>
                                        <option value="<?= $s['id_sucursal'] ?>"><?= $s['nombre_sucursal'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnGuardarUsuario">Guardar Empleado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userForm = document.getElementById('formNuevoUsuario');
    
    if(userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnGuardarUsuario');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

            const formData = new FormData(this);

            // Ajustamos la ruta para que siempre apunte al controlador correctamente
            fetch('../app/controllers/UsuarioController.php?action=registrarAjax', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Error en el servidor');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Si tienes SweetAlert2 instalado es mejor, si no, usamos alert
                    alert("¡Éxito! " + data.message);
                    location.reload(); 
                } else {
                    alert("Error: " + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Hubo un fallo en la conexión.");
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
    const selectRol = document.querySelector('select[name="rol"]');
    const selectSucursal = document.getElementById('select_sucursal');

    selectRol.addEventListener('change', function() {
        if (this.value === 'Admin') {
            selectSucursal.value = ""; // Limpiar selección
            selectSucursal.disabled = true; // Deshabilitar
            selectSucursal.classList.add('bg-secondary-soft'); // Estilo visual de bloqueado
        } else {
            selectSucursal.disabled = false;
            selectSucursal.classList.remove('bg-secondary-soft');
        }
    });
});
</script>
