-- Permisos de operacion para empleados y flujo de pedidos por responsabilidad.
-- Cancelado se conserva solo para registros historicos y no admite transiciones.

START TRANSACTION;

INSERT INTO permisos (codigo, descripcion) VALUES
    ('inventory.adjust', 'Registrar abastecimientos de inventario'),
    ('inventory.waste', 'Registrar mermas de inventario en sucursales asignadas'),
    ('orders.finish', 'Marcar pedidos pendientes como terminados'),
    ('orders.deliver', 'Entregar pedidos terminados y cobrar saldos'),
    ('cash.adjust', 'Registrar salidas y ajustes de caja')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso FROM roles r JOIN permisos p
    ON p.codigo IN ('inventory.waste', 'orders.finish')
WHERE r.codigo = 'ADMIN_SUCURSAL';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso FROM roles r JOIN permisos p
    ON p.codigo IN ('inventory.waste', 'cash.adjust', 'orders.deliver')
WHERE r.codigo = 'EMPLEADO';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso FROM roles r JOIN permisos p
    ON p.codigo IN ('inventory.waste', 'orders.finish', 'orders.deliver')
WHERE r.codigo = 'SUPERADMIN';

DELETE rp FROM rol_permisos rp
JOIN roles r ON r.id_rol = rp.id_rol
JOIN permisos p ON p.id_permiso = rp.id_permiso
WHERE r.codigo = 'EMPLEADO'
  AND p.codigo IN ('inventory.adjust', 'orders.update', 'orders.finish');

DELETE rp FROM rol_permisos rp
JOIN roles r ON r.id_rol = rp.id_rol
JOIN permisos p ON p.id_permiso = rp.id_permiso
WHERE r.codigo = 'ADMIN_SUCURSAL'
  AND p.codigo IN ('orders.update', 'orders.deliver');

COMMIT;

ALTER TABLE pedidos
    MODIFY COLUMN estado ENUM(
        'Pendiente', 'En Preparación', 'Listo', 'Terminado', 'Entregado', 'Cancelado'
    ) NOT NULL DEFAULT 'Pendiente';

UPDATE pedidos SET estado = 'Pendiente' WHERE estado = 'En Preparación';
UPDATE pedidos SET estado = 'Terminado' WHERE estado = 'Listo';

ALTER TABLE pedidos
    MODIFY COLUMN estado ENUM('Pendiente', 'Terminado', 'Entregado', 'Cancelado')
    NOT NULL DEFAULT 'Pendiente';

INSERT IGNORE INTO schema_migrations (migration)
VALUES ('202609231000_employee_operations_and_order_states.sql');
