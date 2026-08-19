-- SAF — Seed Data (ajustado al schema existente)
-- Relativo a: 2026-03-30

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── EMPRESA ─────────────────────────────────────────────────────────────────
INSERT INTO `empresas` (`id`, `nombre`, `rfc`, `domicilio`, `colonia`, `municipio`, `estado`, `cp`, `telefono`, `email`, `numero_permiso_sict`, `fecha_permiso_sict`, `vencimiento_permiso_sict`, `estatus`) VALUES
(1, 'Transportes Norteño S.A. de C.V.', 'TNO940312AB4',
 'Av. Industrial 2240', 'Parque Logístico Norte', 'Monterrey', 'Nuevo León', '64500',
 '+52 81 8000-1234', 'contacto@transportesnorteno.mx',
 'SICT-TFF-A-00234-2022', '2022-03-01', '2026-05-29', 'activa');

-- ─── VEHÍCULOS ────────────────────────────────────────────────────────────────
INSERT INTO `vehiculos` (`id`, `empresa_id`, `marca`, `modelo`, `anio`, `numero_serie`, `numero_motor`, `placas`, `tipo_carga`, `modalidad`, `tipo_servicio`, `color`, `estatus`) VALUES
(1, 1, 'Kenworth',      'T680',     2022, '1XKWDB0X6NJ123456', 'PX-7-22034A',    'ABC-123-D', 'carga_general',    'AUTOTRANSPORTE FEDERAL', 'CARGA GENERAL', 'Blanco',  'activo'),
(2, 1, 'Freightliner',  'Cascadia', 2021, '3AKJGLD57FSGH1234', 'DD15-21987B',    'XYZ-987-F', 'carga_general',    NULL, NULL, 'Gris',    'activo'),
(3, 1, 'International', 'LT',       2020, '3HSDXAPR4LN512345', 'A26-20456C',     'MNO-456-B', 'material_peligroso', NULL, NULL, 'Rojo',   'activo'),
(4, 1, 'Volvo',         'FH',       2023, '4V4NC9EH5PN212345', 'D13K-23102A',    'PQR-321-C', 'voluminosos',      NULL, NULL, 'Azul',    'activo'),
(5, 1, 'Peterbilt',     '579',      2022, '1XPBDP0X2ND712345', 'PACCAR-22789D',  'STU-654-A', 'carga_general',    NULL, NULL, 'Negro',   'activo');

-- ─── OPERADORES ──────────────────────────────────────────────────────────────
INSERT INTO `operadores` (`id`, `empresa_id`, `nombre`, `apellido_paterno`, `apellido_materno`, `curp`, `telefono`, `email`, `fecha_ingreso`, `estatus`) VALUES
(1, 1, 'Jorge',  'Ramírez',  'Morales', 'RAMJ820514HNLMRR04', '+52 81 1111-0001', 'j.ramirez@tn.mx',   '2018-03-15', 'activo'),
(2, 1, 'Miguel', 'González', 'Torres',  'GOTM751203HNLLRG08', '+52 81 1111-0002', 'm.gonzalez@tn.mx',  '2016-07-01', 'activo'),
(3, 1, 'Carlos', 'Flores',   'Juárez',  'FLJC900814HNTLRL02', '+52 81 1111-0003', 'c.flores@tn.mx',    '2020-01-10', 'activo'),
(4, 1, 'Andrés', 'Reyes',    'Sánchez', 'RESA881120HNLYND07', '+52 81 1111-0004', 'a.reyes@tn.mx',     '2019-05-20', 'activo'),
(5, 1, 'Luis',   'Hernández','Vega',    'HEVL930528HNTRGR06', '+52 81 1111-0005', 'l.hernandez@tn.mx', '2021-08-02', 'activo');

-- ─── DOCUMENTOS EMPRESA ──────────────────────────────────────────────────────
INSERT INTO `documentos_empresa` (`empresa_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(1, 'acta_constitutiva', 'acta_constitutiva.pdf', '/docs/empresa/acta_constitutiva.pdf', NULL,         'vigente'),
(1, 'acta_asamblea',     'acta_asamblea_2025.pdf','/docs/empresa/acta_asamblea_2025.pdf', NULL,        'vigente'),
(1, 'permiso_sict',      'permiso_sict.pdf',      '/docs/empresa/permiso_sict.pdf',      '2026-05-29', 'por_vencer');

-- ─── DOCUMENTOS VEHÍCULO 1 (ABC-123-D) — póliza vencida, verificación por vencer ───
INSERT INTO `documentos_vehiculo` (`vehiculo_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_emision`, `fecha_vencimiento`, `estatus`) VALUES
(1, 'tarjeta_circulacion',       'tarjeta_circulacion_v1.pdf',  '/docs/v1/tarjeta.pdf',      '2024-09-15', '2026-09-15', 'vigente'),
(1, 'alta_vehicular',            'alta_vehicular_v1.pdf',       '/docs/v1/alta.pdf',          '2022-01-10', NULL,         'vigente'),
(1, 'poliza_seguro',             'poliza_seguro_v1.pdf',        '/docs/v1/poliza.pdf',        '2025-03-25', '2026-03-25', 'vencido'),
(1, 'inspeccion_fisico_mecanica','inspeccion_v1.pdf',           '/docs/v1/inspeccion.pdf',    '2025-01-08', '2027-01-08', 'vigente'),
(1, 'verificacion_emisiones',    'verificacion_v1.pdf',         '/docs/v1/verificacion.pdf',  '2025-04-17', '2026-04-17', 'por_vencer');

-- ─── DOCUMENTOS VEHÍCULO 2 (XYZ-987-F) — verificación por vencer ───
INSERT INTO `documentos_vehiculo` (`vehiculo_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(2, 'tarjeta_circulacion',       'tarjeta_v2.pdf',     '/docs/v2/tarjeta.pdf',     '2026-10-01', 'vigente'),
(2, 'alta_vehicular',            'alta_v2.pdf',        '/docs/v2/alta.pdf',         NULL,         'vigente'),
(2, 'poliza_seguro',             'poliza_v2.pdf',      '/docs/v2/poliza.pdf',       '2026-11-15', 'vigente'),
(2, 'inspeccion_fisico_mecanica','inspeccion_v2.pdf',  '/docs/v2/inspeccion.pdf',   '2026-12-20', 'vigente'),
(2, 'verificacion_emisiones',    'verificacion_v2.pdf','/docs/v2/verificacion.pdf', '2026-04-17', 'por_vencer');

-- ─── DOCUMENTOS VEHÍCULO 3 (MNO-456-B) — inspección por vencer ───
INSERT INTO `documentos_vehiculo` (`vehiculo_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(3, 'tarjeta_circulacion',       'tarjeta_v3.pdf',     '/docs/v3/tarjeta.pdf',     '2026-08-01', 'vigente'),
(3, 'alta_vehicular',            'alta_v3.pdf',        '/docs/v3/alta.pdf',         NULL,         'vigente'),
(3, 'poliza_seguro',             'poliza_v3.pdf',      '/docs/v3/poliza.pdf',       '2026-09-10', 'vigente'),
(3, 'inspeccion_fisico_mecanica','inspeccion_v3.pdf',  '/docs/v3/inspeccion.pdf',   '2026-04-21', 'por_vencer'),
(3, 'verificacion_emisiones',    'verificacion_v3.pdf','/docs/v3/verificacion.pdf', '2026-07-30', 'vigente');

-- ─── DOCUMENTOS VEHÍCULO 4 (PQR-321-C) — todo vigente ───
INSERT INTO `documentos_vehiculo` (`vehiculo_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(4, 'tarjeta_circulacion',       'tarjeta_v4.pdf',     '/docs/v4/tarjeta.pdf',     '2026-11-01', 'vigente'),
(4, 'alta_vehicular',            'alta_v4.pdf',        '/docs/v4/alta.pdf',         NULL,         'vigente'),
(4, 'poliza_seguro',             'poliza_v4.pdf',      '/docs/v4/poliza.pdf',       '2026-10-15', 'vigente'),
(4, 'inspeccion_fisico_mecanica','inspeccion_v4.pdf',  '/docs/v4/inspeccion.pdf',   '2027-02-20', 'vigente'),
(4, 'verificacion_emisiones',    'verificacion_v4.pdf','/docs/v4/verificacion.pdf', '2026-08-05', 'vigente');

-- ─── DOCUMENTOS VEHÍCULO 5 (STU-654-A) — todo vigente ───
INSERT INTO `documentos_vehiculo` (`vehiculo_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(5, 'tarjeta_circulacion',       'tarjeta_v5.pdf',     '/docs/v5/tarjeta.pdf',     '2026-12-30', 'vigente'),
(5, 'alta_vehicular',            'alta_v5.pdf',        '/docs/v5/alta.pdf',         NULL,         'vigente'),
(5, 'poliza_seguro',             'poliza_v5.pdf',      '/docs/v5/poliza.pdf',       '2026-09-01', 'vigente'),
(5, 'inspeccion_fisico_mecanica','inspeccion_v5.pdf',  '/docs/v5/inspeccion.pdf',   '2027-03-10', 'vigente'),
(5, 'verificacion_emisiones',    'verificacion_v5.pdf','/docs/v5/verificacion.pdf', '2026-06-30', 'vigente');

-- ─── DOCUMENTOS OPERADOR 1 (Jorge Ramírez) — apto médico vencido ───
INSERT INTO `documentos_operador` (`operador_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(1, 'licencia',            'licencia_o1.pdf',     '/docs/o1/licencia.pdf',     '2026-10-14', 'vigente'),
(1, 'apto_medico',         'apto_medico_o1.pdf',  '/docs/o1/apto.pdf',         '2026-03-28', 'vencido'),
(1, 'ine',                 'ine_o1.pdf',           '/docs/o1/ine.pdf',          '2027-05-14', 'vigente'),
(1, 'acta_nacimiento',     'acta_o1.pdf',          '/docs/o1/acta.pdf',         NULL,         'vigente'),
(1, 'comprobante_domicilio','comp_dom_o1.pdf',     '/docs/o1/compdom.pdf',      '2026-09-01', 'vigente');

-- ─── DOCUMENTOS OPERADOR 2 (Miguel González) — licencia por vencer ───
INSERT INTO `documentos_operador` (`operador_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(2, 'licencia',            'licencia_o2.pdf',     '/docs/o2/licencia.pdf',     '2026-04-21', 'por_vencer'),
(2, 'apto_medico',         'apto_medico_o2.pdf',  '/docs/o2/apto.pdf',         '2026-08-03', 'vigente'),
(2, 'ine',                 'ine_o2.pdf',           '/docs/o2/ine.pdf',          '2028-12-03', 'vigente'),
(2, 'acta_nacimiento',     'acta_o2.pdf',          '/docs/o2/acta.pdf',         NULL,         'vigente'),
(2, 'comprobante_domicilio','comp_dom_o2.pdf',     '/docs/o2/compdom.pdf',      '2026-10-15', 'vigente');

-- ─── DOCUMENTOS OPERADOR 3 (Carlos Flores) — todo vigente ───
INSERT INTO `documentos_operador` (`operador_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(3, 'licencia',            'licencia_o3.pdf',     '/docs/o3/licencia.pdf',     '2027-08-14', 'vigente'),
(3, 'apto_medico',         'apto_medico_o3.pdf',  '/docs/o3/apto.pdf',         '2026-07-10', 'vigente'),
(3, 'ine',                 'ine_o3.pdf',           '/docs/o3/ine.pdf',          '2027-11-20', 'vigente'),
(3, 'acta_nacimiento',     'acta_o3.pdf',          '/docs/o3/acta.pdf',         NULL,         'vigente'),
(3, 'comprobante_domicilio','comp_dom_o3.pdf',     '/docs/o3/compdom.pdf',      '2026-11-01', 'vigente');

-- ─── DOCUMENTOS OPERADOR 4 (Andrés Reyes) — 1 faltante ───
INSERT INTO `documentos_operador` (`operador_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(4, 'licencia',            'licencia_o4.pdf',     '/docs/o4/licencia.pdf',     '2026-11-20', 'vigente'),
(4, 'apto_medico',         'apto_medico_o4.pdf',  '/docs/o4/apto.pdf',         '2026-09-05', 'vigente'),
(4, 'ine',                 'ine_o4.pdf',           '/docs/o4/ine.pdf',          '2026-12-01', 'vigente'),
(4, 'comprobante_domicilio','comp_dom_o4.pdf',     '/docs/o4/compdom.pdf',      '2026-08-20', 'vigente');
-- acta_nacimiento faltante (no insertada a propósito)

-- ─── DOCUMENTOS OPERADOR 5 (Luis Hernández) — todo vigente ───
INSERT INTO `documentos_operador` (`operador_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `fecha_vencimiento`, `estatus`) VALUES
(5, 'licencia',            'licencia_o5.pdf',     '/docs/o5/licencia.pdf',     '2027-05-28', 'vigente'),
(5, 'apto_medico',         'apto_medico_o5.pdf',  '/docs/o5/apto.pdf',         '2026-06-15', 'vigente'),
(5, 'ine',                 'ine_o5.pdf',           '/docs/o5/ine.pdf',          '2027-09-10', 'vigente'),
(5, 'acta_nacimiento',     'acta_o5.pdf',          '/docs/o5/acta.pdf',         NULL,         'vigente'),
(5, 'comprobante_domicilio','comp_dom_o5.pdf',     '/docs/o5/compdom.pdf',      '2026-12-28', 'vigente');

-- ─── MANTENIMIENTOS ──────────────────────────────────────────────────────────
INSERT INTO `mantenimientos` (`vehiculo_id`, `fecha`, `tipo`, `descripcion`, `kilometraje`, `taller`, `costo`) VALUES
(1, '2026-02-15', 'preventivo',  'Cambio de aceite y filtros',          128450, 'Taller Norte MTY',   4200.00),
(1, '2026-01-10', 'correctivo',  'Reparación sistema de frenos',        121300, 'Kenworth Servicio',  18500.00),
(1, '2025-10-05', 'preventivo',  'Revisión general 100k km',            100000, 'Taller Norte MTY',   12800.00),
(2, '2026-01-20', 'preventivo',  'Cambio de aceite',                    95000,  'Taller Sur MTY',     3800.00),
(3, '2026-02-28', 'correctivo',  'Reparación compresor de aire',        110500, 'International Svc',  22000.00);

-- ─── BAJAS VEHICULARES ───────────────────────────────────────────────────────
-- Baja activa (vehículo 1, paso 3 = placas_devueltas pendientes)
INSERT INTO `bajas_vehiculares` (`vehiculo_id`, `empresa_id`, `motivo_baja`, `fecha_baja`, `descripcion`, `estatus_tramite`, `placas_devueltas`, `numero_folio_baja`, `notas`) VALUES
(1, 1, 'siniestro', '2026-03-28', 'Vehículo siniestrado en carretera Monterrey-Saltillo', 'placas_devueltas', 0, NULL,          'Pendiente confirmar devolución de placas ante la SICT'),
(1, 1, 'reemplazo', '2026-01-15', 'Reemplazo por unidad nueva',                           'completado',       1, 'SICT-B-00891', NULL),
(1, 1, 'venta',     '2025-11-02', 'Venta a tercero',                                      'completado',       1, 'SICT-B-00754', NULL);

-- ─── ALERTAS ─────────────────────────────────────────────────────────────────
INSERT INTO `alertas` (`tipo`, `titulo`, `descripcion`, `entidad_tipo`, `entidad_id`, `leida`, `created`, `modified`) VALUES
('critico',     'Póliza vencida — ABC-123-D',         'La póliza de seguro del vehículo ABC-123-D venció hace 5 días', 'vehiculo', 1, 0, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),
('critico',     'Apto médico vencido — J. Ramírez',   'El apto médico del operador Jorge Ramírez venció hace 2 días',  'operador', 1, 0, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
('advertencia', 'Verificación por vencer — XYZ-987-F','La verificación de emisiones vence en 18 días',                 'vehiculo', 2, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
('advertencia', 'Licencia por vencer — M. González',  'La licencia federal de Miguel González vence en 22 días',       'operador', 2, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
('info',        'Permiso SICT renueva en 60 días',    'El permiso SICT de la empresa vence el 29/05/2026',             'empresa',  1, 0, NOW(),                           NOW());

SET FOREIGN_KEY_CHECKS = 1;
