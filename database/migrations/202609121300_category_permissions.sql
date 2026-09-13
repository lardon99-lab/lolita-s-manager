INSERT INTO permisos (codigo, descripcion) VALUES
    ('categories.manage', 'Crear, editar y desactivar categorias globales');

INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
JOIN permisos p ON p.codigo = 'categories.manage'
WHERE r.codigo = 'SUPERADMIN';
