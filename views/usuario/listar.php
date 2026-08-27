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
                        <span class="badge <?= $u['rol'] == 'Admin' ? 'bg-dark' : 'bg-light text-dark border' ?>">
                            <?= $u['rol'] ?>
                        </span>
                    </td>
                    <td><?= $u['nombre_sucursal'] ?></td>
                    <td>
                        <span class="badge bg-success-soft text-success">
                            <i class="fa-solid fa-circle fst-normal small me-1"></i> <?= $u['estado'] ?>
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
</div>