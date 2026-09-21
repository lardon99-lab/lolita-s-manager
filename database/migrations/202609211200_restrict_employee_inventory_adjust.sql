-- Los empleados consultan inventario, pero no abastecen ni registran mermas.
-- La revocacion incluye permisos heredados por rol y asignaciones individuales.

DELETE rp
FROM rol_permisos rp
JOIN roles r ON r.id_rol = rp.id_rol
JOIN permisos p ON p.id_permiso = rp.id_permiso
WHERE r.codigo = 'EMPLEADO'
  AND p.codigo = 'inventory.adjust';

DELETE up
FROM usuario_permisos up
JOIN usuarios u ON u.id_usuario = up.id_usuario
JOIN roles r ON r.id_rol = u.id_rol
JOIN permisos p ON p.id_permiso = up.id_permiso
WHERE r.codigo = 'EMPLEADO'
  AND p.codigo = 'inventory.adjust';
