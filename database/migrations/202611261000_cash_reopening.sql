-- Permite varias aperturas por sucursal y día. La apertura se serializa
-- con el bloqueo de la sucursal que también usan los movimientos de caja.
ALTER TABLE jornadas_caja
    ADD KEY idx_caja_sucursal_fecha (id_sucursal, fecha_operativa, id_caja),
    DROP INDEX uq_caja_sucursal_fecha;

INSERT IGNORE INTO schema_migrations (migration) VALUES ('202611261000_cash_reopening.sql');

-- Reversión posible solo cuando no haya más de una apertura por sucursal y fecha:
-- ALTER TABLE jornadas_caja ADD UNIQUE KEY uq_caja_sucursal_fecha (id_sucursal, fecha_operativa), DROP INDEX idx_caja_sucursal_fecha;
