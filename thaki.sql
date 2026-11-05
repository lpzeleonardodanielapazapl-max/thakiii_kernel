-- ============================================================
-- BASE DE DATOS DEFINITIVA: thaki_modificado
-- Sistema: Thaki Voyage
-- Motor: MySQL 8.0
-- ============================================================

DROP DATABASE IF EXISTS thaki_modificado;
CREATE DATABASE thaki_modificado
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE thaki_modificado;

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- 1. AUTENTICACIÓN Y USUARIOS
-- ============================================================

CREATE TABLE rol (
  cod_rol     VARCHAR(20) PRIMARY KEY,
  des_rol     VARCHAR(50) NOT NULL UNIQUE,
  descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permiso (
  cod_permiso VARCHAR(30) PRIMARY KEY,
  des_permiso VARCHAR(150) NOT NULL,
  modulo      VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rol_permiso (
  cod_rol     VARCHAR(20) NOT NULL,
  cod_permiso VARCHAR(30) NOT NULL,
  PRIMARY KEY (cod_rol, cod_permiso),
  CONSTRAINT fk_rp_rol FOREIGN KEY (cod_rol) REFERENCES rol(cod_rol) ON DELETE CASCADE,
  CONSTRAINT fk_rp_permiso FOREIGN KEY (cod_permiso) REFERENCES permiso(cod_permiso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE auth_provider (
  cod_provider VARCHAR(20) PRIMARY KEY,
  nombre       VARCHAR(50) NOT NULL UNIQUE,
  activo       TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuario (
  id_usuario       INT AUTO_INCREMENT PRIMARY KEY,
  username         VARCHAR(60) UNIQUE,
  email            VARCHAR(150) UNIQUE,
  password_hash    VARCHAR(255),
  cod_rol          VARCHAR(20),
  activo           TINYINT(1) DEFAULT 1,
  email_verificado TINYINT(1) DEFAULT 0,
  ultimo_acceso    DATETIME,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_rol FOREIGN KEY (cod_rol) REFERENCES rol(cod_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuario_auth (
  id_usuario       INT NOT NULL,
  cod_provider     VARCHAR(20) NOT NULL,
  provider_user_id VARCHAR(150) NOT NULL,
  access_token     TEXT,
  refresh_token    TEXT,
  expira_en        DATETIME,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, cod_provider),
  CONSTRAINT fk_uath_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_uath_provider FOREIGN KEY (cod_provider) REFERENCES auth_provider(cod_provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sesion (
  id_sesion    VARCHAR(100) PRIMARY KEY,
  id_usuario   INT,
  ip           VARCHAR(45),
  user_agent   VARCHAR(300),
  fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_fin    DATETIME,
  activa       TINYINT(1) DEFAULT 1,
  CONSTRAINT fk_sesion_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE token_recuperacion (
  id_token   INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT,
  token      VARCHAR(255) NOT NULL UNIQUE,
  tipo       VARCHAR(30) NOT NULL,
  expira_en  DATETIME NOT NULL,
  usado      TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_token_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE log_acceso (
  id_log     INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT,
  email      VARCHAR(150),
  exitoso    TINYINT(1) NOT NULL,
  ip         VARCHAR(45),
  user_agent VARCHAR(300),
  fecha      DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. PERSONAL
-- ============================================================

CREATE TABLE persona (
  cod_persona      VARCHAR(20) PRIMARY KEY,
  tipo_documento   VARCHAR(20),
  numero_documento VARCHAR(30) UNIQUE,
  nombres          VARCHAR(100) NOT NULL,
  apellidos        VARCHAR(100) NOT NULL,
  telefono         VARCHAR(30),
  email            VARCHAR(150),
  direccion        VARCHAR(200),
  fecha_nacimiento DATE,
  genero           VARCHAR(20),
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cargo (
  cod_cargo   VARCHAR(20) PRIMARY KEY,
  des_cargo   VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE departamento (
  cod_dep     VARCHAR(20) PRIMARY KEY,
  des_dep     VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE empleado (
  cod_empleado  VARCHAR(20) PRIMARY KEY,
  cod_persona   VARCHAR(20) NOT NULL UNIQUE,
  cod_cargo     VARCHAR(20),
  cod_dep       VARCHAR(20),
  fecha_ingreso DATE,
  fecha_retiro  DATE,
  salario       DECIMAL(12,2),
  es_guia       TINYINT(1) DEFAULT 0,
  es_asesor     TINYINT(1) DEFAULT 0,
  es_conductor  TINYINT(1) DEFAULT 0,
  estado        VARCHAR(20) DEFAULT 'ACTIVO',
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_empleado_persona FOREIGN KEY (cod_persona) REFERENCES persona(cod_persona),
  CONSTRAINT fk_empleado_cargo FOREIGN KEY (cod_cargo) REFERENCES cargo(cod_cargo),
  CONSTRAINT fk_empleado_dep FOREIGN KEY (cod_dep) REFERENCES departamento(cod_dep)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. CLIENTES
-- ============================================================

CREATE TABLE cliente (
  cod_cliente    VARCHAR(20) PRIMARY KEY,
  cod_persona    VARCHAR(20),
  codigo_cliente VARCHAR(30) UNIQUE,
  estado         VARCHAR(20) DEFAULT 'ACTIVO',
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cliente_persona FOREIGN KEY (cod_persona) REFERENCES persona(cod_persona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. EMPRESA
-- ============================================================

CREATE TABLE empresa (
  cod_empresa VARCHAR(20) PRIMARY KEY,
  nombre      VARCHAR(150) NOT NULL,
  slogan      VARCHAR(200),
  mision      TEXT,
  vision      TEXT,
  historia    TEXT,
  logo_url    VARCHAR(300),
  direccion   VARCHAR(200),
  ciudad      VARCHAR(100),
  pais        VARCHAR(80),
  telefono    VARCHAR(50),
  email       VARCHAR(150),
  sitio_web   VARCHAR(150),
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. DESTINOS
-- ============================================================

CREATE TABLE categoria_destino (
  cod_cat_destino VARCHAR(20) PRIMARY KEY,
  des_cat_destino VARCHAR(100) NOT NULL,
  descripcion     TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE destino (
  cod_destino               VARCHAR(20) PRIMARY KEY,
  nombre                    VARCHAR(150) NOT NULL,
  slug                      VARCHAR(150) UNIQUE,
  cod_cat_destino           VARCHAR(20),
  pais                      VARCHAR(80),
  region                    VARCHAR(80),
  ciudad                    VARCHAR(80),
  latitud                   DECIMAL(10,6),
  longitud                  DECIMAL(10,6),
  altitud_msnm              INT,
  dificultad                VARCHAR(30),
  duracion_recomendada_dias INT,
  temporada                 VARCHAR(100),
  descripcion_corta         TEXT,
  historia                  TEXT,
  como_se_hace              TEXT,
  recomendaciones           TEXT,
  estado                    VARCHAR(20) DEFAULT 'ACTIVO',
  created_at                DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at                DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_destino_categoria FOREIGN KEY (cod_cat_destino) REFERENCES categoria_destino(cod_cat_destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE destino_historia (
  cod_historia INT AUTO_INCREMENT PRIMARY KEY,
  cod_destino  VARCHAR(20) NOT NULL,
  titulo       VARCHAR(150),
  contenido    TEXT NOT NULL,
  fuente       VARCHAR(200),
  fecha_evento DATE,
  orden        INT DEFAULT 0,
  CONSTRAINT fk_dhist_destino FOREIGN KEY (cod_destino) REFERENCES destino(cod_destino) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE destino_itinerario (
  cod_itinerario INT AUTO_INCREMENT PRIMARY KEY,
  cod_destino    VARCHAR(20) NOT NULL,
  dia            INT NOT NULL,
  hora_inicio    TIME,
  hora_fin       TIME,
  titulo         VARCHAR(150),
  descripcion    TEXT,
  lugar          VARCHAR(150),
  duracion_min   INT,
  CONSTRAINT fk_dit_destino FOREIGN KEY (cod_destino) REFERENCES destino(cod_destino) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE destino_actividad (
  cod_actividad    INT AUTO_INCREMENT PRIMARY KEY,
  cod_destino      VARCHAR(20) NOT NULL,
  nombre           VARCHAR(150) NOT NULL,
  descripcion      TEXT,
  nivel            VARCHAR(30),
  equipo_requerido TEXT,
  duracion_min     INT,
  CONSTRAINT fk_dact_destino FOREIGN KEY (cod_destino) REFERENCES destino(cod_destino) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE destino_imagen (
  cod_imagen   INT AUTO_INCREMENT PRIMARY KEY,
  cod_destino  VARCHAR(20) NOT NULL,
  url          VARCHAR(300) NOT NULL,
  descripcion  VARCHAR(200),
  orden        INT DEFAULT 0,
  es_principal TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_dimg_destino FOREIGN KEY (cod_destino) REFERENCES destino(cod_destino) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. VIAJES A MEDIDA
-- ============================================================

CREATE TABLE solicitud_viaje (
  cod_solicitud   VARCHAR(20) PRIMARY KEY,
  cod_cliente     VARCHAR(20) NOT NULL,
  fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_inicio    DATE,
  fecha_fin       DATE,
  num_personas    INT,
  presupuesto     DECIMAL(12,2),
  moneda          VARCHAR(10) DEFAULT 'BOB',
  intereses       TEXT,
  estado          VARCHAR(30) DEFAULT 'RECIBIDA',
  observaciones   TEXT,
  CONSTRAINT fk_solv_cliente FOREIGN KEY (cod_cliente) REFERENCES cliente(cod_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE solicitud_preferencia (
  id_preferencia INT AUTO_INCREMENT PRIMARY KEY,
  cod_solicitud  VARCHAR(20) NOT NULL,
  tipo           VARCHAR(50),
  valor          VARCHAR(200),
  CONSTRAINT fk_solp_solicitud FOREIGN KEY (cod_solicitud) REFERENCES solicitud_viaje(cod_solicitud) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE solicitud_destino (
  cod_solicitud VARCHAR(20) NOT NULL,
  cod_destino   VARCHAR(20) NOT NULL,
  prioridad     INT DEFAULT 1,
  PRIMARY KEY (cod_solicitud, cod_destino),
  CONSTRAINT fk_sold_solicitud FOREIGN KEY (cod_solicitud) REFERENCES solicitud_viaje(cod_solicitud) ON DELETE CASCADE,
  CONSTRAINT fk_sold_destino FOREIGN KEY (cod_destino) REFERENCES destino(cod_destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE propuesta_viaje (
  cod_propuesta VARCHAR(20) PRIMARY KEY,
  cod_solicitud VARCHAR(20) NOT NULL,
  version       INT DEFAULT 1,
  titulo        VARCHAR(200),
  descripcion   TEXT,
  duracion_dias INT,
  precio_total  DECIMAL(12,2),
  moneda        VARCHAR(10) DEFAULT 'BOB',
  fecha_envio   DATETIME DEFAULT CURRENT_TIMESTAMP,
  estado        VARCHAR(30) DEFAULT 'ENVIADA',
  UNIQUE KEY uk_prop_sol_version (cod_solicitud, version),
  CONSTRAINT fk_prop_solicitud FOREIGN KEY (cod_solicitud) REFERENCES solicitud_viaje(cod_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE propuesta_detalle (
  id_detalle    INT AUTO_INCREMENT PRIMARY KEY,
  cod_propuesta VARCHAR(20) NOT NULL,
  dia           INT,
  descripcion   TEXT,
  servicios     TEXT,
  precio        DECIMAL(12,2),
  CONSTRAINT fk_propd_propuesta FOREIGN KEY (cod_propuesta) REFERENCES propuesta_viaje(cod_propuesta) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE propuesta_aprobacion (
  id_aprobacion INT AUTO_INCREMENT PRIMARY KEY,
  cod_propuesta VARCHAR(20) NOT NULL,
  aprobado      TINYINT(1) NOT NULL,
  comentarios   TEXT,
  fecha         DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_propa_propuesta FOREIGN KEY (cod_propuesta) REFERENCES propuesta_viaje(cod_propuesta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reserva (
  cod_reserva   VARCHAR(20) PRIMARY KEY,
  cod_cliente   VARCHAR(20) NOT NULL,
  fecha_reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_inicio  DATE,
  fecha_fin     DATE,
  num_personas  INT DEFAULT 1,
  estado        VARCHAR(30) DEFAULT 'PENDIENTE',
  total         DECIMAL(12,2),
  moneda        VARCHAR(10) DEFAULT 'BOB',
  observaciones TEXT,
  CONSTRAINT fk_reserva_cliente FOREIGN KEY (cod_cliente) REFERENCES cliente(cod_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE viaje_confirmado (
  cod_viaje     VARCHAR(20) PRIMARY KEY,
  cod_propuesta VARCHAR(20),
  cod_reserva   VARCHAR(20),
  fecha_inicio  DATE,
  fecha_fin     DATE,
  estado        VARCHAR(30) DEFAULT 'CONFIRMADO',
  UNIQUE KEY uk_vc_propuesta (cod_propuesta),
  CONSTRAINT fk_vc_propuesta FOREIGN KEY (cod_propuesta) REFERENCES propuesta_viaje(cod_propuesta),
  CONSTRAINT fk_vc_reserva FOREIGN KEY (cod_reserva) REFERENCES reserva(cod_reserva)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE viaje_servicio (
  cod_servicio      VARCHAR(20) PRIMARY KEY,
  cod_viaje         VARCHAR(20) NOT NULL,
  tipo              VARCHAR(50),
  descripcion       TEXT,
  proveedor         VARCHAR(150),
  costo             DECIMAL(12,2),
  cod_reserva_hotel VARCHAR(20),
  cod_reserva_trans VARCHAR(20),
  CONSTRAINT fk_vs_viaje FOREIGN KEY (cod_viaje) REFERENCES viaje_confirmado(cod_viaje) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reserva_pasajero (
  id_pasajero     INT AUTO_INCREMENT PRIMARY KEY,
  cod_reserva     VARCHAR(20) NOT NULL,
  cod_persona     VARCHAR(20),
  nombre_completo VARCHAR(200),
  documento       VARCHAR(30),
  observaciones   TEXT,
  CONSTRAINT fk_rp_reserva FOREIGN KEY (cod_reserva) REFERENCES reserva(cod_reserva) ON DELETE CASCADE,
  CONSTRAINT fk_rp_persona FOREIGN KEY (cod_persona) REFERENCES persona(cod_persona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pago (
  cod_pago    VARCHAR(20) PRIMARY KEY,
  cod_reserva VARCHAR(20) NOT NULL,
  fecha       DATE NOT NULL,
  monto       DECIMAL(12,2) NOT NULL,
  metodo      VARCHAR(50),
  referencia  VARCHAR(100),
  estado      VARCHAR(20) DEFAULT 'CONFIRMADO',
  CONSTRAINT fk_pago_reserva FOREIGN KEY (cod_reserva) REFERENCES reserva(cod_reserva)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. HOTELES
-- ============================================================

CREATE TABLE hotel (
  cod_hotel   VARCHAR(20) PRIMARY KEY,
  nombre      VARCHAR(150) NOT NULL,
  descripcion TEXT,
  categoria   INT,
  direccion   VARCHAR(200),
  ciudad      VARCHAR(100),
  pais        VARCHAR(80),
  telefono    VARCHAR(50),
  email       VARCHAR(150),
  sitio_web   VARCHAR(200),
  latitud     DECIMAL(10,6),
  longitud    DECIMAL(10,6),
  check_in    TIME,
  check_out   TIME,
  servicios   TEXT,
  imagen_url  VARCHAR(300),
  cod_destino VARCHAR(20),
  estado      VARCHAR(20) DEFAULT 'ACTIVO',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hotel_destino FOREIGN KEY (cod_destino) REFERENCES destino(cod_destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE hotel_tipo_habitacion (
  cod_tipo_hab VARCHAR(20) PRIMARY KEY,
  cod_hotel    VARCHAR(20) NOT NULL,
  nombre       VARCHAR(100) NOT NULL,
  descripcion  TEXT,
  capacidad    INT,
  num_camas    INT,
  tipo_camas   VARCHAR(100),
  imagen_url   VARCHAR(300),
  estado       VARCHAR(20) DEFAULT 'ACTIVO',
  CONSTRAINT fk_htipo_hotel FOREIGN KEY (cod_hotel) REFERENCES hotel(cod_hotel) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE hotel_tarifa (
  cod_tarifa       VARCHAR(20) PRIMARY KEY,
  cod_tipo_hab     VARCHAR(20) NOT NULL,
  fecha_inicio     DATE NOT NULL,
  fecha_fin        DATE,
  moneda           VARCHAR(10) DEFAULT 'BOB',
  precio_noche     DECIMAL(12,2) NOT NULL,
  incluye_desayuno TINYINT(1) DEFAULT 0,
  observaciones    TEXT,
  CONSTRAINT fk_htarifa_tipo FOREIGN KEY (cod_tipo_hab) REFERENCES hotel_tipo_habitacion(cod_tipo_hab) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reserva_hotel (
  cod_reserva_hotel VARCHAR(20) PRIMARY KEY,
  cod_reserva       VARCHAR(20) NOT NULL,
  cod_hotel         VARCHAR(20) NOT NULL,
  fecha_check_in    DATE NOT NULL,
  fecha_check_out   DATE NOT NULL,
  num_habitaciones  INT DEFAULT 1,
  total             DECIMAL(12,2),
  estado            VARCHAR(20) DEFAULT 'CONFIRMADA',
  observaciones     TEXT,
  CONSTRAINT fk_rHotel_reserva FOREIGN KEY (cod_reserva) REFERENCES reserva(cod_reserva),
  CONSTRAINT fk_rHotel_hotel FOREIGN KEY (cod_hotel) REFERENCES hotel(cod_hotel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reserva_hotel_detalle (
  id_detalle        INT AUTO_INCREMENT PRIMARY KEY,
  cod_reserva_hotel VARCHAR(20) NOT NULL,
  cod_tipo_hab      VARCHAR(20) NOT NULL,
  cantidad          INT DEFAULT 1,
  precio_noche      DECIMAL(12,2) NOT NULL,
  num_noches        INT NOT NULL,
  subtotal          DECIMAL(12,2),
  CONSTRAINT fk_rHD_reserva FOREIGN KEY (cod_reserva_hotel) REFERENCES reserva_hotel(cod_reserva_hotel) ON DELETE CASCADE,
  CONSTRAINT fk_rHD_tipo FOREIGN KEY (cod_tipo_hab) REFERENCES hotel_tipo_habitacion(cod_tipo_hab)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. TRANSPORTE
-- ============================================================

CREATE TABLE vehiculo (
  cod_vehiculo  VARCHAR(20) PRIMARY KEY,
  placa         VARCHAR(20) NOT NULL UNIQUE,
  marca         VARCHAR(80),
  modelo        VARCHAR(80),
  anio          INT,
  color         VARCHAR(50),
  capacidad     INT,
  tipo          VARCHAR(50),
  estado        VARCHAR(20) DEFAULT 'DISPONIBLE',
  observaciones TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conductor (
  cod_conductor     VARCHAR(20) PRIMARY KEY,
  cod_empleado      VARCHAR(20) NOT NULL UNIQUE,
  licencia          VARCHAR(50),
  categoria_lic     VARCHAR(10),
  vencimiento_lic   DATE,
  experiencia_anios INT,
  estado            VARCHAR(20) DEFAULT 'ACTIVO',
  CONSTRAINT fk_conductor_empleado FOREIGN KEY (cod_empleado) REFERENCES empleado(cod_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE servicio_transporte (
  cod_servicio  VARCHAR(20) PRIMARY KEY,
  nombre        VARCHAR(150) NOT NULL,
  descripcion   TEXT,
  tipo          VARCHAR(50),
  origen        VARCHAR(150),
  destino       VARCHAR(150),
  distancia_km  DECIMAL(10,2),
  duracion_min  INT,
  precio_base   DECIMAL(12,2),
  moneda        VARCHAR(10) DEFAULT 'BOB',
  capacidad_max INT,
  estado        VARCHAR(20) DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reserva_transporte (
  cod_reserva_trans VARCHAR(20) PRIMARY KEY,
  cod_reserva       VARCHAR(20) NOT NULL,
  cod_servicio      VARCHAR(20),
  cod_vehiculo      VARCHAR(20),
  cod_conductor     VARCHAR(20),
  fecha_salida      DATETIME NOT NULL,
  fecha_llegada     DATETIME,
  punto_recogida    VARCHAR(200),
  punto_destino     VARCHAR(200),
  num_pasajeros     INT,
  total             DECIMAL(12,2),
  estado            VARCHAR(20) DEFAULT 'PROGRAMADO',
  observaciones     TEXT,
  CONSTRAINT fk_rTrans_reserva FOREIGN KEY (cod_reserva) REFERENCES reserva(cod_reserva),
  CONSTRAINT fk_rTrans_servicio FOREIGN KEY (cod_servicio) REFERENCES servicio_transporte(cod_servicio),
  CONSTRAINT fk_rTrans_vehiculo FOREIGN KEY (cod_vehiculo) REFERENCES vehiculo(cod_vehiculo),
  CONSTRAINT fk_rTrans_conductor FOREIGN KEY (cod_conductor) REFERENCES conductor(cod_conductor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 9. INVENTARIOS
-- ============================================================

CREATE TABLE almacen (
  cod_almacen VARCHAR(20) PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  ubicacion   VARCHAR(200),
  cod_responsable VARCHAR(20),
  estado      VARCHAR(20) DEFAULT 'ACTIVO',
  CONSTRAINT fk_almacen_resp FOREIGN KEY (cod_responsable) REFERENCES empleado(cod_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clase_producto (
  cod_clase      VARCHAR(20) PRIMARY KEY,
  des_clase      VARCHAR(150) NOT NULL,
  descripcion    TEXT,
  cod_padre      VARCHAR(20),
  es_serializado TINYINT(1) DEFAULT 0,
  es_consumible  TINYINT(1) DEFAULT 0,
  tipo_inv       VARCHAR(30),
  CONSTRAINT fk_clase_padre FOREIGN KEY (cod_padre) REFERENCES clase_producto(cod_clase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE marca (
  cod_mar     VARCHAR(20) PRIMARY KEY,
  des_mar     VARCHAR(100) NOT NULL UNIQUE,
  pais_origen VARCHAR(80)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE unidad_medida (
  cod_um      VARCHAR(10) PRIMARY KEY,
  des_um      VARCHAR(50) NOT NULL,
  abreviatura VARCHAR(10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE producto (
  cod_pro      VARCHAR(30) PRIMARY KEY,
  cod_clase    VARCHAR(20) NOT NULL,
  cod_mar      VARCHAR(20),
  cod_um       VARCHAR(10),
  des_pro      VARCHAR(200) NOT NULL,
  modelo       VARCHAR(100),
  color        VARCHAR(80),
  stock_minimo DECIMAL(12,2) DEFAULT 0,
  estado       VARCHAR(20) DEFAULT 'ACTIVO',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_producto_clase FOREIGN KEY (cod_clase) REFERENCES clase_producto(cod_clase),
  CONSTRAINT fk_producto_marca FOREIGN KEY (cod_mar) REFERENCES marca(cod_mar),
  CONSTRAINT fk_producto_um FOREIGN KEY (cod_um) REFERENCES unidad_medida(cod_um)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE precio (
  cod_pro   VARCHAR(30) NOT NULL,
  cod_pre   VARCHAR(20) NOT NULL,
  fec_com   DATE NOT NULL,
  mon_pre   VARCHAR(10) DEFAULT 'BOB',
  monto_pre DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (cod_pro, cod_pre, fec_com),
  CONSTRAINT fk_precio_producto FOREIGN KEY (cod_pro) REFERENCES producto(cod_pro) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventario_unidad (
  id_unidad        INT AUTO_INCREMENT PRIMARY KEY,
  cod_pro          VARCHAR(30) NOT NULL,
  cod_almacen      VARCHAR(20),
  codigo_unico     VARCHAR(50) UNIQUE,
  estado_fisico    VARCHAR(20),
  calificacion     INT,
  requiere_arreglo TINYINT(1) DEFAULT 0,
  requiere_cambio  TINYINT(1) DEFAULT 0,
  requiere_baja    TINYINT(1) DEFAULT 0,
  fecha_alta       DATE,
  fecha_baja       DATE,
  observaciones    TEXT,
  CONSTRAINT fk_invU_producto FOREIGN KEY (cod_pro) REFERENCES producto(cod_pro),
  CONSTRAINT fk_invU_almacen FOREIGN KEY (cod_almacen) REFERENCES almacen(cod_almacen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventario_stock (
  id_stock          INT AUTO_INCREMENT PRIMARY KEY,
  cod_pro           VARCHAR(30) NOT NULL,
  cod_almacen       VARCHAR(20) NOT NULL,
  cantidad          DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo_promedio    DECIMAL(12,2),
  ubicacion_detalle VARCHAR(150),
  UNIQUE KEY uk_stock (cod_pro, cod_almacen),
  CONSTRAINT fk_invS_producto FOREIGN KEY (cod_pro) REFERENCES producto(cod_pro),
  CONSTRAINT fk_invS_almacen FOREIGN KEY (cod_almacen) REFERENCES almacen(cod_almacen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE movimiento_inventario (
  cod_movimiento      VARCHAR(20) PRIMARY KEY,
  tipo_movimiento     VARCHAR(30) NOT NULL,
  fecha               DATETIME DEFAULT CURRENT_TIMESTAMP,
  cod_almacen_origen  VARCHAR(20),
  cod_almacen_destino VARCHAR(20),
  cod_empleado_resp   VARCHAR(20),
  documento_tipo      VARCHAR(20),
  documento_ref       VARCHAR(30),
  observaciones       TEXT,
  CONSTRAINT fk_mov_almacen_o FOREIGN KEY (cod_almacen_origen) REFERENCES almacen(cod_almacen),
  CONSTRAINT fk_mov_almacen_d FOREIGN KEY (cod_almacen_destino) REFERENCES almacen(cod_almacen),
  CONSTRAINT fk_mov_empleado FOREIGN KEY (cod_empleado_resp) REFERENCES empleado(cod_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE movimiento_detalle (
  id_detalle        INT AUTO_INCREMENT PRIMARY KEY,
  cod_movimiento    VARCHAR(20) NOT NULL,
  cod_pro           VARCHAR(30) NOT NULL,
  id_unidad         INT,
  cantidad          DECIMAL(12,2) NOT NULL DEFAULT 1,
  precio_unitario   DECIMAL(12,2),
  estado_entrega    VARCHAR(20),
  estado_devolucion VARCHAR(20),
  devuelto          TINYINT(1) DEFAULT 0,
  observaciones     TEXT,
  CONSTRAINT fk_movD_mov FOREIGN KEY (cod_movimiento) REFERENCES movimiento_inventario(cod_movimiento) ON DELETE CASCADE,
  CONSTRAINT fk_movD_producto FOREIGN KEY (cod_pro) REFERENCES producto(cod_pro),
  CONSTRAINT fk_movD_unidad FOREIGN KEY (id_unidad) REFERENCES inventario_unidad(id_unidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 10. DOCUMENTOS Y PRÉSTAMOS
-- ============================================================

CREATE TABLE documento_inventario (
  cod_doc              VARCHAR(20) PRIMARY KEY,
  tipo                 VARCHAR(5) NOT NULL,
  numero               VARCHAR(30) NOT NULL,
  fecha                DATE NOT NULL,
  proveedor            VARCHAR(150),
  departamento         VARCHAR(150),
  destino_a            VARCHAR(200),
  grupo                VARCHAR(150),
  cod_empleado_entrega VARCHAR(20),
  recibido_por         VARCHAR(150),
  observaciones        TEXT,
  total                DECIMAL(12,2),
  created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_doc_tipo_num (tipo, numero),
  CONSTRAINT fk_doc_empleado FOREIGN KEY (cod_empleado_entrega) REFERENCES empleado(cod_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE documento_detalle (
  id_detalle       INT AUTO_INCREMENT PRIMARY KEY,
  cod_doc          VARCHAR(20) NOT NULL,
  cod_pro          VARCHAR(30),
  id_unidad        INT,
  descripcion      TEXT,
  cantidad         DECIMAL(12,2) NOT NULL,
  nota_observacion TEXT,
  estado_articulo  INT,
  devuelto         TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_docD_doc FOREIGN KEY (cod_doc) REFERENCES documento_inventario(cod_doc) ON DELETE CASCADE,
  CONSTRAINT fk_docD_producto FOREIGN KEY (cod_pro) REFERENCES producto(cod_pro),
  CONSTRAINT fk_docD_unidad FOREIGN KEY (id_unidad) REFERENCES inventario_unidad(id_unidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE prestamo_concesion (
  cod_prestamo       VARCHAR(20) PRIMARY KEY,
  tipo               VARCHAR(20) NOT NULL,
  cod_empleado       VARCHAR(20),
  cod_persona        VARCHAR(20),
  fecha_prestamo     DATE NOT NULL,
  fecha_dev_esperada DATE,
  fecha_dev_real     DATE,
  estado             VARCHAR(20) DEFAULT 'ACTIVO',
  cod_doc_salida     VARCHAR(20),
  observaciones      TEXT,
  CONSTRAINT fk_prestamo_empleado FOREIGN KEY (cod_empleado) REFERENCES empleado(cod_empleado),
  CONSTRAINT fk_prestamo_persona FOREIGN KEY (cod_persona) REFERENCES persona(cod_persona),
  CONSTRAINT fk_prestamo_doc FOREIGN KEY (cod_doc_salida) REFERENCES documento_inventario(cod_doc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE prestamo_detalle (
  id_detalle        INT AUTO_INCREMENT PRIMARY KEY,
  cod_prestamo      VARCHAR(20) NOT NULL,
  cod_pro           VARCHAR(30) NOT NULL,
  id_unidad         INT,
  cantidad          DECIMAL(12,2) NOT NULL DEFAULT 1,
  estado_entrega    VARCHAR(20),
  estado_devolucion VARCHAR(20),
  devuelto          TINYINT(1) DEFAULT 0,
  observaciones     TEXT,
  CONSTRAINT fk_pd_prestamo FOREIGN KEY (cod_prestamo) REFERENCES prestamo_concesion(cod_prestamo) ON DELETE CASCADE,
  CONSTRAINT fk_pd_producto FOREIGN KEY (cod_pro) REFERENCES producto(cod_pro),
  CONSTRAINT fk_pd_unidad FOREIGN KEY (id_unidad) REFERENCES inventario_unidad(id_unidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 11. CHAT Y NOTIFICACIONES
-- ============================================================

CREATE TABLE conversacion (
  cod_conversacion   INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario_cliente INT NOT NULL,
  id_usuario_asesor  INT,
  asunto             VARCHAR(200),
  fecha_inicio       DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_ultimo_msg   DATETIME,
  estado             VARCHAR(20) DEFAULT 'ABIERTA',
  CONSTRAINT fk_conv_cliente FOREIGN KEY (id_usuario_cliente) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_conv_asesor FOREIGN KEY (id_usuario_asesor) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conversacion_participante (
  cod_conversacion INT NOT NULL,
  id_usuario       INT NOT NULL,
  rol_en_chat      VARCHAR(30),
  PRIMARY KEY (cod_conversacion, id_usuario),
  CONSTRAINT fk_cp_conversacion FOREIGN KEY (cod_conversacion) REFERENCES conversacion(cod_conversacion) ON DELETE CASCADE,
  CONSTRAINT fk_cp_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mensaje (
  cod_mensaje      INT AUTO_INCREMENT PRIMARY KEY,
  cod_conversacion INT NOT NULL,
  id_remitente     INT,
  contenido        TEXT,
  tipo             VARCHAR(20) DEFAULT 'TEXTO',
  fecha_envio      DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_conversacion FOREIGN KEY (cod_conversacion) REFERENCES conversacion(cod_conversacion) ON DELETE CASCADE,
  CONSTRAINT fk_msg_remitente FOREIGN KEY (id_remitente) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mensaje_adjunto (
  cod_adjunto INT AUTO_INCREMENT PRIMARY KEY,
  cod_mensaje INT NOT NULL,
  url         VARCHAR(300) NOT NULL,
  tipo        VARCHAR(30),
  nombre      VARCHAR(200),
  CONSTRAINT fk_adj_mensaje FOREIGN KEY (cod_mensaje) REFERENCES mensaje(cod_mensaje) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mensaje_lectura (
  cod_mensaje   INT NOT NULL,
  id_usuario    INT NOT NULL,
  fecha_lectura DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cod_mensaje, id_usuario),
  CONSTRAINT fk_ml_mensaje FOREIGN KEY (cod_mensaje) REFERENCES mensaje(cod_mensaje) ON DELETE CASCADE,
  CONSTRAINT fk_ml_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asignacion_asesor (
  cod_asignacion   INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario_cli   INT,
  id_usuario_ase   INT,
  fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  activa           TINYINT(1) DEFAULT 1,
  CONSTRAINT fk_asig_cli FOREIGN KEY (id_usuario_cli) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_asig_ase FOREIGN KEY (id_usuario_ase) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notificacion (
  cod_notificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario       INT,
  titulo           VARCHAR(200),
  contenido        TEXT,
  tipo             VARCHAR(30),
  leida            TINYINT(1) DEFAULT 0,
  fecha            DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE plantilla_mensaje (
  cod_plantilla INT AUTO_INCREMENT PRIMARY KEY,
  titulo        VARCHAR(150),
  contenido     TEXT NOT NULL,
  categoria     VARCHAR(50),
  activa        TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 12. ACTIVIDAD, TESTIMONIOS Y AUDITORÍA
-- ============================================================

CREATE TABLE actividad_cliente (
  id_actividad   INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario     INT,
  cod_cliente    VARCHAR(20),
  tipo_actividad VARCHAR(50) NOT NULL,
  entidad        VARCHAR(50),
  entidad_id     VARCHAR(30),
  metadata       JSON,
  fecha          DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_act_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_act_cliente FOREIGN KEY (cod_cliente) REFERENCES cliente(cod_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE viaje_cliente_historial (
  id_historial INT AUTO_INCREMENT PRIMARY KEY,
  cod_cliente  VARCHAR(20) NOT NULL,
  cod_viaje    VARCHAR(20),
  cod_reserva  VARCHAR(20),
  estado       VARCHAR(30),
  fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
  comentario   TEXT,
  CONSTRAINT fk_vch_cliente FOREIGN KEY (cod_cliente) REFERENCES cliente(cod_cliente),
  CONSTRAINT fk_vch_viaje FOREIGN KEY (cod_viaje) REFERENCES viaje_confirmado(cod_viaje),
  CONSTRAINT fk_vch_reserva FOREIGN KEY (cod_reserva) REFERENCES reserva(cod_reserva)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE testimonio (
  cod_testimonio INT AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(150),
  contenido      TEXT NOT NULL,
  calificacion   INT,
  fecha          DATE,
  publicado      TINYINT(1) DEFAULT 0,
  cod_cliente    VARCHAR(20),
  CONSTRAINT fk_test_cliente FOREIGN KEY (cod_cliente) REFERENCES cliente(cod_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE auditoria (
  id_auditoria  INT AUTO_INCREMENT PRIMARY KEY,
  tabla         VARCHAR(80),
  registro_id   VARCHAR(50),
  accion        VARCHAR(20),
  id_usuario    INT,
  fecha         DATETIME DEFAULT CURRENT_TIMESTAMP,
  datos_antes   JSON,
  datos_despues JSON,
  CONSTRAINT fk_aud_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE configuracion (
  clave       VARCHAR(80) PRIMARY KEY,
  valor       TEXT,
  descripcion TEXT,
  modulo      VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE log_sistema (
  id_log  INT AUTO_INCREMENT PRIMARY KEY,
  nivel   VARCHAR(20),
  mensaje TEXT,
  modulo  VARCHAR(80),
  fecha   DATETIME DEFAULT CURRENT_TIMESTAMP,
  detalle JSON
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE archivo (
  id_archivo   INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(200) NOT NULL,
  ruta         VARCHAR(300) NOT NULL,
  tipo_mime    VARCHAR(100),
  tamano_bytes BIGINT,
  subido_por   INT,
  fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_arch_usuario FOREIGN KEY (subido_por) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 13. DATOS INICIALES
-- ============================================================

-- Roles definitivos
INSERT INTO rol (cod_rol, des_rol, descripcion) VALUES
('ROL-001', 'ADMIN',           'Control total del sistema'),
('ROL-002', 'ADMINISTRATIVO',  'Personal interno de Thaki Voyage'),
('ROL-003', 'CLIENTE',         'Usuario externo registrado');

-- Permisos
INSERT INTO permiso (cod_permiso, des_permiso, modulo) VALUES
('PERM_VER_DASHBOARD',        'Acceder al dashboard',                      'dashboard'),
('PERM_GESTIONAR_USUARIOS',   'Crear, editar y eliminar usuarios',         'usuarios'),
('PERM_GESTIONAR_INVENTARIO', 'Gestionar productos, stock y movimientos', 'inventario'),
('PERM_GESTIONAR_RESERVAS',   'Gestionar reservas y pagos',                'reservas'),
('PERM_GESTIONAR_VIAJES',     'Diseñar y enviar propuestas de viaje',      'viajes'),
('PERM_USAR_CHAT',            'Enviar mensajes en el chat',                'chat'),
('PERM_VER_REPORTES',         'Acceder a reportes',                        'reportes'),
('PERM_VER_SOLICITUDES',      'Ver solicitudes de viaje de clientes',      'viajes');

-- Relación Rol-Permiso
INSERT INTO rol_permiso (cod_rol, cod_permiso) VALUES
('ROL-001', 'PERM_VER_DASHBOARD'), ('ROL-001', 'PERM_GESTIONAR_USUARIOS'),
('ROL-001', 'PERM_GESTIONAR_INVENTARIO'), ('ROL-001', 'PERM_GESTIONAR_RESERVAS'),
('ROL-001', 'PERM_GESTIONAR_VIAJES'), ('ROL-001', 'PERM_USAR_CHAT'),
('ROL-001', 'PERM_VER_REPORTES'), ('ROL-001', 'PERM_VER_SOLICITUDES'),
('ROL-002', 'PERM_VER_DASHBOARD'), ('ROL-002', 'PERM_GESTIONAR_INVENTARIO'),
('ROL-002', 'PERM_GESTIONAR_RESERVAS'), ('ROL-002', 'PERM_GESTIONAR_VIAJES'),
('ROL-002', 'PERM_USAR_CHAT'), ('ROL-002', 'PERM_VER_REPORTES'),
('ROL-002', 'PERM_VER_SOLICITUDES'),
('ROL-003', 'PERM_VER_DASHBOARD'), ('ROL-003', 'PERM_USAR_CHAT'),
('ROL-003', 'PERM_VER_SOLICITUDES');

-- Auth Providers
INSERT INTO auth_provider (cod_provider, nombre) VALUES
('GOOGLE', 'Google'), ('LOCAL', 'Local'), ('FACEBOOK', 'Facebook');

-- Cargos
INSERT INTO cargo (cod_cargo, des_cargo, descripcion) VALUES
('CAR-001', 'Gerente General',         'Máxima autoridad de la empresa'),
('CAR-002', 'Encargado de Almacén',    'Responsable del inventario'),
('CAR-003', 'Asesor de Viajes',        'Diseña las propuestas para clientes'),
('CAR-004', 'Guía de Turismo',         'Acompaña a los grupos en los viajes'),
('CAR-005', 'Conductor',               'Conduce los vehículos de la empresa'),
('CAR-006', 'Personal Administrativo', 'Apoyo administrativo');

-- Unidades de Medida
INSERT INTO unidad_medida (cod_um, des_um, abreviatura) VALUES
('UM-001', 'Unidad', 'u'), ('UM-002', 'Caja', 'cja'),
('UM-003', 'Metro', 'm'), ('UM-004', 'Litro', 'L'), ('UM-005', 'Kilogramo', 'kg');

-- Categorías de Destino
INSERT INTO categoria_destino (cod_cat_destino, des_cat_destino, descripcion) VALUES
('CATD-001', 'Aventura', 'Trekking y montaña'), ('CATD-002', 'Cultural', 'Histórico cultural'),
('CATD-003', 'Gastronómico', 'Rutas gastronómicas'), ('CATD-004', 'Ecológico', 'Naturaleza');

-- Clases de Producto
INSERT INTO clase_producto (cod_clase, des_clase, es_serializado, es_consumible, tipo_inv) VALUES
('CL-001', 'BOTELLON DE OXIGENO', 1, 0, 'ALPINISMO'), ('CL-002', 'BOTELLA DE AGUA', 0, 0, 'ALPINISMO'),
('CL-003', 'BICICLETA', 1, 0, 'ALPINISMO'), ('CL-004', 'CARPA', 1, 0, 'ALPINISMO'),
('CL-005', 'COLCHONETA', 1, 0, 'ALPINISMO'), ('CL-006', 'CABINA DE DUCHA', 1, 0, 'ALPINISMO'),
('CL-007', 'CAMARA HIPERBARICA', 1, 0, 'ALPINISMO'), ('CL-008', 'MESA', 1, 0, 'ALPINISMO'),
('CL-009', 'SILLA', 1, 0, 'ALPINISMO'), ('CL-010', 'SLEEPING', 1, 0, 'ALPINISMO'),
('CL-011', 'SACO DE DORMIR', 1, 0, 'ALPINISMO'), ('CL-012', 'PALA', 1, 0, 'ALPINISMO'),
('CL-013', 'MASCARA DE OXIGENO', 0, 1, 'ALPINISMO'), ('CL-014', 'CONTENEDOR', 1, 0, 'ALPINISMO'),
('CL-015', 'TELEFONO SATELITAL', 1, 0, 'ALPINISMO'), ('CL-016', 'CALENTADOR DE AGUA', 1, 0, 'COCINA'),
('CL-017', 'MANOMETRO', 1, 0, 'ALPINISMO'), ('CL-018', 'OXIGENO SUPLETORIO', 0, 1, 'ALPINISMO'),
('CL-019', 'CALDERA', 1, 0, 'COCINA'), ('CL-020', 'OLLAS', 1, 0, 'COCINA'),
('CL-021', 'COCINA', 1, 0, 'COCINA'), ('CL-022', 'CONSERVADORA', 1, 0, 'COCINA'),
('CL-023', 'GARRAFA DE GAS', 1, 0, 'COCINA'), ('CL-024', 'GALON DE AGUA', 1, 0, 'COCINA'),
('CL-025', 'TERMO', 1, 0, 'COCINA'), ('CL-026', 'VAJILLA', 0, 0, 'COCINA'),
('CL-027', 'CUBIERTOS', 0, 0, 'COCINA'), ('CL-028', 'FLORERO', 1, 0, 'EVENTOS'),
('CL-029', 'CANASTA DE MIMBRE', 1, 0, 'EVENTOS'), ('CL-030', 'COJIN', 1, 0, 'EVENTOS'),
('CL-031', 'MANTEL', 1, 0, 'EVENTOS'), ('CL-032', 'CARPA EVENTO', 1, 0, 'EVENTOS');

-- Marcas
INSERT INTO marca (cod_mar, des_mar) VALUES
('M-0001', 'DOITE'), ('M-0002', 'QUECHUA'), ('M-0003', 'THE NORTH FACE'),
('M-0004', 'KING CAMP'), ('M-0005', 'KAMA'), ('M-0006', 'GRUNHOF'),
('M-0007', 'COLEMAN'), ('M-0008', 'MOMAMI'), ('M-0009', 'PELICAN'),
('M-0010', 'ALTUS'), ('M-0011', 'NATUREHIKE'), ('M-0012', 'TRULY TOOLS');

-- Almacén
INSERT INTO almacen (cod_almacen, nombre, ubicacion, estado) VALUES
('ALM-001', 'Almacén Principal', 'Oficina central de Thaki Voyage', 'ACTIVO');

-- Empresa
INSERT INTO empresa (cod_empresa, nombre, slogan, direccion, ciudad, pais, telefono, email, sitio_web) VALUES
('EMP-001', 'THAKI VOYAGE', 'Turismo personalizado de altura',
 'La Paz, Bolivia', 'La Paz', 'Bolivia', '+591 2 1234567',
 'info@thakivoyage.com', 'www.thakivoyage.com');

-- Configuración
INSERT INTO configuracion (clave, valor, descripcion, modulo) VALUES
('MONEDA_DEFAULT',   'BOB',              'Moneda por defecto',     'general'),
('IDIOMA_DEFAULT',   'es',               'Idioma por defecto',     'general'),
('ZONA_HORARIA',     'America/La_Paz',   'Zona horaria',           'general'),
('GOOGLE_CLIENT_ID', '',                 'Google OAuth Client ID', 'auth');