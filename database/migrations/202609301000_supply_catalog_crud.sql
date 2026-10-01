-- Clasifica los vasos para permitir administrar el catalogo sin codigos fijos.

ALTER TABLE insumos
    ADD COLUMN tipo_uso ENUM('bebida', 'batido') NULL AFTER unidad_medida,
    ADD COLUMN stock_minimo DECIMAL(12,3) NOT NULL DEFAULT 10 AFTER tipo_uso;

UPDATE insumos
SET tipo_uso = CASE WHEN codigo = 'cup_shake' THEN 'batido' ELSE 'bebida' END
WHERE tipo_uso IS NULL;

UPDATE insumos i
LEFT JOIN (
    SELECT id_insumo, MIN(stock_minimo) AS stock_minimo
    FROM inventario_insumos
    GROUP BY id_insumo
) ii ON ii.id_insumo = i.id_insumo
SET i.stock_minimo = COALESCE(ii.stock_minimo, 10);

ALTER TABLE insumos
    MODIFY COLUMN tipo_uso ENUM('bebida', 'batido') NOT NULL;

INSERT IGNORE INTO schema_migrations (migration)
VALUES ('202609301000_supply_catalog_crud.sql');

-- Reversion manual, solo despues de retirar vasos creados con el CRUD:
-- ALTER TABLE insumos DROP COLUMN stock_minimo, DROP COLUMN tipo_uso;
