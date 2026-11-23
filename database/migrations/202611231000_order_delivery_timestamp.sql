-- Registra la fecha real de entrega sin sobrescribir la fecha de creacion.
-- Los pedidos ya entregados conservan la fecha que el flujo anterior dejo en fecha_registro.

ALTER TABLE pedidos
    ADD COLUMN fecha_entregado DATETIME NULL AFTER fecha_registro,
    ADD KEY idx_pedidos_estado_sucursal_entregado (estado, id_sucursal, fecha_entregado);

UPDATE pedidos
SET fecha_entregado = fecha_registro
WHERE estado = 'Entregado' AND fecha_entregado IS NULL;

INSERT IGNORE INTO schema_migrations (migration)
VALUES ('202611231000_order_delivery_timestamp.sql');

-- Reversion manual: ALTER TABLE pedidos DROP INDEX idx_pedidos_estado_sucursal_entregado, DROP COLUMN fecha_entregado;
