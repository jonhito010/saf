-- SAF: catálogos de selects + requisitos para % de cumplimiento por empresa
-- Idempotente (seguro re-ejecutar).

SET NAMES utf8mb4;

-- ── Empresa: flag MP ──────────────────────────────────────────────────────────
SET @col_mp := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'empresas'
    AND COLUMN_NAME = 'transporte_materiales_peligrosos'
);
SET @sql_mp := IF(
  @col_mp = 0,
  'ALTER TABLE empresas ADD COLUMN transporte_materiales_peligrosos TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = empresa sujeta a requisitos MP/residuos peligrosos'' AFTER estatus',
  'SELECT 1'
);
PREPARE stmt_mp FROM @sql_mp;
EXECUTE stmt_mp;
DEALLOCATE PREPARE stmt_mp;

-- Auto-marcar empresas que ya tienen unidades MP
UPDATE empresas e
SET e.transporte_materiales_peligrosos = 1
WHERE EXISTS (
  SELECT 1 FROM vehiculos v
  WHERE v.empresa_id = e.id
    AND v.tipo_carga = 'material_peligroso'
    AND v.estatus = 'activo'
);

-- ── Catálogo tipo de carga ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `catalogo_tipo_carga` (
  `codigo` VARCHAR(40) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `requiere_modalidad` TINYINT(1) NOT NULL DEFAULT 0,
  `es_material_peligroso` TINYINT(1) NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `catalogo_tipo_carga` (`codigo`, `nombre`, `requiere_modalidad`, `es_material_peligroso`, `activo`, `orden`) VALUES
('carga_general', 'Carga general', 1, 0, 1, 10),
('carga_especializada', 'Carga especializada', 1, 0, 1, 20),
('material_peligroso', 'Material peligroso', 0, 1, 1, 30),
('voluminosos', 'Voluminosos', 0, 0, 1, 40),
('fondos_y_valores', 'Fondos y valores', 0, 0, 1, 50)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  requiere_modalidad = VALUES(requiere_modalidad),
  es_material_peligroso = VALUES(es_material_peligroso),
  activo = VALUES(activo),
  orden = VALUES(orden);

-- ── Catálogo modalidad ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `catalogo_modalidad` (
  `codigo` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `catalogo_modalidad` (`codigo`, `nombre`, `activo`, `orden`) VALUES
('AUTOTRANSPORTE FEDERAL', 'Transporte federal', 1, 10),
('TRANSPORTE PRIVADO', 'Transporte privado', 1, 20)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  activo = VALUES(activo),
  orden = VALUES(orden);

-- ── Catálogo tipo de servicio (ligado a modalidad) ────────────────────────────
CREATE TABLE IF NOT EXISTS `catalogo_tipo_servicio` (
  `codigo` VARCHAR(80) NOT NULL,
  `modalidad_codigo` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`codigo`, `modalidad_codigo`),
  KEY `idx_cts_modalidad` (`modalidad_codigo`),
  CONSTRAINT `fk_cts_modalidad`
    FOREIGN KEY (`modalidad_codigo`) REFERENCES `catalogo_modalidad` (`codigo`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `catalogo_tipo_servicio` (`codigo`, `modalidad_codigo`, `nombre`, `activo`, `orden`) VALUES
('CARGA GENERAL', 'AUTOTRANSPORTE FEDERAL', 'Carga general', 1, 10),
('PAQUETERIA Y MENSAJERIA', 'AUTOTRANSPORTE FEDERAL', 'Paquetería y mensajería', 1, 20),
('ARRENDAMIENTO', 'AUTOTRANSPORTE FEDERAL', 'Arrendamiento', 1, 30),
('CARGA ESPECIALIZADA', 'AUTOTRANSPORTE FEDERAL', 'Carga especializada', 1, 40),
('AUTOTRANSPORTE FEDERAL', 'AUTOTRANSPORTE FEDERAL', 'Autotransporte federal', 1, 50)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  activo = VALUES(activo),
  orden = VALUES(orden);

-- ── Docs corporativos requeridos para % empresa ───────────────────────────────
UPDATE tipos_documento
SET requerido_cumplimiento = 1, updated_at = NOW()
WHERE ambito = 'empresa'
  AND categoria = 'corporativo'
  AND codigo IN (
    'acta_constitutiva',
    'poder_notarial_representante',
    'identificacion_representante_legal',
    'constancia_situacion_fiscal',
    'opinion_cumplimiento_sat',
    'comprobante_domicilio_fiscal',
    'registro_rnaf',
    'permiso_sict',
    'poliza_rc_autotransporte'
  );

-- Opcionales siguen en 0
UPDATE tipos_documento
SET requerido_cumplimiento = 0, updated_at = NOW()
WHERE ambito = 'empresa'
  AND categoria = 'corporativo'
  AND codigo IN ('acta_asamblea', 'cedula_fiscal', 'otro');
