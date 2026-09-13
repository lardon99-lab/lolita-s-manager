INSERT INTO permisos (codigo, descripcion) VALUES
    ('cash.adjust', 'Registrar salidas y ajustes de caja');

INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
JOIN permisos p ON p.codigo = 'cash.adjust'
WHERE r.codigo IN ('SUPERADMIN', 'ADMIN_SUCURSAL');
