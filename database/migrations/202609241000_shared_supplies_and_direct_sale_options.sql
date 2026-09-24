-- Insumos compartidos, recetas y personalizaciones para venta directa.
-- Las bebidas y batidos se venden directamente y consumen vasos por sucursal.

START TRANSACTION;

ALTER TABLE productos
    MODIFY COLUMN tipo_producto ENUM('pastel', 'panaderia', 'bebida', 'batido')
        NOT NULL DEFAULT 'panaderia',
    ADD COLUMN control_inventario ENUM('producto', 'insumos', 'sin_control')
        NOT NULL DEFAULT 'producto' AFTER tipo_producto,
    ADD COLUMN disponible_venta_directa TINYINT(1) NOT NULL DEFAULT 1 AFTER control_inventario,
    ADD COLUMN disponible_pedido TINYINT(1) NOT NULL DEFAULT 1 AFTER disponible_venta_directa;

CREATE TABLE producto_sucursales (
    id_producto INT NOT NULL,
    id_sucursal INT NOT NULL,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (id_producto, id_sucursal),
    KEY idx_producto_sucursales_sucursal (id_sucursal, estado),
    CONSTRAINT fk_producto_sucursales_producto
        FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON DELETE CASCADE,
    CONSTRAINT fk_producto_sucursales_sucursal
        FOREIGN KEY (id_sucursal) REFERENCES sucursales (id_sucursal) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO producto_sucursales (id_producto, id_sucursal)
SELECT DISTINCT id_producto, id_sucursal FROM inventario;

CREATE TABLE insumos (
    id_insumo INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    unidad_medida VARCHAR(20) NOT NULL DEFAULT 'unidad',
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (id_insumo),
    UNIQUE KEY uq_insumos_codigo (codigo),
    UNIQUE KEY uq_insumos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventario_insumos (
    id_inventario_insumo INT NOT NULL AUTO_INCREMENT,
    id_sucursal INT NOT NULL,
    id_insumo INT NOT NULL,
    stock_actual DECIMAL(12,3) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(12,3) NOT NULL DEFAULT 10,
    ultima_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_inventario_insumo),
    UNIQUE KEY uq_inventario_insumo_sucursal (id_sucursal, id_insumo),
    KEY idx_inventario_insumos_alertas (id_sucursal, stock_actual, stock_minimo),
    CONSTRAINT fk_inventario_insumos_sucursal
        FOREIGN KEY (id_sucursal) REFERENCES sucursales (id_sucursal) ON DELETE CASCADE,
    CONSTRAINT fk_inventario_insumos_insumo
        FOREIGN KEY (id_insumo) REFERENCES insumos (id_insumo) ON DELETE CASCADE,
    CONSTRAINT chk_inventario_insumos_stock CHECK (stock_actual >= 0 AND stock_minimo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto_insumos (
    id_producto INT NOT NULL,
    id_insumo INT NOT NULL,
    cantidad DECIMAL(12,3) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_producto, id_insumo),
    KEY idx_producto_insumos_insumo (id_insumo),
    CONSTRAINT fk_producto_insumos_producto
        FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON DELETE CASCADE,
    CONSTRAINT fk_producto_insumos_insumo
        FOREIGN KEY (id_insumo) REFERENCES insumos (id_insumo),
    CONSTRAINT chk_producto_insumos_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimientos_insumos (
    id_movimiento BIGINT NOT NULL AUTO_INCREMENT,
    id_inventario_insumo INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo ENUM('Inventario inicial', 'Abastecimiento', 'Merma', 'Venta', 'Reversion') NOT NULL,
    cantidad DECIMAL(12,3) NOT NULL,
    stock_anterior DECIMAL(12,3) NOT NULL,
    stock_posterior DECIMAL(12,3) NOT NULL,
    referencia_tipo VARCHAR(40) NULL,
    referencia_id BIGINT NULL,
    motivo VARCHAR(255) NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_movimiento),
    KEY idx_movimientos_insumos_fecha (id_inventario_insumo, fecha_registro),
    KEY idx_movimientos_insumos_usuario (id_usuario),
    CONSTRAINT fk_movimientos_insumos_inventario
        FOREIGN KEY (id_inventario_insumo) REFERENCES inventario_insumos (id_inventario_insumo),
    CONSTRAINT fk_movimientos_insumos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE producto_personalizacion_grupos
    ADD COLUMN selecciones_incluidas TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER maximo_selecciones,
    ADD COLUMN recargo_seleccion_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER selecciones_incluidas,
    ADD CONSTRAINT chk_producto_personalizacion_incluidas
        CHECK (selecciones_incluidas <= maximo_selecciones),
    ADD CONSTRAINT chk_producto_personalizacion_recargo_extra
        CHECK (recargo_seleccion_extra >= 0);

CREATE TABLE venta_item_opciones (
    id_item_opcion BIGINT NOT NULL AUTO_INCREMENT,
    id_item INT NOT NULL,
    id_opcion INT NULL,
    grupo_nombre VARCHAR(50) NOT NULL,
    opcion_nombre VARCHAR(80) NOT NULL,
    recargo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id_item_opcion),
    KEY idx_venta_item_opciones_item (id_item),
    CONSTRAINT fk_venta_item_opciones_item
        FOREIGN KEY (id_item) REFERENCES venta_items (id_item) ON DELETE CASCADE,
    CONSTRAINT fk_venta_item_opciones_opcion
        FOREIGN KEY (id_opcion) REFERENCES personalizacion_opciones (id_opcion) ON DELETE SET NULL,
    CONSTRAINT chk_venta_item_opciones_recargo CHECK (recargo_unitario >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO insumos (codigo, nombre, unidad_medida) VALUES
    ('cup_8oz', 'Vaso para bebidas de 8 oz', 'unidad'),
    ('cup_12oz', 'Vaso para bebidas de 12 oz', 'unidad'),
    ('cup_shake', 'Vaso estandar para batido', 'unidad');

INSERT INTO inventario_insumos (id_sucursal, id_insumo, stock_actual, stock_minimo)
SELECT s.id_sucursal, i.id_insumo, 0, 10
FROM sucursales s CROSS JOIN insumos i
WHERE s.estado = 'Activa';

INSERT IGNORE INTO schema_migrations (migration)
VALUES ('202609241000_shared_supplies_and_direct_sale_options.sql');

COMMIT;

-- Reversion manual, despues de respaldar y retirar productos dependientes:
-- DROP TABLE venta_item_opciones;
-- ALTER TABLE producto_personalizacion_grupos DROP COLUMN recargo_seleccion_extra,
--     DROP COLUMN selecciones_incluidas;
-- DROP TABLE movimientos_insumos;
-- DROP TABLE producto_insumos;
-- DROP TABLE inventario_insumos;
-- DROP TABLE insumos;
-- DROP TABLE producto_sucursales;
-- ALTER TABLE productos DROP COLUMN disponible_pedido,
--     DROP COLUMN disponible_venta_directa, DROP COLUMN control_inventario,
--     MODIFY tipo_producto ENUM('pastel','panaderia') NOT NULL DEFAULT 'panaderia';
