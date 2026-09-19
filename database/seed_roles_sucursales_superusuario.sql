-- Datos iniciales para una instalacion vacia de Lolita's Manager.
-- Ejecutar despues de importar test_lolitas_db_estructura_phpmyadmin_2026-09-19.sql.

SET NAMES utf8mb4;
START TRANSACTION;

-- Los IDs deben coincidir con las constantes de App\Security\Auth.
INSERT INTO roles (id_rol, codigo, nombre_rol) VALUES
    (1, 'ADMIN_SUCURSAL', 'Administrador de sucursal'),
    (2, 'EMPLEADO', 'Empleado'),
    (3, 'SUPERADMIN', 'Superusuario'),
    (4, 'PROPIETARIO', 'Propietario');

INSERT INTO sucursales
    (id_sucursal, nombre_sucursal, direccion, telefono, estado)
VALUES
    (1, 'Sucursal No1.', NULL, NULL, 'Activa'),
    (2, 'Sucursal No2.', NULL, NULL, 'Activa'),
    (3, 'Sucursal No3.', NULL, NULL, 'Activa');

INSERT INTO permisos (codigo, descripcion) VALUES
    ('inventory.view', 'Consultar inventario de sucursales asignadas'),
    ('inventory.view_all', 'Consultar inventario de todas las sucursales'),
    ('inventory.adjust', 'Registrar abastecimientos y mermas'),
    ('products.manage', 'Crear y editar productos'),
    ('orders.view', 'Consultar pedidos'),
    ('orders.create', 'Crear pedidos'),
    ('orders.update', 'Actualizar pedidos y sus estados'),
    ('sales.view', 'Consultar ventas y caja'),
    ('sales.create', 'Registrar ventas directas'),
    ('sales.void', 'Anular ventas mediante movimientos compensatorios'),
    ('reports.view', 'Consultar y exportar reportes'),
    ('users.manage', 'Administrar usuarios autorizados'),
    ('branches.manage', 'Administrar sucursales'),
    ('clients.manage', 'Crear, editar y desactivar clientes'),
    ('categories.manage', 'Crear, editar y desactivar categorias globales'),
    ('cash.adjust', 'Registrar salidas y ajustes de caja');

-- El superusuario recibe todos los permisos disponibles.
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT 3, id_permiso FROM permisos;

-- Administradores: operacion y administracion de su sucursal.
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT 1, id_permiso
FROM permisos
WHERE codigo IN (
    'inventory.view', 'inventory.adjust', 'products.manage',
    'orders.view', 'orders.create', 'orders.update',
    'sales.view', 'sales.create', 'reports.view',
    'users.manage', 'clients.manage', 'cash.adjust'
);

-- Empleados: operacion diaria en una sola sucursal.
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT 2, id_permiso
FROM permisos
WHERE codigo IN (
    'inventory.view', 'inventory.adjust',
    'orders.view', 'orders.create', 'orders.update',
    'sales.view', 'sales.create', 'reports.view'
);

-- Propietarios: acceso de consulta a las sucursales que se les asignen.
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT 4, id_permiso
FROM permisos
WHERE codigo IN (
    'inventory.view', 'orders.view', 'sales.view', 'reports.view'
);

-- Usuario: superadmin
-- La contrasena temporal se entrega fuera de este archivo.
INSERT INTO usuarios
    (id_rol, id_sucursal, nombre_usuario, password_hash, nombre_real, email, estado_usuario)
VALUES
    (
        3,
        NULL,
        'superadmin',
        '$2y$10$UtseE2gaQnm9QCbe5.F0Mev/Usdn65ymg6cCo.P1.kzo/Ra4.6lte',
        'Superusuario del sistema',
        'superadmin@lolitas.local',
        'Activo'
    );

COMMIT;

-- Comprobacion opcional:
SELECT id_rol, codigo, nombre_rol FROM roles ORDER BY id_rol;
SELECT id_sucursal, nombre_sucursal, estado FROM sucursales ORDER BY id_sucursal;
SELECT id_usuario, nombre_usuario, id_rol, estado_usuario
FROM usuarios
WHERE nombre_usuario = 'superadmin';
