-- Repara grupos semanticos duplicados creados por despliegues parciales.
-- Ejecutar una sola vez, despues de crear un respaldo de la base de datos.

START TRANSACTION;

-- Si todavia no existe un grupo canonico, reutiliza uno de los grupos antiguos.
UPDATE personalizacion_grupos target
LEFT JOIN personalizacion_grupos canonical ON canonical.codigo = 'cake_filling'
SET target.codigo = 'cake_filling'
WHERE target.codigo IS NULL
  AND LOWER(target.nombre) IN ('relleno', 'rellenos')
  AND canonical.id_grupo IS NULL
LIMIT 1;

UPDATE personalizacion_grupos target
LEFT JOIN personalizacion_grupos canonical ON canonical.codigo = 'cake_covering'
SET target.codigo = 'cake_covering'
WHERE target.codigo IS NULL
  AND LOWER(target.nombre) IN ('cubierta', 'cubiertas', 'cobertura', 'coberturas')
  AND canonical.id_grupo IS NULL
LIMIT 1;

UPDATE personalizacion_grupos target
LEFT JOIN personalizacion_grupos canonical ON canonical.codigo = 'cake_flavor'
SET target.codigo = 'cake_flavor'
WHERE target.codigo IS NULL
  AND LOWER(target.nombre) IN ('sabor', 'sabores', 'sabor de torta')
  AND canonical.id_grupo IS NULL
LIMIT 1;

INSERT IGNORE INTO personalizacion_grupos (codigo, nombre, estado) VALUES
    ('cake_flavor', 'Sabor de torta', 'Activo'),
    ('cake_filling', 'Relleno', 'Activo'),
    ('cake_covering', 'Cubierta', 'Activo');

CREATE TABLE IF NOT EXISTS producto_diseno_config (
    id_producto INT NOT NULL,
    permite_diseno TINYINT(1) NOT NULL DEFAULT 0,
    permite_imagen TINYINT(1) NOT NULL DEFAULT 1,
    recargo_diseno DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id_producto),
    CONSTRAINT fk_producto_diseno_config_producto
        FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON DELETE CASCADE,
    CONSTRAINT chk_producto_diseno_recargo CHECK (recargo_diseno >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_detalle_diseno (
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

CREATE TEMPORARY TABLE tmp_personalizacion_grupos_merge (
    id_grupo_origen INT NOT NULL PRIMARY KEY,
    id_grupo_destino INT NOT NULL
) ENGINE=InnoDB;

INSERT INTO tmp_personalizacion_grupos_merge (id_grupo_origen, id_grupo_destino)
SELECT duplicate_group.id_grupo, canonical.id_grupo
FROM personalizacion_grupos duplicate_group
JOIN personalizacion_grupos canonical
  ON canonical.codigo = CASE
      WHEN LOWER(duplicate_group.nombre) IN ('relleno', 'rellenos') THEN 'cake_filling'
      WHEN LOWER(duplicate_group.nombre) IN ('cubierta', 'cubiertas', 'cobertura', 'coberturas') THEN 'cake_covering'
      WHEN LOWER(duplicate_group.nombre) IN ('sabor', 'sabores', 'sabor de torta') THEN 'cake_flavor'
      ELSE NULL
  END
WHERE duplicate_group.codigo IS NULL
  AND duplicate_group.id_grupo <> canonical.id_grupo;

-- Crea en el grupo canonico las opciones que solo existian en el duplicado.
INSERT INTO personalizacion_opciones (id_grupo, nombre, estado)
SELECT merge_map.id_grupo_destino, source_option.nombre, source_option.estado
FROM personalizacion_opciones source_option
JOIN tmp_personalizacion_grupos_merge merge_map
  ON merge_map.id_grupo_origen = source_option.id_grupo
ON DUPLICATE KEY UPDATE
    estado = IF(
        personalizacion_opciones.estado = 'Activo' OR VALUES(estado) = 'Activo',
        'Activo',
        'Inactivo'
    );

-- Conserva las configuraciones de grupo asignadas a cada producto.
INSERT INTO producto_personalizacion_grupos
    (id_producto, id_grupo, minimo_selecciones, maximo_selecciones, orden)
SELECT product_group.id_producto, merge_map.id_grupo_destino,
       product_group.minimo_selecciones, product_group.maximo_selecciones,
       product_group.orden
FROM producto_personalizacion_grupos product_group
JOIN tmp_personalizacion_grupos_merge merge_map
  ON merge_map.id_grupo_origen = product_group.id_grupo
ON DUPLICATE KEY UPDATE
    minimo_selecciones = GREATEST(
        producto_personalizacion_grupos.minimo_selecciones,
        VALUES(minimo_selecciones)
    ),
    maximo_selecciones = GREATEST(
        producto_personalizacion_grupos.maximo_selecciones,
        VALUES(maximo_selecciones)
    ),
    orden = LEAST(producto_personalizacion_grupos.orden, VALUES(orden));

-- Traslada recargos y opciones predeterminadas a las opciones canonicas.
INSERT INTO producto_personalizacion_opciones
    (id_producto, id_opcion, recargo, predeterminada, estado, orden)
SELECT product_option.id_producto, target_option.id_opcion,
       product_option.recargo, product_option.predeterminada,
       product_option.estado, product_option.orden
FROM producto_personalizacion_opciones product_option
JOIN personalizacion_opciones source_option
  ON source_option.id_opcion = product_option.id_opcion
JOIN tmp_personalizacion_grupos_merge merge_map
  ON merge_map.id_grupo_origen = source_option.id_grupo
JOIN personalizacion_opciones target_option
  ON target_option.id_grupo = merge_map.id_grupo_destino
 AND target_option.nombre = source_option.nombre
ON DUPLICATE KEY UPDATE
    recargo = GREATEST(producto_personalizacion_opciones.recargo, VALUES(recargo)),
    predeterminada = GREATEST(
        producto_personalizacion_opciones.predeterminada,
        VALUES(predeterminada)
    ),
    estado = IF(
        producto_personalizacion_opciones.estado = 'Activo' OR VALUES(estado) = 'Activo',
        'Activo',
        'Inactivo'
    ),
    orden = LEAST(producto_personalizacion_opciones.orden, VALUES(orden));

DELETE product_option
FROM producto_personalizacion_opciones product_option
JOIN personalizacion_opciones source_option
  ON source_option.id_opcion = product_option.id_opcion
JOIN tmp_personalizacion_grupos_merge merge_map
  ON merge_map.id_grupo_origen = source_option.id_grupo;

DELETE product_group
FROM producto_personalizacion_grupos product_group
JOIN tmp_personalizacion_grupos_merge merge_map
  ON merge_map.id_grupo_origen = product_group.id_grupo;

DELETE source_option
FROM personalizacion_opciones source_option
JOIN tmp_personalizacion_grupos_merge merge_map
  ON merge_map.id_grupo_origen = source_option.id_grupo;

DELETE duplicate_group
FROM personalizacion_grupos duplicate_group
JOIN tmp_personalizacion_grupos_merge merge_map
  ON merge_map.id_grupo_origen = duplicate_group.id_grupo;

DROP TEMPORARY TABLE tmp_personalizacion_grupos_merge;

COMMIT;

SELECT id_grupo, codigo, nombre, estado
FROM personalizacion_grupos
WHERE codigo IN ('cake_flavor', 'cake_filling', 'cake_covering')
ORDER BY codigo;
