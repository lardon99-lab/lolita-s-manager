-- Tipos de producto, grupos semanticos de pastel y diseno por pedido.
-- Reversion documentada: respaldar primero; eliminar pedido_detalle_diseno y
-- producto_diseno_config, quitar codigo de personalizacion_grupos y
-- tipo_producto de productos. La reversion pierde los nuevos datos de diseno.

ALTER TABLE productos
    ADD COLUMN tipo_producto ENUM('pastel', 'panaderia') NOT NULL DEFAULT 'panaderia' AFTER id_categoria;

UPDATE productos
SET tipo_producto = 'pastel'
WHERE JSON_VALID(descripcion)
  AND JSON_UNQUOTE(JSON_EXTRACT(descripcion, '$.tipo_producto')) = 'pastel';

ALTER TABLE personalizacion_grupos
    ADD COLUMN codigo VARCHAR(50) NULL AFTER id_grupo,
    ADD UNIQUE KEY uq_personalizacion_grupos_codigo (codigo);

UPDATE personalizacion_grupos SET codigo = 'cake_filling'
WHERE codigo IS NULL AND LOWER(nombre) IN ('relleno', 'rellenos');

UPDATE personalizacion_grupos SET codigo = 'cake_covering'
WHERE codigo IS NULL AND LOWER(nombre) IN ('cubierta', 'cubiertas', 'cobertura', 'coberturas');

UPDATE personalizacion_grupos SET codigo = 'cake_flavor'
WHERE codigo IS NULL AND LOWER(nombre) IN ('sabor', 'sabores', 'sabor de torta');

INSERT IGNORE INTO personalizacion_grupos (codigo, nombre, estado) VALUES
    ('cake_flavor', 'Sabor de torta', 'Activo'),
    ('cake_filling', 'Relleno', 'Activo'),
    ('cake_covering', 'Cubierta', 'Activo');

CREATE TABLE producto_diseno_config (
    id_producto INT NOT NULL,
    permite_diseno TINYINT(1) NOT NULL DEFAULT 0,
    permite_imagen TINYINT(1) NOT NULL DEFAULT 1,
    recargo_diseno DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id_producto),
    CONSTRAINT fk_producto_diseno_config_producto
        FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON DELETE CASCADE,
    CONSTRAINT chk_producto_diseno_recargo CHECK (recargo_diseno >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_detalle_diseno (
    id_diseno BIGINT NOT NULL AUTO_INCREMENT,
    id_detalle INT NOT NULL,
    color_descripcion VARCHAR(150) NULL,
    frase VARCHAR(250) NULL,
    instrucciones VARCHAR(1000) NULL,
    recargo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    archivo_nombre_interno VARCHAR(80) NULL,
    archivo_nombre_original VARCHAR(255) NULL,
    archivo_mime VARCHAR(50) NULL,
    archivo_tamano INT UNSIGNED NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_diseno),
    UNIQUE KEY uq_pedido_detalle_diseno_detalle (id_detalle),
    CONSTRAINT fk_pedido_detalle_diseno_detalle
        FOREIGN KEY (id_detalle) REFERENCES pedido_detalles (id_detalle) ON DELETE CASCADE,
    CONSTRAINT chk_pedido_detalle_diseno_recargo CHECK (recargo_unitario >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
