CREATE TABLE IF NOT EXISTS comprobantes_transferencia (
    id_comprobante BIGINT NOT NULL AUTO_INCREMENT,
    id_sucursal INT NOT NULL,
    referencia VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    intento_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    solicitud_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    id_venta INT NULL,
    id_pago BIGINT NULL,
    PRIMARY KEY (id_comprobante),
    UNIQUE KEY uq_transferencia_sucursal_referencia (id_sucursal, referencia),
    UNIQUE KEY uq_transferencia_intento (intento_hash),
    UNIQUE KEY uq_transferencia_venta (id_venta),
    UNIQUE KEY uq_transferencia_pago (id_pago),
    CONSTRAINT fk_transferencia_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales (id_sucursal),
    CONSTRAINT fk_transferencia_venta FOREIGN KEY (id_venta) REFERENCES ventas_directas (id_venta),
    CONSTRAINT fk_transferencia_pago FOREIGN KEY (id_pago) REFERENCES pagos_pedido (id_pago),
    CONSTRAINT chk_transferencia_monto CHECK (monto > 0),
    CONSTRAINT chk_transferencia_origen CHECK (id_venta IS NULL OR id_pago IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration)
VALUES ('202611261100_bank_transfer_receipts.sql');

-- Reversión después de respaldar los comprobantes y revertir el código:
-- DROP TABLE comprobantes_transferencia;
