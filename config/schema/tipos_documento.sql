-- Catálogo central de tipos de documento (empresa, vehículo, operador).
-- Ejecutar una vez en la BD del sistema SAF.

CREATE TABLE IF NOT EXISTS tipos_documento (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(60) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  ambito ENUM('empresa','vehiculo','operador') NOT NULL,
  categoria VARCHAR(30) NOT NULL DEFAULT 'general',
  descripcion TEXT NULL,
  retencion VARCHAR(255) NULL,
  icono VARCHAR(20) NULL,
  requerido_normativo TINYINT(1) NOT NULL DEFAULT 0,
  requerido_mp TINYINT(1) NOT NULL DEFAULT 0,
  requerido_cumplimiento TINYINT(1) NOT NULL DEFAULT 0,
  visible_formulario TINYINT(1) NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_tipos_documento_ambito_codigo (ambito, codigo),
  KEY idx_tipos_ambito (ambito, activo, orden),
  KEY idx_tipos_categoria (categoria, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Empresa (corporativo) ──
INSERT INTO tipos_documento (codigo, nombre, ambito, categoria, requerido_normativo, requerido_mp, requerido_cumplimiento, visible_formulario, orden, created_at, updated_at) VALUES
('acta_constitutiva', 'Acta constitutiva', 'empresa', 'corporativo', 0, 0, 1, 1, 10, NOW(), NOW()),
('acta_asamblea', 'Acta de asamblea', 'empresa', 'corporativo', 0, 0, 0, 1, 20, NOW(), NOW()),
('poder_notarial_representante', 'Poder notarial (representante legal)', 'empresa', 'corporativo', 0, 0, 1, 1, 30, NOW(), NOW()),
('identificacion_representante_legal', 'Identificación del representante legal', 'empresa', 'corporativo', 0, 0, 1, 1, 40, NOW(), NOW()),
('constancia_situacion_fiscal', 'Constancia de situación fiscal (SAT)', 'empresa', 'corporativo', 0, 0, 1, 1, 50, NOW(), NOW()),
('cedula_fiscal', 'Cédula fiscal', 'empresa', 'corporativo', 0, 0, 0, 1, 60, NOW(), NOW()),
('opinion_cumplimiento_sat', 'Opinión de cumplimiento (SAT)', 'empresa', 'corporativo', 0, 0, 1, 1, 70, NOW(), NOW()),
('comprobante_domicilio_fiscal', 'Comprobante de domicilio fiscal', 'empresa', 'corporativo', 0, 0, 1, 1, 80, NOW(), NOW()),
('registro_rnaf', 'Constancia / registro RNAF (Autotransporte federal)', 'empresa', 'corporativo', 0, 0, 1, 1, 90, NOW(), NOW()),
('permiso_sict', 'Permiso SICT', 'empresa', 'corporativo', 0, 0, 1, 1, 100, NOW(), NOW()),
('poliza_rc_autotransporte', 'Póliza RC autotransporte (federal)', 'empresa', 'corporativo', 0, 0, 1, 1, 110, NOW(), NOW()),
('otro', 'Otro', 'empresa', 'corporativo', 0, 0, 0, 1, 999, NOW(), NOW())
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), requerido_cumplimiento = VALUES(requerido_cumplimiento), updated_at = NOW();

-- ── Empresa (adicionales MP) ──
INSERT INTO tipos_documento (codigo, nombre, ambito, categoria, requerido_mp, visible_formulario, orden, created_at, updated_at) VALUES
('reporte_trimestral_sict', 'Reporte trimestral SICT', 'empresa', 'adicional', 1, 1, 10, NOW(), NOW()),
('documentos_embarque_mp', 'Documentos de embarque (MP/residuos)', 'empresa', 'adicional', 1, 1, 20, NOW(), NOW()),
('hoja_emergencia_transportacion', 'Hoja de emergencia en transportación (HIE)', 'empresa', 'adicional', 1, 1, 30, NOW(), NOW()),
('manifiesto_entrega_transporte_residuos', 'Manifiesto entrega, transporte y recepción', 'empresa', 'adicional', 1, 1, 40, NOW(), NOW()),
('documento_limpieza_remanentes', 'Limpieza y control de remanentes', 'empresa', 'adicional', 1, 1, 50, NOW(), NOW()),
('libro_registro_mercancia_bodega', 'Libro registro mercancía en bodega', 'empresa', 'adicional', 1, 1, 60, NOW(), NOW()),
('manifiesto_derrames_accidente', 'Manifiesto derrames por accidente', 'empresa', 'adicional', 1, 1, 70, NOW(), NOW()),
('bitacora_control_residuos', 'Bitácora control de residuos', 'empresa', 'adicional', 1, 1, 80, NOW(), NOW()),
('otro_adicional', 'Otro (documento adicional)', 'empresa', 'adicional', 0, 1, 999, NOW(), NOW())
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), updated_at = NOW();

-- ── Vehículo (general + normativo) ──
INSERT INTO tipos_documento (codigo, nombre, ambito, categoria, descripcion, retencion, requerido_normativo, visible_formulario, orden, created_at, updated_at) VALUES
('tarjeta_circulacion', 'Tarjeta de circulación', 'vehiculo', 'normativo', 'Tarjeta de circulación vigente de la unidad.', 'Vigente', 1, 1, 10, NOW(), NOW()),
('poliza_seguro', 'Póliza de seguro', 'vehiculo', 'normativo', 'Póliza de seguro de la unidad.', 'Vigente', 1, 1, 20, NOW(), NOW()),
('inspeccion_fisico_mecanica', 'Inspección FM', 'vehiculo', 'normativo', 'Inspección de condiciones físico-mecánicas (FM).', 'Último certificado vigente', 1, 1, 30, NOW(), NOW()),
('verificacion_emisiones', 'Inspección E', 'vehiculo', 'normativo', 'Inspección de emisiones (E).', 'Último certificado vigente', 1, 1, 40, NOW(), NOW()),
('alta_vehicular', 'Alta vehicular', 'vehiculo', 'general', NULL, NULL, 0, 1, 50, NOW(), NOW()),
('permiso_sct_sict', 'Permiso/Autorización SICT (unidad)', 'vehiculo', 'general', NULL, NULL, 0, 1, 60, NOW(), NOW()),
('certificado_emisiones', 'Certificado Inspección E', 'vehiculo', 'general', NULL, NULL, 0, 1, 70, NOW(), NOW()),
('tenencia_refrendo', 'Tenencia/Refrendo', 'vehiculo', 'general', NULL, NULL, 0, 1, 80, NOW(), NOW()),
('factura', 'Factura / Documento de propiedad', 'vehiculo', 'general', NULL, NULL, 0, 1, 90, NOW(), NOW()),
('contrato_arrendamiento', 'Contrato de arrendamiento (si aplica)', 'vehiculo', 'general', NULL, NULL, 0, 1, 100, NOW(), NOW()),
('otro', 'Otro', 'vehiculo', 'general', NULL, NULL, 0, 1, 999, NOW(), NOW())
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), retencion = VALUES(retencion), requerido_normativo = VALUES(requerido_normativo), updated_at = NOW();

-- ── Vehículo (materiales peligrosos) ──
INSERT INTO tipos_documento (codigo, nombre, ambito, categoria, descripcion, retencion, icono, requerido_mp, visible_formulario, orden, created_at, updated_at) VALUES
('poliza_responsabilidad_ambiental', 'Póliza RC daños al medio ambiente', 'vehiculo', 'materiales_peligrosos', 'Póliza de seguro vigente que cubra la responsabilidad por daños al medio ambiente.', 'Vigente', '🌿', 1, 1, 110, NOW(), NOW()),
('comprobante_pago_poliza_ambiental', 'Comprobante de pago (póliza ambiental)', 'vehiculo', 'materiales_peligrosos', 'Comprobante de pago de la póliza de responsabilidad ambiental.', 'Vigente', '💳', 1, 1, 120, NOW(), NOW()),
('bitacora_inspeccion_ocular', 'Bitácora inspección ocular diaria', 'vehiculo', 'materiales_peligrosos', 'Bitácoras de inspección ocular diaria de la unidad.', '6 meses anteriores', '👁️', 1, 1, 130, NOW(), NOW()),
('control_mantenimiento', 'Control mantenimiento preventivo/correctivo', 'vehiculo', 'materiales_peligrosos', 'Registro de mantenimiento preventivo y correctivo de la unidad.', 'Histórico de la unidad', '🔩', 1, 1, 140, NOW(), NOW())
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), retencion = VALUES(retencion), icono = VALUES(icono), requerido_mp = VALUES(requerido_mp), updated_at = NOW();

-- ── Operador ──
INSERT INTO tipos_documento (codigo, nombre, ambito, categoria, requerido_normativo, requerido_mp, requerido_cumplimiento, visible_formulario, orden, created_at, updated_at) VALUES
('licencia', 'Licencia federal', 'operador', 'general', 0, 1, 1, 1, 10, NOW(), NOW()),
('ine', 'INE', 'operador', 'general', 0, 1, 1, 1, 20, NOW(), NOW()),
('comprobante_domicilio', 'Comprobante de domicilio', 'operador', 'general', 0, 0, 1, 1, 30, NOW(), NOW()),
('apto_medico', 'Apto médico', 'operador', 'general', 0, 0, 1, 0, 40, NOW(), NOW()),
('curso_capacitacion', 'Curso de capacitación', 'operador', 'general', 0, 1, 0, 1, 50, NOW(), NOW()),
('acta_nacimiento', 'Acta de nacimiento', 'operador', 'general', 0, 0, 0, 0, 60, NOW(), NOW()),
('otro', 'Otro', 'operador', 'general', 0, 0, 0, 0, 999, NOW(), NOW())
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), requerido_mp = VALUES(requerido_mp), requerido_cumplimiento = VALUES(requerido_cumplimiento), visible_formulario = VALUES(visible_formulario), updated_at = NOW();
