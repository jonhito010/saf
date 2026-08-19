-- SAF — Sistema de Autotransporte Federal
-- Schema + Seed Data
-- Fechas relativas a: 2026-03-30

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `empresas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(255) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `domicilio` text,
  `telefono` varchar(20) DEFAULT NULL,
  `permiso_sict` varchar(50) DEFAULT NULL,
  `permiso_vencimiento` date DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vehiculos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int unsigned NOT NULL DEFAULT 1,
  `placa` varchar(20) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `anio` int DEFAULT NULL,
  `numero_serie` varchar(50) DEFAULT NULL,
  `numero_motor` varchar(50) DEFAULT NULL,
  `tipo_carga` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `operadores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int unsigned NOT NULL DEFAULT 1,
  `nombre` varchar(255) NOT NULL,
  `apellido_paterno` varchar(80) DEFAULT NULL,
  `apellido_materno` varchar(80) DEFAULT NULL,
  `curp` varchar(18) DEFAULT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `num_licencia` varchar(30) DEFAULT NULL,
  `num_reg_id_trib_figura` varchar(40) DEFAULT NULL,
  `residencia_fiscal_figura` varchar(3) DEFAULT NULL,
  `domicilio_calle` varchar(255) DEFAULT NULL,
  `domicilio_numero_exterior` varchar(50) DEFAULT NULL,
  `domicilio_numero_interior` varchar(50) DEFAULT NULL,
  `domicilio_colonia` varchar(120) DEFAULT NULL,
  `domicilio_localidad` varchar(120) DEFAULT NULL,
  `domicilio_referencia` varchar(255) DEFAULT NULL,
  `domicilio_municipio` varchar(120) DEFAULT NULL,
  `domicilio_estado` varchar(3) DEFAULT NULL,
  `domicilio_pais` varchar(3) DEFAULT 'MEX',
  `domicilio_codigo_postal` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `estatus` varchar(10) NOT NULL DEFAULT 'activo',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entidad_tipo` enum('vehiculo','operador','empresa') NOT NULL,
  `entidad_id` int unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estatus` enum('vigente','por_vencer','vencido','faltante') NOT NULL DEFAULT 'vigente',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_entidad` (`entidad_tipo`,`entidad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mantenimientos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int unsigned NOT NULL,
  `fecha` date DEFAULT NULL,
  `tipo` enum('preventivo','correctivo') NOT NULL DEFAULT 'preventivo',
  `descripcion` text,
  `kilometraje` int DEFAULT NULL,
  `taller` varchar(255) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bajas_vehiculares` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int unsigned NOT NULL,
  `motivo` enum('siniestro','reemplazo','venta','otro') DEFAULT NULL,
  `fecha_baja` date DEFAULT NULL,
  `folio_sict` varchar(50) DEFAULT NULL,
  `placas_devueltas` tinyint(1) NOT NULL DEFAULT 0,
  `paso_actual` tinyint unsigned NOT NULL DEFAULT 1,
  `notas` text,
  `estatus` enum('en_proceso','completado','cancelado') NOT NULL DEFAULT 'en_proceso',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `alertas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('critico','advertencia','info') NOT NULL DEFAULT 'info',
  `titulo` varchar(255) DEFAULT NULL,
  `descripcion` text,
  `entidad_tipo` enum('vehiculo','operador','empresa') DEFAULT NULL,
  `entidad_id` int unsigned DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo facturación (SAT); nombres de columna en camelCase alineados con la app
CREATE TABLE IF NOT EXISTS `servicios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(512) NOT NULL,
  `numeroIdentificacion` varchar(120) DEFAULT NULL,
  `claveSAT` varchar(16) DEFAULT NULL,
  `precio` decimal(18,6) DEFAULT NULL,
  `unidadSAT` varchar(10) DEFAULT NULL,
  `objetoImp` varchar(4) DEFAULT NULL,
  `eliminado` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_servicios_eliminado` (`eliminado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clientes receptores (CFDI)
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `rfc` varchar(13) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `regimenFiscal` varchar(5) DEFAULT NULL,
  `usoCFDI` varchar(5) DEFAULT NULL,
  `domicilioFiscal` varchar(10) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `direccionCompleta` varchar(512) DEFAULT NULL,
  `eliminado` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_clientes_eliminado` (`eliminado`),
  KEY `idx_clientes_rfc` (`rfc`),
  KEY `idx_clientes_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comprobantes emitidos (CFDI)
CREATE TABLE IF NOT EXISTS `comprobantes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombreEmisor` varchar(255) DEFAULT NULL,
  `rfcEmisor` varchar(13) DEFAULT NULL,
  `nombreReceptor` varchar(255) DEFAULT NULL,
  `rfcReceptor` varchar(13) DEFAULT NULL,
  `uuid` varchar(45) DEFAULT NULL,
  `serie` varchar(45) DEFAULT NULL,
  `folio` varchar(45) DEFAULT NULL,
  `fechaEmision` datetime DEFAULT NULL,
  `subtotal` varchar(30) DEFAULT NULL,
  `descuento` varchar(30) DEFAULT NULL,
  `total` varchar(30) DEFAULT NULL,
  `estatus` int DEFAULT NULL,
  `xml` longtext,
  `pdf` longtext,
  `formaPago` varchar(5) DEFAULT NULL,
  `llevaCartaPorte` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = incluye complemento Carta Porte',
  `user_id` int DEFAULT NULL,
  `enviado` tinyint(1) DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comprobantes_uuid` (`uuid`),
  KEY `idx_comprobantes_fecha` (`fechaEmision`),
  KEY `idx_comprobantes_carta_porte` (`llevaCartaPorte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── SEED DATA ───────────────────────────────────────────────────────────────

INSERT INTO `empresas` (`id`, `razon_social`, `rfc`, `domicilio`, `telefono`, `permiso_sict`, `permiso_vencimiento`, `created`, `modified`) VALUES
(1, 'Transportes Norteño S.A. de C.V.', 'TNO940312AB4', 'Av. Industrial 2240, Parque Logístico Norte\nMonterrey, N.L. C.P. 64500', '+52 81 8000-1234', 'SICT-TFF-A-00234-2022', '2026-05-29', NOW(), NOW());

INSERT INTO `vehiculos` (`id`, `empresa_id`, `placa`, `marca`, `modelo`, `anio`, `numero_serie`, `numero_motor`, `tipo_carga`, `activo`, `created`, `modified`) VALUES
(1, 1, 'ABC-123-D', 'Kenworth',      'T680',      2022, '1XKWDB0X6NJ123456', 'PX-7-22034A', 'Carga general',   1, NOW(), NOW()),
(2, 1, 'XYZ-987-F', 'Freightliner',  'Cascadia',  2021, '3AKJGLD57FSGH123',  NULL,          'Carga general',   1, NOW(), NOW()),
(3, 1, 'MNO-456-B', 'International', 'LT',        2020, '3HSDXAPR4LN5123',   NULL,          'Mat. peligroso',  1, NOW(), NOW()),
(4, 1, 'PQR-321-C', 'Volvo',         'FH',        2023, '4V4NC9EH5PN2123',   NULL,          'Voluminosos',     1, NOW(), NOW()),
(5, 1, 'STU-654-A', 'Peterbilt',     '579',       2022, '1XPBDP0X2ND7123',   NULL,          'Carga general',   1, NOW(), NOW());

INSERT INTO `operadores` (`id`, `empresa_id`, `nombre`, `curp`, `telefono`, `activo`, `created`, `modified`) VALUES
(1, 1, 'Jorge Ramírez Morales',  'RAMJ820514HNLMRR04', '+52 81 1111-0001', 1, NOW(), NOW()),
(2, 1, 'Miguel González Torres', 'GOTM751203HNLLRG08', '+52 81 1111-0002', 1, NOW(), NOW()),
(3, 1, 'Carlos Flores Juárez',   'FLJC900814HNTLRL02', '+52 81 1111-0003', 1, NOW(), NOW()),
(4, 1, 'Andrés Reyes Sánchez',   'RESA881120HNLYND07', '+52 81 1111-0004', 1, NOW(), NOW()),
(5, 1, 'Luis Hernández Vega',    'HEVL930528HNTRGR06', '+52 81 1111-0005', 1, NOW(), NOW());

-- Documentos Empresa
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('empresa', 1, 'Acta constitutiva',    'acta_constitutiva', NULL,         'vigente',    NOW(), NOW()),
('empresa', 1, 'Acta de asamblea 2025','actas_asamblea',    NULL,         'vigente',    NOW(), NOW()),
('empresa', 1, 'Permiso SICT',         'permiso_sict',      '2026-05-29', 'por_vencer', NOW(), NOW());

-- Documentos Vehículo 1 (ABC-123-D) — póliza vencida, verificación por vencer
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('vehiculo', 1, 'Tarjeta de circulación',   'tarjeta_circulacion', '2026-09-15', 'vigente',    NOW(), NOW()),
('vehiculo', 1, 'Alta vehicular',           'alta_vehicular',      NULL,         'vigente',    NOW(), NOW()),
('vehiculo', 1, 'Póliza de seguro',         'poliza_seguro',       '2026-03-25', 'vencido',    NOW(), NOW()),
('vehiculo', 1, 'Inspección físico-mec.',   'inspeccion_fisica',   '2027-01-08', 'vigente',    NOW(), NOW()),
('vehiculo', 1, 'Verificación emisiones',   'verificacion_emision','2026-04-17', 'por_vencer', NOW(), NOW());

-- Documentos Vehículo 2 (XYZ-987-F) — verificación por vencer
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('vehiculo', 2, 'Tarjeta de circulación',   'tarjeta_circulacion', '2026-10-01', 'vigente',    NOW(), NOW()),
('vehiculo', 2, 'Alta vehicular',           'alta_vehicular',      NULL,         'vigente',    NOW(), NOW()),
('vehiculo', 2, 'Póliza de seguro',         'poliza_seguro',       '2026-11-15', 'vigente',    NOW(), NOW()),
('vehiculo', 2, 'Inspección físico-mec.',   'inspeccion_fisica',   '2026-12-20', 'vigente',    NOW(), NOW()),
('vehiculo', 2, 'Verificación emisiones',   'verificacion_emision','2026-04-17', 'por_vencer', NOW(), NOW());

-- Documentos Vehículo 3 (MNO-456-B) — inspección por vencer
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('vehiculo', 3, 'Tarjeta de circulación',   'tarjeta_circulacion', '2026-08-01', 'vigente',    NOW(), NOW()),
('vehiculo', 3, 'Alta vehicular',           'alta_vehicular',      NULL,         'vigente',    NOW(), NOW()),
('vehiculo', 3, 'Póliza de seguro',         'poliza_seguro',       '2026-09-10', 'vigente',    NOW(), NOW()),
('vehiculo', 3, 'Inspección físico-mec.',   'inspeccion_fisica',   '2026-04-21', 'por_vencer', NOW(), NOW()),
('vehiculo', 3, 'Verificación emisiones',   'verificacion_emision','2026-07-30', 'vigente',    NOW(), NOW());

-- Documentos Vehículo 4 (PQR-321-C) — todo vigente
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('vehiculo', 4, 'Tarjeta de circulación',   'tarjeta_circulacion', '2026-11-01', 'vigente', NOW(), NOW()),
('vehiculo', 4, 'Alta vehicular',           'alta_vehicular',      NULL,         'vigente', NOW(), NOW()),
('vehiculo', 4, 'Póliza de seguro',         'poliza_seguro',       '2026-10-15', 'vigente', NOW(), NOW()),
('vehiculo', 4, 'Inspección físico-mec.',   'inspeccion_fisica',   '2027-02-20', 'vigente', NOW(), NOW()),
('vehiculo', 4, 'Verificación emisiones',   'verificacion_emision','2026-08-05', 'vigente', NOW(), NOW());

-- Documentos Vehículo 5 (STU-654-A) — todo vigente
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('vehiculo', 5, 'Tarjeta de circulación',   'tarjeta_circulacion', '2026-12-30', 'vigente', NOW(), NOW()),
('vehiculo', 5, 'Alta vehicular',           'alta_vehicular',      NULL,         'vigente', NOW(), NOW()),
('vehiculo', 5, 'Póliza de seguro',         'poliza_seguro',       '2026-09-01', 'vigente', NOW(), NOW()),
('vehiculo', 5, 'Inspección físico-mec.',   'inspeccion_fisica',   '2027-03-10', 'vigente', NOW(), NOW()),
('vehiculo', 5, 'Verificación emisiones',   'verificacion_emision','2026-06-30', 'vigente', NOW(), NOW());

-- Documentos Operador 1 (Jorge Ramírez) — apto médico vencido
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('operador', 1, 'Licencia federal',        'licencia',            '2026-10-14', 'vigente', NOW(), NOW()),
('operador', 1, 'Apto médico',             'apto_medico',         '2026-03-28', 'vencido', NOW(), NOW()),
('operador', 1, 'INE',                     'ine',                 '2027-05-14', 'vigente', NOW(), NOW()),
('operador', 1, 'Acta de nacimiento',      'acta_nacimiento',     NULL,         'vigente', NOW(), NOW()),
('operador', 1, 'Comprobante domicilio',   'comp_domicilio',      '2026-09-01', 'vigente', NOW(), NOW());

-- Documentos Operador 2 (Miguel González) — licencia por vencer
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('operador', 2, 'Licencia federal',        'licencia',            '2026-04-21', 'por_vencer', NOW(), NOW()),
('operador', 2, 'Apto médico',             'apto_medico',         '2026-08-03', 'vigente',    NOW(), NOW()),
('operador', 2, 'INE',                     'ine',                 '2028-12-03', 'vigente',    NOW(), NOW()),
('operador', 2, 'Acta de nacimiento',      'acta_nacimiento',     NULL,         'vigente',    NOW(), NOW()),
('operador', 2, 'Comprobante domicilio',   'comp_domicilio',      '2026-10-15', 'vigente',    NOW(), NOW());

-- Documentos Operador 3 (Carlos Flores) — todo vigente
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('operador', 3, 'Licencia federal',        'licencia',            '2027-08-14', 'vigente', NOW(), NOW()),
('operador', 3, 'Apto médico',             'apto_medico',         '2026-07-10', 'vigente', NOW(), NOW()),
('operador', 3, 'INE',                     'ine',                 '2027-11-20', 'vigente', NOW(), NOW()),
('operador', 3, 'Acta de nacimiento',      'acta_nacimiento',     NULL,         'vigente', NOW(), NOW()),
('operador', 3, 'Comprobante domicilio',   'comp_domicilio',      '2026-11-01', 'vigente', NOW(), NOW());

-- Documentos Operador 4 (Andrés Reyes) — 1 faltante
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('operador', 4, 'Licencia federal',        'licencia',            '2026-11-20', 'vigente',  NOW(), NOW()),
('operador', 4, 'Apto médico',             'apto_medico',         '2026-09-05', 'vigente',  NOW(), NOW()),
('operador', 4, 'INE',                     'ine',                 '2026-12-01', 'vigente',  NOW(), NOW()),
('operador', 4, 'Acta de nacimiento',      'acta_nacimiento',     NULL,         'faltante', NOW(), NOW()),
('operador', 4, 'Comprobante domicilio',   'comp_domicilio',      '2026-08-20', 'vigente',  NOW(), NOW());

-- Documentos Operador 5 (Luis Hernández) — todo vigente
INSERT INTO `documentos` (`entidad_tipo`, `entidad_id`, `nombre`, `tipo`, `fecha_vencimiento`, `estatus`, `created`, `modified`) VALUES
('operador', 5, 'Licencia federal',        'licencia',            '2027-05-28', 'vigente', NOW(), NOW()),
('operador', 5, 'Apto médico',             'apto_medico',         '2026-06-15', 'vigente', NOW(), NOW()),
('operador', 5, 'INE',                     'ine',                 '2027-09-10', 'vigente', NOW(), NOW()),
('operador', 5, 'Acta de nacimiento',      'acta_nacimiento',     NULL,         'vigente', NOW(), NOW()),
('operador', 5, 'Comprobante domicilio',   'comp_domicilio',      '2026-12-28', 'vigente', NOW(), NOW());

-- Mantenimientos (vehículo 1)
INSERT INTO `mantenimientos` (`vehiculo_id`, `fecha`, `tipo`, `descripcion`, `kilometraje`, `taller`, `costo`, `created`, `modified`) VALUES
(1, '2026-02-15', 'preventivo',  'Cambio de aceite y filtros',         128450, 'Taller Norte MTY',  4200.00,  NOW(), NOW()),
(1, '2026-01-10', 'correctivo',  'Reparación sistema de frenos',       121300, 'Kenworth Servicio', 18500.00, NOW(), NOW()),
(1, '2025-10-05', 'preventivo',  'Revisión general 100k km',           100000, 'Taller Norte MTY',  12800.00, NOW(), NOW()),
(2, '2026-01-20', 'preventivo',  'Cambio de aceite',                   95000,  'Taller Sur MTY',    3800.00,  NOW(), NOW()),
(3, '2026-02-28', 'correctivo',  'Reparación compresor de aire',       110500, 'Int\'l Servicio',   22000.00, NOW(), NOW());

-- Bajas Vehiculares
INSERT INTO `bajas_vehiculares` (`vehiculo_id`, `motivo`, `fecha_baja`, `folio_sict`, `placas_devueltas`, `paso_actual`, `notas`, `estatus`, `created`, `modified`) VALUES
(1, 'siniestro',  '2026-03-28', NULL,          0, 3, 'Vehículo siniestrado en carretera Monterrey-Saltillo', 'en_proceso', NOW(), NOW()),
(6, 'reemplazo',  '2026-01-15', 'SICT-B-00891', 1, 5, NULL, 'completado', NOW(), NOW()),
(7, 'venta',      '2025-11-02', 'SICT-B-00754', 1, 5, NULL, 'completado', NOW(), NOW());

-- Alertas
INSERT INTO `alertas` (`tipo`, `titulo`, `descripcion`, `entidad_tipo`, `entidad_id`, `leida`, `created`, `modified`) VALUES
('critico',      'Póliza vencida — ABC-123-D',         'La póliza de seguro del vehículo ABC-123-D venció hace 5 días', 'vehiculo',  1, 0, DATE_SUB(NOW(), INTERVAL 5 DAY),  NOW()),
('critico',      'Apto médico vencido — J. Ramírez',   'El apto médico del operador Jorge Ramírez venció hace 2 días',  'operador',  1, 0, DATE_SUB(NOW(), INTERVAL 2 DAY),  NOW()),
('advertencia',  'Verificación por vencer — XYZ-987-F','La verificación de emisiones vence en 18 días',                 'vehiculo',  2, 0, DATE_SUB(NOW(), INTERVAL 1 DAY),  NOW()),
('advertencia',  'Licencia por vencer — M. González',  'La licencia federal de Miguel González vence en 22 días',       'operador',  2, 0, DATE_SUB(NOW(), INTERVAL 1 DAY),  NOW()),
('info',         'Permiso SICT renueva en 60 días',    'El permiso SICT de la empresa vence el 29/05/2026',             'empresa',   1, 0, NOW(),                            NOW());

SET FOREIGN_KEY_CHECKS = 1;
