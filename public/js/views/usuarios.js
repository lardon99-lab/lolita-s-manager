// --- Lógica de Roles y Sucursales (NUEVO Y EDITAR) ---
function toggleSucursales(idRol, idDiv, idSelect) {
    const sSuc = document.getElementById(idSelect);
    const dSuc = document.getElementById(idDiv);
    if (idRol == "3") dSuc.style.display = 'none';
    else {
        dSuc.style.display = 'block';
        sSuc.multiple = (idRol == "1");
    }
}

document.getElementById('select_rol').addEventListener('change', function() {
    toggleSucursales(this.value, 'div_suc', 'select_suc');
});

document.getElementById('edit_select_rol').addEventListener('change', function() {
    toggleSucursales(this.value, 'edit_div_suc', 'edit_select_suc');
});

// --- AJAX REGISTRAR (Recuperado) ---
document.getElementById('formNuevoUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api.php?resource=usuarios&action=registrarAjax', {
        method: 'POST',
        body: new FormData(this)
    }).then(r => r.json()).then(data => {
        if(data.status === 'success') location.reload();
        else Swal.fire('Error', data.message, 'error');
    });
});

// --- AJAX EDITAR ---
function editarUsuario(id) {
    fetch(`api.php?resource=usuarios&action=obtenerAjax&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                const u = data.usuario;
                document.getElementById('edit_id_usuario').value = u.id_usuario;
                document.getElementById('edit_nombre_real').value = u.nombre_real;
                document.getElementById('edit_nombre_usuario').value = u.nombre_usuario;
                document.getElementById('edit_estado').value = u.estado_usuario;
                document.getElementById('edit_select_rol').value = u.id_rol;
                
                toggleSucursales(u.id_rol, 'edit_div_suc', 'edit_select_suc');

                const selectSuc = document.getElementById('edit_select_suc');
                Array.from(selectSuc.options).forEach(opt => {
                    opt.selected = data.sucursales.includes(parseInt(opt.value));
                });

                new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
            }
        });
}

document.getElementById('formEditarUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api.php?resource=usuarios&action=editarAjax', {
        method: 'POST',
        body: new FormData(this)
    }).then(r => r.json()).then(data => {
        if(data.status === 'success') location.reload();
        else Swal.fire('Error', data.message, 'error');
    });
});

// --- AJAX PASSWORD ---
function abrirModalPassword(id, nombre) {
    document.getElementById('pass_id_usuario').value = id;
    document.getElementById('pass_nombre_usuario').innerText = nombre;
    document.getElementById('formCambiarPassword').reset();
    new bootstrap.Modal(document.getElementById('modalCambiarPassword')).show();
}

document.getElementById('formCambiarPassword').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api.php?resource=usuarios&action=cambiarPasswordAjax', {
        method: 'POST',
        body: new FormData(this)
    }).then(r => r.json()).then(data => {
        if(data.status === 'success') {
            Swal.fire('Éxito', 'Contraseña actualizada', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
});
