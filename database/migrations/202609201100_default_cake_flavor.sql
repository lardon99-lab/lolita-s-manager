-- Agrega Vainilla como sabor inicial a los pasteles que aun no tienen sabores.
-- Reversion: eliminar las relaciones creadas para cake_flavor y eliminar la
-- opcion Vainilla solamente si no esta siendo utilizada por otros productos.

INSERT IGNORE INTO personalizacion_opciones (id_grupo, nombre, estado)
SELECT id_grupo, 'Vainilla', 'Activo'
FROM personalizacion_grupos
WHERE codigo = 'cake_flavor';

UPDATE personalizacion_opciones o
JOIN personalizacion_grupos g ON g.id_grupo = o.id_grupo
SET o.nombre = 'Jalea de piña'
WHERE g.codigo = 'cake_filling' AND LOWER(o.nombre) = 'jalea de pina';

UPDATE personalizacion_opciones o
JOIN personalizacion_grupos g ON g.id_grupo = o.id_grupo
SET o.nombre = 'Betún'
WHERE g.codigo = 'cake_covering' AND LOWER(o.nombre) = 'betun';

INSERT IGNORE INTO producto_personalizacion_grupos
    (id_producto, id_grupo, minimo_selecciones, maximo_selecciones, orden)
SELECT p.id_producto, g.id_grupo, 1, 1, 0
FROM productos p
JOIN personalizacion_grupos g ON g.codigo = 'cake_flavor'
WHERE p.tipo_producto = 'pastel';

UPDATE producto_personalizacion_grupos pg
JOIN personalizacion_grupos g ON g.id_grupo = pg.id_grupo
JOIN productos p ON p.id_producto = pg.id_producto
SET pg.orden = CASE g.codigo
    WHEN 'cake_flavor' THEN 0
    WHEN 'cake_filling' THEN 1
    WHEN 'cake_covering' THEN 2
    ELSE pg.orden
END
WHERE p.tipo_producto = 'pastel'
  AND g.codigo IN ('cake_flavor', 'cake_filling', 'cake_covering');

INSERT INTO producto_personalizacion_opciones
    (id_producto, id_opcion, recargo, predeterminada, estado, orden)
SELECT p.id_producto, o.id_opcion, 0.00, 1, 'Activo', 0
FROM productos p
JOIN personalizacion_grupos g ON g.codigo = 'cake_flavor'
JOIN personalizacion_opciones o ON o.id_grupo = g.id_grupo AND o.nombre = 'Vainilla'
WHERE p.tipo_producto = 'pastel'
  AND NOT EXISTS (
      SELECT 1
      FROM producto_personalizacion_opciones existing_po
      JOIN personalizacion_opciones existing_o ON existing_o.id_opcion = existing_po.id_opcion
      WHERE existing_po.id_producto = p.id_producto
        AND existing_o.id_grupo = g.id_grupo
  );
