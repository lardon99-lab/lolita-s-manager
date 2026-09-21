-- Agrupa los movimientos de una misma recepcion de inventario y evita que un
-- reintento del navegador registre dos veces el mismo abastecimiento.
-- Reversion: DROP TABLE abastecimientos;

CREATE TABLE abastecimientos (
    id_abastecimiento BIGINT NOT NULL AUTO_INCREMENT,
    id_sucursal INT NOT NULL,
    id_usuario INT NOT NULL,
    idempotency_key CHAR(64) NOT NULL,
    total_productos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total_unidades INT UNSIGNED NOT NULL DEFAULT 0,
    observaciones VARCHAR(500) NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_abastecimiento),
    UNIQUE KEY uq_abastecimiento_usuario_idempotencia (id_usuario, idempotency_key),
    KEY idx_abastecimientos_sucursal_fecha (id_sucursal, fecha_registro),
    CONSTRAINT fk_abastecimientos_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales (id_sucursal),
    CONSTRAINT fk_abastecimientos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

