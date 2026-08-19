-- =============================================================================
-- SAF · Bootstrap schema MySQL 8 (CakePHP 5.3 — esquema real)
-- Inferido desde Model/Table, Entity, controllers, patches y seed.sql
-- NO incluye tablas de patch_mejoras_v2 (alertas_reglas, notificaciones, etc.)
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── USUARIOS ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
  `rol_id` INT NOT NULL DEFAULT 3 COMMENT '1=Superadmin, 2=Consultor, 3=Usuario',
  `es_cuenta` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = cuenta titular',
  `nombre` VARCHAR(120) NOT NULL,
  `apellidos` VARCHAR(120) NULL DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_acceso` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  KEY `idx_usuarios_empresa` (`empresa_id`),
  KEY `idx_usuarios_rol` (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── EMPRESAS ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `empresas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `rfc` VARCHAR(13) NOT NULL,
  `domicilio` TEXT NULL,
  `colonia` VARCHAR(100) NULL DEFAULT NULL,
  `municipio` VARCHAR(100) NULL DEFAULT NULL,
  `estado` VARCHAR(100) NULL DEFAULT NULL,
  `cp` VARCHAR(5) NULL DEFAULT NULL,
  `telefono` VARCHAR(20) NULL DEFAULT NULL,
  `email` VARCHAR(150) NULL DEFAULT NULL,
  `numero_permiso_sict` VARCHAR(50) NULL DEFAULT NULL,
  `fecha_permiso_sict` DATE NULL DEFAULT NULL,
  `vencimiento_permiso_sict` DATE NULL DEFAULT NULL,
  `estatus` VARCHAR(20) NOT NULL DEFAULT 'activa' COMMENT 'activa|suspendida|baja',
  `transporte_materiales_peligrosos` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = requisitos MP/residuos',
  `regimen_fiscal` VARCHAR(5) NULL DEFAULT NULL,
  `lugar_expedicion` VARCHAR(5) NULL DEFAULT NULL,
  `serie_cfdi` VARCHAR(45) NULL DEFAULT NULL,
  `folio_cfdi` VARCHAR(45) NULL DEFAULT NULL,
  `folio_inicial` VARCHAR(15) NULL DEFAULT NULL,
  `folios_disponibles` INT NULL DEFAULT NULL,
  `pem_key` TEXT NULL,
  `pem_cer` TEXT NULL,
  `numero_certificado` VARCHAR(45) NULL DEFAULT NULL,
  `fecha_inicio_cert` DATE NULL DEFAULT NULL,
  `fecha_fin_cert` DATE NULL DEFAULT NULL,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresas_estatus` (`estatus`),
  KEY `idx_empresas_rfc` (`rfc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── USUARIO ↔ EMPRESAS (multi-tenant) ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuario_empresas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `es_propietario` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_empresa` (`usuario_id`, `empresa_id`),
  KEY `idx_usuario_empresas_empresa` (`empresa_id`),
  CONSTRAINT `fk_usuario_empresas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuario_empresas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TIPOS DE DOCUMENTO (estructura; datos en tipos_documento.sql) ───────────
CREATE TABLE IF NOT EXISTS `tipos_documento` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(60) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `ambito` ENUM('empresa','vehiculo','operador') NOT NULL,
  `categoria` VARCHAR(30) NOT NULL DEFAULT 'general',
  `descripcion` TEXT NULL,
  `retencion` VARCHAR(255) NULL DEFAULT NULL,
  `icono` VARCHAR(20) NULL DEFAULT NULL,
  `requerido_normativo` TINYINT(1) NOT NULL DEFAULT 0,
  `requerido_mp` TINYINT(1) NOT NULL DEFAULT 0,
  `requerido_cumplimiento` TINYINT(1) NOT NULL DEFAULT 0,
  `visible_formulario` TINYINT(1) NOT NULL DEFAULT 1,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipos_documento_ambito_codigo` (`ambito`, `codigo`),
  KEY `idx_tipos_ambito` (`ambito`, `activo`, `orden`),
  KEY `idx_tipos_categoria` (`categoria`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Catálogos operación (Carta Porte / tipo carga) ──────────────────────────
CREATE TABLE IF NOT EXISTS `catalogo_tipo_carga` (
  `codigo` VARCHAR(40) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `requiere_modalidad` TINYINT(1) NOT NULL DEFAULT 0,
  `es_material_peligroso` TINYINT(1) NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `catalogo_modalidad` (
  `codigo` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `catalogo_tipo_servicio` (
  `codigo` VARCHAR(80) NOT NULL,
  `modalidad_codigo` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`codigo`, `modalidad_codigo`),
  KEY `idx_cts_modalidad` (`modalidad_codigo`),
  CONSTRAINT `fk_cts_modalidad` FOREIGN KEY (`modalidad_codigo`) REFERENCES `catalogo_modalidad` (`codigo`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `catalogo_tipo_carga` (`codigo`, `nombre`, `requiere_modalidad`, `es_material_peligroso`, `activo`, `orden`) VALUES
('carga_general', 'Carga general', 1, 0, 1, 10),
('carga_especializada', 'Carga especializada', 1, 0, 1, 20),
('material_peligroso', 'Material peligroso', 0, 1, 1, 30),
('voluminosos', 'Voluminosos', 0, 0, 1, 40),
('fondos_y_valores', 'Fondos y valores', 0, 0, 1, 50)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `requiere_modalidad` = VALUES(`requiere_modalidad`),
  `es_material_peligroso` = VALUES(`es_material_peligroso`),
  `activo` = VALUES(`activo`),
  `orden` = VALUES(`orden`);

INSERT INTO `catalogo_modalidad` (`codigo`, `nombre`, `activo`, `orden`) VALUES
('AUTOTRANSPORTE FEDERAL', 'Transporte federal', 1, 10),
('TRANSPORTE PRIVADO', 'Transporte privado', 1, 20)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `activo` = VALUES(`activo`),
  `orden` = VALUES(`orden`);

INSERT INTO `catalogo_tipo_servicio` (`codigo`, `modalidad_codigo`, `nombre`, `activo`, `orden`) VALUES
('CARGA GENERAL', 'AUTOTRANSPORTE FEDERAL', 'Carga general', 1, 10),
('PAQUETERIA Y MENSAJERIA', 'AUTOTRANSPORTE FEDERAL', 'Paquetería y mensajería', 1, 20),
('ARRENDAMIENTO', 'AUTOTRANSPORTE FEDERAL', 'Arrendamiento', 1, 30),
('CARGA ESPECIALIZADA', 'AUTOTRANSPORTE FEDERAL', 'Carga especializada', 1, 40),
('AUTOTRANSPORTE FEDERAL', 'AUTOTRANSPORTE FEDERAL', 'Autotransporte federal', 1, 50)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `activo` = VALUES(`activo`),
  `orden` = VALUES(`orden`);

-- ─── VEHÍCULOS ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vehiculos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `marca` VARCHAR(60) NOT NULL,
  `modelo` VARCHAR(60) NOT NULL,
  `anio` INT NOT NULL,
  `numero_serie` VARCHAR(80) NOT NULL,
  `numero_motor` VARCHAR(80) NULL DEFAULT NULL,
  `placas` VARCHAR(20) NOT NULL,
  `tipo_carga` VARCHAR(40) NOT NULL,
  `modalidad` VARCHAR(80) NULL DEFAULT NULL,
  `tipo_servicio` VARCHAR(80) NULL DEFAULT NULL,
  `color` VARCHAR(40) NULL DEFAULT NULL,
  `peso_bruto_vehicular` DECIMAL(12,3) NULL DEFAULT NULL,
  `num_ejes` INT NULL DEFAULT NULL,
  `estatus` VARCHAR(20) NOT NULL DEFAULT 'activo' COMMENT 'activo|inactivo',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vehiculos_empresa` (`empresa_id`),
  KEY `idx_vehiculos_placas` (`placas`),
  KEY `idx_vehiculos_estatus` (`estatus`),
  CONSTRAINT `fk_vehiculos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── OPERADORES ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `operadores` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(80) NOT NULL,
  `apellido_paterno` VARCHAR(80) NOT NULL,
  `apellido_materno` VARCHAR(80) NULL DEFAULT NULL,
  `curp` VARCHAR(18) NULL DEFAULT NULL,
  `rfc` VARCHAR(13) NULL DEFAULT NULL,
  `num_licencia` VARCHAR(30) NULL DEFAULT NULL,
  `num_reg_id_trib_figura` VARCHAR(40) NULL DEFAULT NULL,
  `residencia_fiscal_figura` VARCHAR(3) NULL DEFAULT NULL,
  `domicilio_calle` VARCHAR(255) NULL DEFAULT NULL,
  `domicilio_numero_exterior` VARCHAR(50) NULL DEFAULT NULL,
  `domicilio_numero_interior` VARCHAR(50) NULL DEFAULT NULL,
  `domicilio_colonia` VARCHAR(120) NULL DEFAULT NULL,
  `domicilio_localidad` VARCHAR(120) NULL DEFAULT NULL,
  `domicilio_referencia` VARCHAR(255) NULL DEFAULT NULL,
  `domicilio_municipio` VARCHAR(120) NULL DEFAULT NULL,
  `domicilio_estado` VARCHAR(3) NULL DEFAULT NULL,
  `domicilio_pais` VARCHAR(3) NULL DEFAULT 'MEX',
  `domicilio_codigo_postal` VARCHAR(10) NULL DEFAULT NULL,
  `telefono` VARCHAR(10) NULL DEFAULT NULL,
  `email` VARCHAR(255) NULL DEFAULT NULL,
  `foto_ruta` VARCHAR(255) NULL DEFAULT NULL,
  `fecha_ingreso` DATE NULL DEFAULT NULL,
  `estatus` VARCHAR(20) NOT NULL DEFAULT 'activo' COMMENT 'activo|inactivo|suspendido|baja',
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_operadores_empresa` (`empresa_id`),
  KEY `idx_operadores_estatus` (`estatus`),
  CONSTRAINT `fk_operadores_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── DOCUMENTOS ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `documentos_empresa` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `tipo_documento` VARCHAR(60) NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `fecha_vencimiento` DATE NULL DEFAULT NULL,
  `estatus` VARCHAR(20) NOT NULL DEFAULT 'vigente' COMMENT 'vigente|por_vencer|vencido',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_empresa_empresa` (`empresa_id`),
  KEY `idx_doc_empresa_tipo` (`tipo_documento`),
  KEY `idx_doc_empresa_estatus` (`estatus`),
  CONSTRAINT `fk_doc_empresa_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documentos_vehiculo` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehiculo_id` INT UNSIGNED NOT NULL,
  `tipo_documento` VARCHAR(60) NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `fecha_emision` DATE NULL DEFAULT NULL,
  `fecha_vencimiento` DATE NULL DEFAULT NULL,
  `estatus` VARCHAR(20) NOT NULL DEFAULT 'vigente' COMMENT 'vigente|por_vencer|vencido',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_vehiculo_vehiculo` (`vehiculo_id`),
  KEY `idx_doc_vehiculo_tipo` (`tipo_documento`),
  KEY `idx_doc_vehiculo_estatus` (`estatus`),
  CONSTRAINT `fk_doc_vehiculo_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documentos_operador` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operador_id` INT UNSIGNED NOT NULL,
  `tipo_documento` VARCHAR(60) NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `fecha_vencimiento` DATE NULL DEFAULT NULL,
  `estatus` VARCHAR(20) NOT NULL DEFAULT 'vigente' COMMENT 'vigente|por_vencer|vencido|faltante',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_operador_operador` (`operador_id`),
  KEY `idx_doc_operador_tipo` (`tipo_documento`),
  KEY `idx_doc_operador_estatus` (`estatus`),
  CONSTRAINT `fk_doc_operador_operador` FOREIGN KEY (`operador_id`) REFERENCES `operadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ALERTAS DE CUMPLIMIENTO ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `alertas_cumplimiento` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entidad_tipo` VARCHAR(20) NOT NULL COMMENT 'empresa|vehiculo|operador',
  `entidad_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `tipo_alerta` VARCHAR(40) NOT NULL,
  `tipo_documento` VARCHAR(100) NULL DEFAULT NULL,
  `descripcion` VARCHAR(500) NULL DEFAULT NULL,
  `fecha_vencimiento` DATE NULL DEFAULT NULL,
  `dias_anticipacion` INT NULL DEFAULT NULL,
  `nivel_urgencia` VARCHAR(20) NOT NULL DEFAULT 'info' COMMENT 'info|advertencia|critico',
  `notificado` TINYINT(1) NOT NULL DEFAULT 0,
  `resuelto` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alertas_empresa` (`empresa_id`),
  KEY `idx_alertas_entidad` (`entidad_tipo`, `entidad_id`),
  KEY `idx_alertas_resuelto` (`resuelto`, `nivel_urgencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── MANTENIMIENTO (bitácora) ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `mantenimiento_bitacora` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehiculo_id` INT UNSIGNED NOT NULL,
  `fecha` DATE NOT NULL,
  `tipo_servicio` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `kilometraje` DECIMAL(12,2) NULL DEFAULT NULL,
  `taller` VARCHAR(200) NULL DEFAULT NULL,
  `responsable` VARCHAR(200) NULL DEFAULT NULL,
  `costo` DECIMAL(12,2) NULL DEFAULT NULL,
  `adjunto` VARCHAR(500) NULL DEFAULT NULL,
  `notas` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mantenimiento_vehiculo` (`vehiculo_id`),
  KEY `idx_mantenimiento_fecha` (`fecha`),
  CONSTRAINT `fk_mantenimiento_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── BAJAS VEHICULARES ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bajas_vehiculares` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehiculo_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `motivo_baja` VARCHAR(20) NOT NULL COMMENT 'siniestro|reemplazo|venta|robo|otro',
  `fecha_baja` DATE NULL DEFAULT NULL,
  `descripcion` TEXT NULL,
  `estatus_tramite` VARCHAR(30) NOT NULL DEFAULT 'iniciado',
  `placas_devueltas` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_devolucion_placas` DATE NULL DEFAULT NULL,
  `numero_folio_baja` VARCHAR(60) NULL DEFAULT NULL,
  `notas` VARCHAR(500) NULL DEFAULT NULL,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bajas_vehiculo` (`vehiculo_id`),
  KEY `idx_bajas_empresa` (`empresa_id`),
  KEY `idx_bajas_estatus` (`estatus_tramite`),
  CONSTRAINT `fk_bajas_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_bajas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CLIENTES / SERVICIOS (CFDI) ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
  `rfc` VARCHAR(13) NULL DEFAULT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `regimenFiscal` VARCHAR(5) NULL DEFAULT NULL,
  `usoCFDI` VARCHAR(5) NULL DEFAULT NULL,
  `domicilioFiscal` VARCHAR(10) NULL DEFAULT NULL,
  `correo` VARCHAR(255) NULL DEFAULT NULL,
  `direccionCompleta` VARCHAR(512) NULL DEFAULT NULL,
  `eliminado` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_clientes_empresa` (`empresa_id`),
  KEY `idx_clientes_eliminado` (`eliminado`),
  KEY `idx_clientes_rfc` (`rfc`),
  KEY `idx_clientes_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `servicios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
  `descripcion` VARCHAR(512) NOT NULL,
  `numeroIdentificacion` VARCHAR(120) NULL DEFAULT NULL,
  `claveSAT` VARCHAR(16) NULL DEFAULT NULL,
  `precio` DECIMAL(18,6) NULL DEFAULT NULL,
  `unidadSAT` VARCHAR(10) NULL DEFAULT NULL,
  `objetoImp` VARCHAR(4) NULL DEFAULT NULL,
  `eliminado` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_servicios_empresa` (`empresa_id`),
  KEY `idx_servicios_eliminado` (`eliminado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── COMPROBANTES (CFDI) ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `comprobantes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
  `nombreEmisor` VARCHAR(255) NULL DEFAULT NULL,
  `rfcEmisor` VARCHAR(13) NULL DEFAULT NULL,
  `nombreReceptor` VARCHAR(255) NULL DEFAULT NULL,
  `rfcReceptor` VARCHAR(13) NULL DEFAULT NULL,
  `uuid` VARCHAR(45) NULL DEFAULT NULL,
  `serie` VARCHAR(45) NULL DEFAULT NULL,
  `folio` VARCHAR(45) NULL DEFAULT NULL,
  `fechaEmision` DATETIME NULL DEFAULT NULL,
  `tipoComprobante` VARCHAR(5) NULL DEFAULT NULL,
  `anio` VARCHAR(4) NULL DEFAULT NULL,
  `mes` VARCHAR(2) NULL DEFAULT NULL,
  `subtotal` VARCHAR(30) NULL DEFAULT NULL,
  `descuento` VARCHAR(30) NULL DEFAULT NULL,
  `total` VARCHAR(30) NULL DEFAULT NULL,
  `estatus` INT NULL DEFAULT NULL,
  `xml` LONGTEXT NULL,
  `pdf` LONGTEXT NULL,
  `formaPago` VARCHAR(5) NULL DEFAULT NULL,
  `llevaCartaPorte` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = complemento Carta Porte',
  `user_id` INT NULL DEFAULT NULL,
  `enviado` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comprobantes_uuid` (`uuid`),
  KEY `idx_comprobantes_fecha` (`fechaEmision`),
  KEY `idx_comprobantes_empresa` (`empresa_id`),
  KEY `idx_comprobantes_carta_porte` (`llevaCartaPorte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── UBICACIONES (Carta Porte) ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ubicaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo_ubicacion` VARCHAR(10) NOT NULL COMMENT 'Origen|Destino',
  `id_ubicacion` VARCHAR(10) NOT NULL COMMENT 'OR/DE + 6 dígitos',
  `rfc` VARCHAR(13) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `calle` VARCHAR(255) NOT NULL,
  `numero_exterior` VARCHAR(50) NOT NULL,
  `numero_interior` VARCHAR(50) NULL DEFAULT NULL,
  `colonia` VARCHAR(10) NOT NULL,
  `municipio` VARCHAR(5) NOT NULL,
  `estado` VARCHAR(3) NOT NULL,
  `codigo_postal` VARCHAR(5) NOT NULL,
  `localidad` VARCHAR(10) NULL DEFAULT NULL,
  `pais` VARCHAR(3) NULL DEFAULT 'MEX',
  `referencia` VARCHAR(255) NULL DEFAULT NULL,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ubicaciones_clave` (`id_ubicacion`),
  KEY `idx_ubicaciones_tipo` (`tipo_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CATÁLOGO MERCANCÍAS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `catalogo_mercancias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bienes_transp` VARCHAR(8) NOT NULL COMMENT 'Clave SAT c_ClaveProdServCP',
  `descripcion` VARCHAR(255) NOT NULL,
  `clave_unidad` VARCHAR(3) NOT NULL COMMENT 'Clave SAT c_ClaveUnidad',
  `peso_en_kg` DECIMAL(12,3) NULL DEFAULT NULL,
  `material_peligroso` TINYINT(1) NOT NULL DEFAULT 0,
  `cve_material_peligroso` VARCHAR(4) NULL DEFAULT NULL,
  `embalaje` VARCHAR(4) NULL DEFAULT NULL,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bienes_transp` (`bienes_transp`),
  KEY `idx_descripcion` (`descripcion`(80))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo propio de mercancías para Carta Porte';

-- ─── Catálogos SAT auxiliares (facturación) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `series` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `serie` VARCHAR(20) NULL DEFAULT NULL,
  `folioIncial` INT NULL DEFAULT NULL,
  `folioActual` INT NULL DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `eliminado` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `unidades` (
  `id` VARCHAR(10) NOT NULL COMMENT 'Clave SAT c_ClaveUnidad',
  `nombre` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `claveprods` (
  `id` VARCHAR(16) NOT NULL COMMENT 'Clave SAT producto/servicio',
  `nombre` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED: usuarios demo + empresa demo
-- =============================================================================

INSERT INTO `usuarios` (
  `id`, `parent_id`, `empresa_id`, `rol_id`, `es_cuenta`,
  `nombre`, `apellidos`, `email`, `password_hash`, `activo`, `created_at`, `updated_at`
) VALUES
(
  1, NULL, NULL, 1, 1,
  'Super', 'Administrador', 'superadmin@saf.local',
  '$2y$12$.oHu7bQ7ZB4KdIXM9fff2.hRIn6J1ChUdapU795whJdcPse.z548W',
  1, NOW(), NOW()
),
(
  2, NULL, 1, 2, 1,
  'Consultor', 'Demo', 'consultor@saf.local',
  '$2y$12$.oHu7bQ7ZB4KdIXM9fff2.hRIn6J1ChUdapU795whJdcPse.z548W',
  1, NOW(), NOW()
);

INSERT INTO `empresas` (
  `id`, `nombre`, `rfc`, `domicilio`, `colonia`, `municipio`, `estado`, `cp`,
  `telefono`, `email`, `numero_permiso_sict`, `fecha_permiso_sict`, `vencimiento_permiso_sict`,
  `estatus`, `created_by`, `created_at`, `updated_at`
) VALUES (
  1, 'Transportes Demo S.A. de C.V.', 'TDE940312AB4',
  'Av. Industrial 2240', 'Parque Logístico Norte', 'Monterrey', 'Nuevo León', '64500',
  '+52 81 8000-1234', 'contacto@transportesdemo.mx',
  'SICT-TFF-A-00234-2022', '2022-03-01', '2026-05-29',
  'activa', 1, NOW(), NOW()
);

INSERT INTO `usuario_empresas` (`usuario_id`, `empresa_id`, `es_propietario`, `created_at`) VALUES
(2, 1, 1, NOW());

SET FOREIGN_KEY_CHECKS = 1;
