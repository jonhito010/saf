-- =============================================================================
-- SAF · Validar patch v2 (correr en phpMyAdmin → base saf)
-- Debe devolver 6 tablas con "OK" y columnas clave con "OK"
-- =============================================================================

SELECT 'TABLAS NUEVAS' AS seccion, '' AS detalle, '' AS estado;

SELECT 'tabla' AS tipo, t.tabla, IF(t.existe=1,'OK','FALTA') AS estado
FROM (
  SELECT 'alertas_reglas' AS tabla, COUNT(*) AS existe FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alertas_reglas'
  UNION ALL SELECT 'notificaciones_flujo', COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notificaciones_flujo'
  UNION ALL SELECT 'notificaciones', COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notificaciones'
  UNION ALL SELECT 'verificaciones_historial', COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='verificaciones_historial'
  UNION ALL SELECT 'auditoria_cambios', COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='auditoria_cambios'
  UNION ALL SELECT 'sistema_logs', COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sistema_logs'
) t;

SELECT 'COLUMNAS' AS seccion, '' AS detalle, '' AS estado;

SELECT 'columna' AS tipo, CONCAT(c.tabla,'.',c.columna) AS detalle, IF(c.existe=1,'OK','FALTA') AS estado
FROM (
  SELECT 'usuarios' AS tabla, 'suspendido_at' AS columna, COUNT(*) AS existe FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='suspendido_at'
  UNION ALL SELECT 'usuarios','es_cuenta', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='es_cuenta'
  UNION ALL SELECT 'operadores','baja_at', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='operadores' AND COLUMN_NAME='baja_at'
  UNION ALL SELECT 'alertas_cumplimiento','leida', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alertas_cumplimiento' AND COLUMN_NAME='leida'
  UNION ALL SELECT 'alertas_cumplimiento','url_destino', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alertas_cumplimiento' AND COLUMN_NAME='url_destino'
  UNION ALL SELECT 'alertas_cumplimiento','dias_restantes', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alertas_cumplimiento' AND COLUMN_NAME='dias_restantes'
  UNION ALL SELECT 'tipos_documento','editable', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tipos_documento' AND COLUMN_NAME='editable'
) c;

SELECT 'SEEDS' AS seccion, '' AS detalle, '' AS estado;

SELECT 'seed' AS tipo, 'alertas_reglas filas' AS detalle, CAST(COUNT(*) AS CHAR) AS estado FROM alertas_reglas
UNION ALL SELECT 'seed', 'notificaciones_flujo filas', CAST(COUNT(*) AS CHAR) FROM notificaciones_flujo;

-- Resumen final
SELECT
  CASE
    WHEN
      (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN
        ('alertas_reglas','notificaciones_flujo','notificaciones','verificaciones_historial','auditoria_cambios','sistema_logs')) = 6
      AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='es_cuenta') = 1
      AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alertas_cumplimiento' AND COLUMN_NAME='url_destino') = 1
    THEN 'PATCH OK — Base lista para las mejoras v2'
    ELSE 'PATCH INCOMPLETO — Faltan tablas o columnas'
  END AS resultado;
