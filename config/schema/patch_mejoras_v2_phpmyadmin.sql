-- =============================================================================
-- SAF · Mejoras v2 — SQL seguro para phpMyAdmin
-- Salta columnas que ya existen (no marca error #1060).
-- Uso: base "saf" → pestaña SQL → pegar TODO → Continuar
-- =============================================================================

SET NAMES utf8mb4;

-- Helper: ejecutar ALTER solo si la columna NO existe
-- Patrón: SET @existe / SET @sql / PREPARE / EXECUTE

-- ---------- usuarios.suspendido_at ----------
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'suspendido_at'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `usuarios` ADD COLUMN `suspendido_at` DATETIME NULL DEFAULT NULL AFTER `activo`',
  'SELECT ''OK: usuarios.suspendido_at ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- usuarios.motivo_suspension ----------
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'motivo_suspension'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `usuarios` ADD COLUMN `motivo_suspension` VARCHAR(255) NULL DEFAULT NULL AFTER `suspendido_at`',
  'SELECT ''OK: usuarios.motivo_suspension ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- usuarios.es_cuenta ----------
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'es_cuenta'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `usuarios` ADD COLUMN `es_cuenta` TINYINT(1) NOT NULL DEFAULT 0 AFTER `rol_id`',
  'SELECT ''OK: usuarios.es_cuenta ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `usuarios` SET `es_cuenta` = 1 WHERE `rol_id` IN (1, 2);

-- ---------- empresas.suspendida_at ----------
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empresas' AND COLUMN_NAME = 'suspendida_at'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `empresas` ADD COLUMN `suspendida_at` DATETIME NULL DEFAULT NULL',
  'SELECT ''OK: empresas.suspendida_at ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- empresas.motivo_suspension ----------
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empresas' AND COLUMN_NAME = 'motivo_suspension'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `empresas` ADD COLUMN `motivo_suspension` VARCHAR(255) NULL DEFAULT NULL',
  'SELECT ''OK: empresas.motivo_suspension ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- operadores.baja_at ----------
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'operadores' AND COLUMN_NAME = 'baja_at'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `operadores` ADD COLUMN `baja_at` DATETIME NULL DEFAULT NULL',
  'SELECT ''OK: operadores.baja_at ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- operadores.baja_motivo ----------
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'operadores' AND COLUMN_NAME = 'baja_motivo'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `operadores` ADD COLUMN `baja_motivo` VARCHAR(255) NULL DEFAULT NULL',
  'SELECT ''OK: operadores.baja_motivo ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- operadores.baja_usuario_id ----------
SET @existe := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'operadores' AND COLUMN_NAME = 'baja_usuario_id'
);
SET @sql := IF(@existe = 0,
  'ALTER TABLE `operadores` ADD COLUMN `baja_usuario_id` INT UNSIGNED NULL DEFAULT NULL',
  'SELECT ''OK: operadores.baja_usuario_id ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- alertas_cumplimiento ----------
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_cumplimiento' AND COLUMN_NAME = 'leida');
SET @sql := IF(@existe = 0, 'ALTER TABLE `alertas_cumplimiento` ADD COLUMN `leida` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notificado`', 'SELECT ''OK: leida ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_cumplimiento' AND COLUMN_NAME = 'leida_at');
SET @sql := IF(@existe = 0, 'ALTER TABLE `alertas_cumplimiento` ADD COLUMN `leida_at` DATETIME NULL DEFAULT NULL AFTER `leida`', 'SELECT ''OK: leida_at ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_cumplimiento' AND COLUMN_NAME = 'documento_id');
SET @sql := IF(@existe = 0, 'ALTER TABLE `alertas_cumplimiento` ADD COLUMN `documento_id` INT UNSIGNED NULL DEFAULT NULL AFTER `tipo_documento`', 'SELECT ''OK: documento_id ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_cumplimiento' AND COLUMN_NAME = 'url_destino');
SET @sql := IF(@existe = 0, 'ALTER TABLE `alertas_cumplimiento` ADD COLUMN `url_destino` VARCHAR(255) NULL DEFAULT NULL AFTER `documento_id`', 'SELECT ''OK: url_destino ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_cumplimiento' AND COLUMN_NAME = 'dias_restantes');
SET @sql := IF(@existe = 0, 'ALTER TABLE `alertas_cumplimiento` ADD COLUMN `dias_restantes` INT NULL DEFAULT NULL AFTER `dias_anticipacion`', 'SELECT ''OK: dias_restantes ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_cumplimiento' AND COLUMN_NAME = 'entidad_nombre');
SET @sql := IF(@existe = 0, 'ALTER TABLE `alertas_cumplimiento` ADD COLUMN `entidad_nombre` VARCHAR(180) NULL DEFAULT NULL AFTER `entidad_id`', 'SELECT ''OK: entidad_nombre ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_cumplimiento' AND COLUMN_NAME = 'empresa_nombre');
SET @sql := IF(@existe = 0, 'ALTER TABLE `alertas_cumplimiento` ADD COLUMN `empresa_nombre` VARCHAR(180) NULL DEFAULT NULL AFTER `empresa_id`', 'SELECT ''OK: empresa_nombre ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alertas_cumplimiento' AND COLUMN_NAME = 'tipo_documento_label');
SET @sql := IF(@existe = 0, 'ALTER TABLE `alertas_cumplimiento` ADD COLUMN `tipo_documento_label` VARCHAR(150) NULL DEFAULT NULL AFTER `tipo_documento`', 'SELECT ''OK: tipo_documento_label ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- tipos_documento.editable ----------
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tipos_documento' AND COLUMN_NAME = 'editable');
SET @sql := IF(@existe = 0, 'ALTER TABLE `tipos_documento` ADD COLUMN `editable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `activo`', 'SELECT ''OK: editable ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `tipos_documento` SET `editable` = 0 WHERE `editable` IS NULL OR `editable` = 1;

-- ---------- Tablas nuevas ----------
CREATE TABLE IF NOT EXISTS `alertas_reglas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dias_antes` INT NOT NULL,
  `nombre` VARCHAR(80) NOT NULL,
  `ambito` VARCHAR(20) NOT NULL DEFAULT 'todos',
  `tipo_documento` VARCHAR(60) NOT NULL DEFAULT '',
  `enviar_alerta` TINYINT(1) NOT NULL DEFAULT 1,
  `ocultar_despues` TINYINT(1) NOT NULL DEFAULT 0,
  `mantener_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `nivel_urgencia` ENUM('info','advertencia','critico') NOT NULL DEFAULT 'info',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` INT NOT NULL DEFAULT 0,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regla_dias_ambito_tipo` (`dias_antes`, `empresa_id`, `ambito`, `tipo_documento`),
  KEY `idx_alertas_reglas_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `alertas_reglas`
  (`dias_antes`, `nombre`, `ambito`, `tipo_documento`, `enviar_alerta`, `ocultar_despues`, `mantener_visible`, `nivel_urgencia`, `activo`, `orden`, `empresa_id`, `created_at`)
SELECT * FROM (
  SELECT 45 AS dias_antes, 'Aviso 45 días' AS nombre, 'todos' AS ambito, '' AS tipo_documento, 1 AS enviar_alerta, 0 AS ocultar_despues, 1 AS mantener_visible, 'info' AS nivel_urgencia, 1 AS activo, 1 AS orden, NULL AS empresa_id, NOW() AS created_at
  UNION ALL SELECT 30, 'Aviso 30 días', 'todos', '', 1, 0, 1, 'advertencia', 1, 2, NULL, NOW()
  UNION ALL SELECT 15, 'Aviso 15 días', 'todos', '', 1, 0, 1, 'advertencia', 1, 3, NULL, NOW()
  UNION ALL SELECT 7,  'Aviso 7 días',  'todos', '', 1, 0, 1, 'critico', 1, 4, NULL, NOW()
  UNION ALL SELECT 1,  'Aviso 1 día',   'todos', '', 1, 0, 1, 'critico', 1, 5, NULL, NOW()
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `alertas_reglas` LIMIT 1);

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
WHERE NOT EXISTS (SELECT 1 FROM `notificaciones_flujo` LIMIT 1);

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

-- Verificación rápida
SELECT 'alertas_reglas' AS tabla, COUNT(*) AS filas FROM alertas_reglas
UNION ALL SELECT 'notificaciones_flujo', COUNT(*) FROM notificaciones_flujo
UNION ALL SELECT 'notificaciones', COUNT(*) FROM notificaciones
UNION ALL SELECT 'sistema_logs', COUNT(*) FROM sistema_logs
UNION ALL SELECT 'auditoria_cambios', COUNT(*) FROM auditoria_cambios
UNION ALL SELECT 'verificaciones_historial', COUNT(*) FROM verificaciones_historial;

SELECT 'LISTO' AS estado;
