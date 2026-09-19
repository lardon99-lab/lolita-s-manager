CREATE TABLE personalizacion_grupos (
    id_grupo INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_grupo),
    UNIQUE KEY uq_personalizacion_grupos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personalizacion_opciones (
    id_opcion INT NOT NULL AUTO_INCREMENT,
    id_grupo INT NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_opcion),
    UNIQUE KEY uq_personalizacion_opcion_grupo_nombre (id_grupo, nombre),
    CONSTRAINT fk_personalizacion_opcion_grupo
        FOREIGN KEY (id_grupo) REFERENCES personalizacion_grupos (id_grupo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto_personalizacion_grupos (
    id_producto INT NOT NULL,
    id_grupo INT NOT NULL,
    minimo_selecciones TINYINT UNSIGNED NOT NULL DEFAULT 0,
    maximo_selecciones TINYINT UNSIGNED NOT NULL DEFAULT 1,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id_producto, id_grupo),
    CONSTRAINT fk_producto_personalizacion_grupo_producto
        FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON DELETE CASCADE,
    CONSTRAINT fk_producto_personalizacion_grupo_grupo
        FOREIGN KEY (id_grupo) REFERENCES personalizacion_grupos (id_grupo),
    CONSTRAINT chk_producto_personalizacion_limites
        CHECK (maximo_selecciones >= 1 AND minimo_selecciones <= maximo_selecciones)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto_personalizacion_opciones (
    id_producto INT NOT NULL,
    id_opcion INT NOT NULL,
    recargo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    predeterminada TINYINT(1) NOT NULL DEFAULT 0,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id_producto, id_opcion),
    KEY idx_producto_opciones_estado (id_producto, estado, orden),
    CONSTRAINT fk_producto_personalizacion_opcion_producto
        FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON DELETE CASCADE,
    CONSTRAINT fk_producto_personalizacion_opcion_opcion
        FOREIGN KEY (id_opcion) REFERENCES personalizacion_opciones (id_opcion),
    CONSTRAINT chk_producto_personalizacion_recargo CHECK (recargo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_detalle_opciones (
    id_detalle_opcion BIGINT NOT NULL AUTO_INCREMENT,
    id_detalle INT NOT NULL,
    id_opcion INT NULL,
    grupo_nombre VARCHAR(50) NOT NULL,
    opcion_nombre VARCHAR(80) NOT NULL,
    recargo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id_detalle_opcion),
    KEY idx_pedido_detalle_opciones_detalle (id_detalle),
    CONSTRAINT fk_pedido_detalle_opciones_detalle
        FOREIGN KEY (id_detalle) REFERENCES pedido_detalles (id_detalle) ON DELETE CASCADE,
    CONSTRAINT fk_pedido_detalle_opciones_opcion
        FOREIGN KEY (id_opcion) REFERENCES personalizacion_opciones (id_opcion) ON DELETE SET NULL,
    CONSTRAINT chk_pedido_detalle_opciones_recargo CHECK (recargo_unitario >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reversion manual (solo tras respaldo): eliminar en orden
-- pedido_detalle_opciones, producto_personalizacion_opciones,
-- producto_personalizacion_grupos, personalizacion_opciones y personalizacion_grupos.
