-- Access control by capability and branch.
-- Apply once after backing up the database.

ALTER TABLE roles
    ADD COLUMN codigo VARCHAR(50) NULL AFTER id_rol;

UPDATE roles SET codigo = 'ADMIN_SUCURSAL' WHERE id_rol = 1;
UPDATE roles SET codigo = 'EMPLEADO' WHERE id_rol = 2;
UPDATE roles SET codigo = 'SUPERADMIN' WHERE id_rol = 3;

ALTER TABLE roles
    MODIFY codigo VARCHAR(50) NOT NULL,
    ADD CONSTRAINT uq_roles_codigo UNIQUE (codigo),
    ADD CONSTRAINT uq_roles_nombre UNIQUE (nombre_rol);

INSERT INTO roles (codigo, nombre_rol)
VALUES ('PROPIETARIO', 'Propietario');

CREATE TABLE permisos (
    id_permiso INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(80) NOT NULL,
    descripcion VARCHAR(180) NOT NULL,
    PRIMARY KEY (id_permiso),
    UNIQUE KEY uq_permisos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol_permisos (
    id_rol INT NOT NULL,
    id_permiso INT NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT fk_rol_permisos_rol FOREIGN KEY (id_rol) REFERENCES roles (id_rol),
    CONSTRAINT fk_rol_permisos_permiso FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_permisos (
    id_usuario INT NOT NULL,
    id_permiso INT NOT NULL,
    PRIMARY KEY (id_usuario, id_permiso),
    CONSTRAINT fk_usuario_permisos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_permisos_permiso FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (codigo, descripcion) VALUES
    ('inventory.view', 'Consultar inventario de sucursales asignadas'),
    ('inventory.view_all', 'Consultar inventario de todas las sucursales'),
    ('inventory.adjust', 'Registrar abastecimientos y mermas'),
    ('products.manage', 'Crear y editar productos y categorias'),
    ('orders.view', 'Consultar pedidos'),
    ('orders.create', 'Crear pedidos'),
    ('orders.update', 'Actualizar pedidos y sus estados'),
    ('sales.view', 'Consultar ventas y caja'),
    ('sales.create', 'Registrar ventas directas'),
    ('sales.void', 'Anular ventas mediante movimientos compensatorios'),
    ('reports.view', 'Consultar y exportar reportes'),
    ('users.manage', 'Administrar usuarios autorizados'),
    ('branches.manage', 'Administrar sucursales');

-- Superadmin receives every capability.
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r CROSS JOIN permisos p
WHERE r.codigo = 'SUPERADMIN';

-- Branch administrators manage operations in their assigned branches.
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r JOIN permisos p ON p.codigo IN (
    'inventory.view', 'inventory.adjust', 'products.manage',
    'orders.view', 'orders.create', 'orders.update',
    'sales.view', 'sales.create', 'reports.view', 'users.manage'
)
WHERE r.codigo = 'ADMIN_SUCURSAL';

-- Employees perform daily operations in one assigned branch.
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r JOIN permisos p ON p.codigo IN (
    'inventory.view', 'inventory.adjust',
    'orders.view', 'orders.create', 'orders.update',
    'sales.view', 'sales.create', 'reports.view'
)
WHERE r.codigo = 'EMPLEADO';

-- Owners have read-only access to their assigned branches by default.
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r JOIN permisos p ON p.codigo IN (
    'inventory.view', 'orders.view', 'sales.view', 'reports.view'
)
WHERE r.codigo = 'PROPIETARIO';

-- Normalize existing employee assignments into the pivot table.
INSERT IGNORE INTO usuario_sucursales (id_usuario, id_sucursal)
SELECT id_usuario, id_sucursal
FROM usuarios
WHERE id_sucursal IS NOT NULL;

ALTER TABLE clientes
    ADD COLUMN estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    ADD INDEX idx_clientes_estado_nombre (estado, nombre_completo);

CREATE TEMPORARY TABLE categorias_canonicas AS
SELECT nombre_categoria, MIN(id_categoria) AS id_categoria
FROM categorias
GROUP BY nombre_categoria;

UPDATE productos p
JOIN categorias c ON c.id_categoria = p.id_categoria
JOIN categorias_canonicas cc ON cc.nombre_categoria = c.nombre_categoria
SET p.id_categoria = cc.id_categoria
WHERE p.id_categoria <> cc.id_categoria;

DELETE c
FROM categorias c
JOIN categorias_canonicas cc ON cc.nombre_categoria = c.nombre_categoria
WHERE c.id_categoria <> cc.id_categoria;

DROP TEMPORARY TABLE categorias_canonicas;

ALTER TABLE categorias
    ADD COLUMN estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    ADD CONSTRAINT uq_categorias_nombre UNIQUE (nombre_categoria);

ALTER TABLE productos
    ADD COLUMN estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    ADD INDEX idx_productos_estado_nombre (estado, nombre_producto);

ALTER TABLE sucursales
    ADD COLUMN estado ENUM('Activa', 'Inactiva') NOT NULL DEFAULT 'Activa',
    ADD CONSTRAINT uq_sucursales_nombre UNIQUE (nombre_sucursal);

ALTER TABLE ventas_directas
    ADD COLUMN metodo_pago ENUM('Efectivo', 'Transferencia', 'Tarjeta', 'Otro') NOT NULL DEFAULT 'Efectivo' AFTER total,
    ADD INDEX idx_ventas_sucursal_fecha (id_sucursal, fecha_venta);

ALTER TABLE pedidos
    ADD INDEX idx_pedidos_sucursal_estado_entrega (id_sucursal, estado, fecha_entrega),
    ADD INDEX idx_pedidos_sucursal_registro (id_sucursal, fecha_registro);

ALTER TABLE inventario
    ADD INDEX idx_inventario_sucursal_caducidad (id_sucursal, fecha_caducidad);

ALTER TABLE mermas_caja
    ADD INDEX idx_mermas_caja_sucursal_fecha (id_sucursal, fecha_registro),
    ADD INDEX idx_mermas_caja_usuario (id_usuario),
    ADD CONSTRAINT fk_mermas_caja_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales (id_sucursal),
    ADD CONSTRAINT fk_mermas_caja_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario);

CREATE TABLE pagos_pedido (
    id_pago BIGINT NOT NULL AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    id_usuario INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('Efectivo', 'Transferencia', 'Tarjeta', 'Otro') NOT NULL,
    referencia VARCHAR(120) NULL,
    estado ENUM('Aplicado', 'Anulado') NOT NULL DEFAULT 'Aplicado',
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pago),
    KEY idx_pagos_pedido_fecha (id_pedido, fecha_registro),
    CONSTRAINT fk_pagos_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido),
    CONSTRAINT fk_pagos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimientos_inventario (
    id_movimiento BIGINT NOT NULL AUTO_INCREMENT,
    id_inventario INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo ENUM('Inventario inicial', 'Abastecimiento', 'Merma', 'Venta', 'Reversion') NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_posterior INT NOT NULL,
    referencia_tipo VARCHAR(40) NULL,
    referencia_id BIGINT NULL,
    motivo VARCHAR(255) NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_movimiento),
    KEY idx_movimientos_inventario_fecha (id_inventario, fecha_registro),
    KEY idx_movimientos_usuario (id_usuario),
    CONSTRAINT fk_movimientos_inventario FOREIGN KEY (id_inventario) REFERENCES inventario (id_inventario),
    CONSTRAINT fk_movimientos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auditoria (
    id_auditoria BIGINT NOT NULL AUTO_INCREMENT,
    id_usuario INT NULL,
    accion VARCHAR(80) NOT NULL,
    entidad VARCHAR(80) NOT NULL,
    entidad_id BIGINT NULL,
    id_sucursal INT NULL,
    detalles JSON NULL,
    direccion_ip VARCHAR(45) NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_auditoria),
    KEY idx_auditoria_entidad (entidad, entidad_id, fecha_registro),
    KEY idx_auditoria_usuario_fecha (id_usuario, fecha_registro),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_auditoria_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales (id_sucursal) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
