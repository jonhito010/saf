-- =============================================================================
-- SAF · Reglas de alerta por tipo de documento
-- Uso: base "saf" → phpMyAdmin → SQL → pegar → Continuar
-- =============================================================================

SET NAMES utf8mb4;

-- ambito: todos | empresa | vehiculo | operador
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_reglas' AND COLUMN_NAME = 'ambito'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `alertas_reglas` ADD COLUMN `ambito` VARCHAR(20) NOT NULL DEFAULT ''todos'' AFTER `nombre`',
  'SELECT ''OK: ambito ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- tipo_documento: '' = todos los del ámbito; si no, código del catálogo
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_reglas' AND COLUMN_NAME = 'tipo_documento'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `alertas_reglas` ADD COLUMN `tipo_documento` VARCHAR(60) NOT NULL DEFAULT '''' AFTER `ambito`',
  'SELECT ''OK: tipo_documento ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Sustituir UNIQUE (días, empresa) por (días, empresa, ámbito, tipo)
SET @existe := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_reglas' AND INDEX_NAME = 'uq_regla_dias_empresa'
);
SET @sql := IF(@existe > 0,
  'ALTER TABLE `alertas_reglas` DROP INDEX `uq_regla_dias_empresa`',
  'SELECT ''OK: uq_regla_dias_empresa ya eliminado'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_reglas' AND INDEX_NAME = 'uq_regla_dias_ambito_tipo'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `alertas_reglas` ADD UNIQUE KEY `uq_regla_dias_ambito_tipo` (`dias_antes`, `empresa_id`, `ambito`, `tipo_documento`)',
  'SELECT ''OK: uq_regla_dias_ambito_tipo ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `alertas_reglas` SET `ambito` = 'todos' WHERE `ambito` IS NULL OR `ambito` = '';
UPDATE `alertas_reglas` SET `tipo_documento` = '' WHERE `tipo_documento` IS NULL;

SELECT 'ambito' AS columna, COUNT(*) AS ok FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alertas_reglas' AND COLUMN_NAME='ambito'
UNION ALL
SELECT 'tipo_documento', COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alertas_reglas' AND COLUMN_NAME='tipo_documento';
