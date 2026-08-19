-- =============================================================================
-- SAF · patch_mejoras_v2.sql
-- Mejoras: superadmin/cuentas, alertas configurables, notificaciones,
-- auditoría, verificaciones, operadores (baja lógica ampliada), catálogo fijo.
-- Idempotente donde es posible (procedimientos / IF NOT EXISTS).
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

-- ---------- Helper: agregar columna si no existe ----------
DROP PROCEDURE IF EXISTS saf_add_column_if_missing;
DELIMITER $$
CREATE PROCEDURE saf_add_column_if_missing(
  IN p_table VARCHAR(64),
  IN p_column VARCHAR(64),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND COLUMN_NAME = p_column
  ) THEN
    SET @sql := CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

-- ---------- Usuarios / cuentas ----------
CALL saf_add_column_if_missing('usuarios', 'suspendido_at',
  '`suspendido_at` DATETIME NULL DEFAULT NULL AFTER `activo`');
CALL saf_add_column_if_missing('usuarios', 'motivo_suspension',
  '`motivo_suspension` VARCHAR(255) NULL DEFAULT NULL AFTER `suspendido_at`');
CALL saf_add_column_if_missing('usuarios', 'es_cuenta',
  '`es_cuenta` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = cuenta titular (consultor)'' AFTER `rol_id`');

-- Marcar administradores existentes como cuentas
UPDATE usuarios SET es_cuenta = 1 WHERE rol_id IN (1, 2) AND (es_cuenta IS NULL OR es_cuenta = 0);

-- ---------- Empresas: suspensión ----------
CALL saf_add_column_if_missing('empresas', 'suspendida_at',
  '`suspendida_at` DATETIME NULL DEFAULT NULL');
CALL saf_add_column_if_missing('empresas', 'motivo_suspension',
  '`motivo_suspension` VARCHAR(255) NULL DEFAULT NULL');

-- Ampliar estatus empresa si es varchar
-- (activa | suspendida | baja) — se gestiona en app; no forzar ENUM.

-- ---------- Operadores: estados de baja lógica ----------
-- Conservar datos; ampliar valores permitidos en app.
CALL saf_add_column_if_missing('operadores', 'baja_at',
  '`baja_at` DATETIME NULL DEFAULT NULL');
CALL saf_add_column_if_missing('operadores', 'baja_motivo',
  '`baja_motivo` VARCHAR(255) NULL DEFAULT NULL');
CALL saf_add_column_if_missing('operadores', 'baja_usuario_id',
  '`baja_usuario_id` INT UNSIGNED NULL DEFAULT NULL');

-- ---------- Alertas cumplimiento: lectura y deep-link ----------
CALL saf_add_column_if_missing('alertas_cumplimiento', 'leida',
  '`leida` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notificado`');
CALL saf_add_column_if_missing('alertas_cumplimiento', 'leida_at',
  '`leida_at` DATETIME NULL DEFAULT NULL AFTER `leida`');
CALL saf_add_column_if_missing('alertas_cumplimiento', 'documento_id',
  '`documento_id` INT UNSIGNED NULL DEFAULT NULL AFTER `tipo_documento`');
CALL saf_add_column_if_missing('alertas_cumplimiento', 'url_destino',
  '`url_destino` VARCHAR(255) NULL DEFAULT NULL AFTER `documento_id`');
CALL saf_add_column_if_missing('alertas_cumplimiento', 'dias_restantes',
  '`dias_restantes` INT NULL DEFAULT NULL AFTER `dias_anticipacion`');
CALL saf_add_column_if_missing('alertas_cumplimiento', 'entidad_nombre',
  '`entidad_nombre` VARCHAR(180) NULL DEFAULT NULL AFTER `entidad_id`');
CALL saf_add_column_if_missing('alertas_cumplimiento', 'empresa_nombre',
  '`empresa_nombre` VARCHAR(180) NULL DEFAULT NULL AFTER `empresa_id`');
CALL saf_add_column_if_missing('alertas_cumplimiento', 'tipo_documento_label',
  '`tipo_documento_label` VARCHAR(150) NULL DEFAULT NULL AFTER `tipo_documento`');

-- ---------- Tipos documento: catálogo fijo ----------
CALL saf_add_column_if_missing('tipos_documento', 'editable',
  '`editable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''0 = catálogo fijo'' AFTER `activo`');
UPDATE tipos_documento SET editable = 0 WHERE editable IS NULL OR editable = 1;

-- ---------- Reglas de alertas (catálogo configurable) ----------
CREATE TABLE IF NOT EXISTS `alertas_reglas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dias_antes` INT NOT NULL,
  `nombre` VARCHAR(80) NOT NULL,
  `enviar_alerta` TINYINT(1) NOT NULL DEFAULT 1,
  `ocultar_despues` TINYINT(1) NOT NULL DEFAULT 0,
  `mantener_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `nivel_urgencia` ENUM('info','advertencia','critico') NOT NULL DEFAULT 'info',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` INT NOT NULL DEFAULT 0,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = global',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regla_dias_empresa` (`dias_antes`, `empresa_id`),
  KEY `idx_alertas_reglas_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `alertas_reglas`
  (`dias_antes`, `nombre`, `enviar_alerta`, `ocultar_despues`, `mantener_visible`, `nivel_urgencia`, `activo`, `orden`, `empresa_id`, `created_at`)
SELECT * FROM (
  SELECT 45 AS dias_antes, 'Aviso 45 días' AS nombre, 1 AS enviar_alerta, 0 AS ocultar_despues, 1 AS mantener_visible, 'info' AS nivel_urgencia, 1 AS activo, 1 AS orden, NULL AS empresa_id, NOW() AS created_at
  UNION ALL SELECT 30, 'Aviso 30 días', 1, 0, 1, 'advertencia', 1, 2, NULL, NOW()
  UNION ALL SELECT 15, 'Aviso 15 días', 1, 0, 1, 'advertencia', 1, 3, NULL, NOW()
  UNION ALL SELECT 7,  'Aviso 7 días',  1, 0, 1, 'critico', 1, 4, NULL, NOW()
  UNION ALL SELECT 1,  'Aviso 1 día',   1, 0, 1, 'critico', 1, 5, NULL, NOW()
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM alertas_reglas LIMIT 1);

-- ---------- Flujo de notificaciones (Consultor primero) ----------
CREATE TABLE IF NOT EXISTS `notificaciones_flujo` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `orden` INT NOT NULL DEFAULT 1,
  `rol_id` INT NOT NULL,
  `rol_nombre` VARCHAR(60) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_flujo_orden` (`orden`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notificaciones_flujo` (`orden`, `rol_id`, `rol_nombre`, `activo`, `empresa_id`, `created_at`)
SELECT * FROM (
  SELECT 1 AS orden, 2 AS rol_id, 'Consultor' AS rol_nombre, 1 AS activo, NULL AS empresa_id, NOW() AS created_at
  UNION ALL SELECT 2, 3, 'Usuario', 1, NULL, NOW()
  UNION ALL SELECT 3, 1, 'Super Administrador', 0, NULL, NOW()
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM notificaciones_flujo LIMIT 1);

-- ---------- Bandeja de notificaciones ----------
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NULL,
  `alerta_id` INT UNSIGNED NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `mensaje` VARCHAR(500) NOT NULL,
  `url_destino` VARCHAR(255) NULL,
  `tipo` VARCHAR(40) NOT NULL DEFAULT 'alerta',
  `leida` TINYINT(1) NOT NULL DEFAULT 0,
  `leida_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_usuario_leida` (`usuario_id`, `leida`, `created_at`),
  KEY `idx_notif_alerta` (`alerta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Historial de verificaciones ----------
CREATE TABLE IF NOT EXISTS `verificaciones_historial` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `operador_id` INT UNSIGNED NULL,
  `vehiculo_id` INT UNSIGNED NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(60) NOT NULL DEFAULT 'verificacion',
  `resultado` VARCHAR(40) NOT NULL,
  `detalle` TEXT NULL,
  `fecha` DATETIME NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_verif_empresa_fecha` (`empresa_id`, `fecha`),
  KEY `idx_verif_operador` (`operador_id`),
  KEY `idx_verif_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Auditoría de modificaciones (campo a campo) ----------
CREATE TABLE IF NOT EXISTS `auditoria_cambios` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NULL,
  `usuario_id` INT UNSIGNED NULL,
  `entidad_tipo` VARCHAR(60) NOT NULL,
  `entidad_id` INT UNSIGNED NOT NULL,
  `campo` VARCHAR(80) NOT NULL,
  `valor_anterior` TEXT NULL,
  `valor_nuevo` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aud_entidad` (`entidad_tipo`, `entidad_id`),
  KEY `idx_aud_empresa_fecha` (`empresa_id`, `created_at`),
  KEY `idx_aud_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Log del sistema ----------
CREATE TABLE IF NOT EXISTS `sistema_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NULL,
  `empresa_id` INT UNSIGNED NULL,
  `accion` VARCHAR(60) NOT NULL,
  `modulo` VARCHAR(60) NULL,
  `descripcion` VARCHAR(500) NOT NULL,
  `detalle_json` JSON NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `nivel` ENUM('info','warning','error') NOT NULL DEFAULT 'info',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_slog_accion_fecha` (`accion`, `created_at`),
  KEY `idx_slog_usuario` (`usuario_id`, `created_at`),
  KEY `idx_slog_empresa` (`empresa_id`, `created_at`),
  KEY `idx_slog_modulo` (`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS saf_add_column_if_missing;

-- Fin patch_mejoras_v2.sql
