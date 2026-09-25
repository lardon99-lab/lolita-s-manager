-- Agrega el vaso compartido de 16 oz para bebidas.
-- Es idempotente para permitir su importacion segura desde phpMyAdmin.

START TRANSACTION;

INSERT INTO insumos (codigo, nombre, unidad_medida, estado)
VALUES ('cup_16oz', 'Vaso para bebidas de 16 oz', 'unidad', 'Activo')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    unidad_medida = VALUES(unidad_medida),
    estado = 'Activo';

INSERT INTO inventario_insumos (id_sucursal, id_insumo, stock_actual, stock_minimo)
SELECT s.id_sucursal, i.id_insumo, 0, 10
FROM sucursales s
JOIN insumos i ON i.codigo = 'cup_16oz'
LEFT JOIN inventario_insumos ii
    ON ii.id_sucursal = s.id_sucursal
   AND ii.id_insumo = i.id_insumo
WHERE ii.id_inventario_insumo IS NULL;

INSERT IGNORE INTO schema_migrations (migration)
VALUES ('202609251000_add_16oz_cup_supply.sql');

COMMIT;

