-- Comprobantes confirmados por el operador en un datafono independiente.
CREATE TABLE IF NOT EXISTS comprobantes_tarjeta (
    id_comprobante BIGINT NOT NULL AUTO_INCREMENT,
    id_sucursal INT NOT NULL,
    terminal VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    fecha_operacion DATE NOT NULL,
    referencia VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    intento_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    solicitud_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    id_venta INT NULL,
    id_pago BIGINT NULL,
    PRIMARY KEY (id_comprobante),
    UNIQUE KEY uq_comprobante_terminal (id_sucursal, terminal, fecha_operacion, referencia),
    UNIQUE KEY uq_comprobante_intento (intento_hash),
    UNIQUE KEY uq_comprobante_venta (id_venta),
    UNIQUE KEY uq_comprobante_pago (id_pago),
    CONSTRAINT fk_comprobante_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales (id_sucursal),
    CONSTRAINT fk_comprobante_venta FOREIGN KEY (id_venta) REFERENCES ventas_directas (id_venta),
    CONSTRAINT fk_comprobante_pago FOREIGN KEY (id_pago) REFERENCES pagos_pedido (id_pago),
    CONSTRAINT chk_comprobante_monto CHECK (monto > 0),
    CONSTRAINT chk_comprobante_origen CHECK (id_venta IS NULL OR id_pago IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration)
VALUES ('202611241000_manual_card_payments.sql');

-- Reversion manual tras respaldar los comprobantes y revertir el codigo:
-- DROP TABLE comprobantes_tarjeta;
