INSERT INTO permisos (codigo, descripcion) VALUES
    ('clients.manage', 'Crear, editar y desactivar clientes');

INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
JOIN permisos p ON p.codigo = 'clients.manage'
WHERE r.codigo IN ('SUPERADMIN', 'ADMIN_SUCURSAL');
