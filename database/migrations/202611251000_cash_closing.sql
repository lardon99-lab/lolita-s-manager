CREATE TABLE IF NOT EXISTS jornadas_caja (
    id_caja BIGINT NOT NULL AUTO_INCREMENT,
    id_sucursal INT NOT NULL,
    fecha_operativa DATE NOT NULL,
    estado ENUM('Abierta','En conteo','Cerrada') NOT NULL DEFAULT 'Abierta',
    abierto_por INT NOT NULL,
    fecha_apertura DATETIME NOT NULL,
    contado_por INT NULL,
    fecha_conteo DATETIME NULL,
    cerrado_por INT NULL,
    fecha_cierre DATETIME NULL,
    informe JSON NULL,
    recibido_por INT NULL,
    fecha_recepcion DATETIME NULL,
    monto_recibido DECIMAL(14,2) NULL,
    nota_recepcion VARCHAR(500) NULL,
    PRIMARY KEY (id_caja),
    UNIQUE KEY uq_caja_sucursal_fecha (id_sucursal, fecha_operativa),
    CONSTRAINT fk_jornada_caja_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales (id_sucursal),
    CONSTRAINT fk_jornada_caja_apertura FOREIGN KEY (abierto_por) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_jornada_caja_conteo FOREIGN KEY (contado_por) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_jornada_caja_cierre FOREIGN KEY (cerrado_por) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_jornada_caja_recepcion FOREIGN KEY (recibido_por) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ventas_directas
    ADD COLUMN id_caja BIGINT NULL,
    ADD CONSTRAINT fk_venta_jornada_caja FOREIGN KEY (id_caja) REFERENCES jornadas_caja (id_caja);

ALTER TABLE pagos_pedido
    ADD COLUMN id_caja BIGINT NULL,
    ADD CONSTRAINT fk_pago_jornada_caja FOREIGN KEY (id_caja) REFERENCES jornadas_caja (id_caja);

ALTER TABLE mermas_caja
    ADD COLUMN id_caja BIGINT NULL,
    ADD CONSTRAINT fk_gasto_jornada_caja FOREIGN KEY (id_caja) REFERENCES jornadas_caja (id_caja);

INSERT IGNORE INTO schema_migrations (migration) VALUES ('202611251000_cash_closing.sql');

-- Reversion, despues de exportar los cortes y revertir el codigo:
-- ALTER TABLE mermas_caja DROP FOREIGN KEY fk_gasto_jornada_caja, DROP COLUMN id_caja;
-- ALTER TABLE pagos_pedido DROP FOREIGN KEY fk_pago_jornada_caja, DROP COLUMN id_caja;
-- ALTER TABLE ventas_directas DROP FOREIGN KEY fk_venta_jornada_caja, DROP COLUMN id_caja;
-- DROP TABLE jornadas_caja;
