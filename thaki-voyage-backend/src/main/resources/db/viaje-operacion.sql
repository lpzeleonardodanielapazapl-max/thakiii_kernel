-- Aplicar sobre bases ya inicializadas (compose volumen persistente).
-- Si la columna ya existe, ignora el error de duplicado.
ALTER TABLE empleado ADD COLUMN es_cocinero TINYINT(1) DEFAULT 0;

CREATE TABLE IF NOT EXISTS viaje_personal (
  id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
  cod_viaje     VARCHAR(20) NOT NULL,
  cod_empleado  VARCHAR(20) NOT NULL,
  rol_en_viaje  VARCHAR(30) NOT NULL,
  notas         VARCHAR(500),
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_viaje_empleado (cod_viaje, cod_empleado),
  CONSTRAINT fk_vp_viaje FOREIGN KEY (cod_viaje) REFERENCES viaje_confirmado(cod_viaje) ON DELETE CASCADE,
  CONSTRAINT fk_vp_empleado FOREIGN KEY (cod_empleado) REFERENCES empleado(cod_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS viaje_utileria (
  id_utileria   INT AUTO_INCREMENT PRIMARY KEY,
  cod_viaje     VARCHAR(20) NOT NULL,
  cod_producto  VARCHAR(30) NOT NULL,
  cantidad      INT NOT NULL,
  notas         VARCHAR(500),
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_viaje_producto (cod_viaje, cod_producto),
  CONSTRAINT fk_vu_viaje FOREIGN KEY (cod_viaje) REFERENCES viaje_confirmado(cod_viaje) ON DELETE CASCADE,
  CONSTRAINT fk_vu_producto FOREIGN KEY (cod_producto) REFERENCES producto(cod_pro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
