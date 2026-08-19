-- Relación cuenta (usuario) ↔ empresas (multi-tenant)
CREATE TABLE IF NOT EXISTS `usuario_empresas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `empresa_id` int unsigned NOT NULL,
  `es_propietario` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_empresa` (`usuario_id`, `empresa_id`),
  KEY `idx_usuario_empresas_empresa` (`empresa_id`),
  CONSTRAINT `fk_usuario_empresas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuario_empresas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vincular datos existentes (idempotente vía INSERT IGNORE)
INSERT IGNORE INTO `usuario_empresas` (`usuario_id`, `empresa_id`, `es_propietario`)
SELECT u.id, u.empresa_id, 1
FROM `usuarios` u
WHERE u.empresa_id IS NOT NULL AND u.empresa_id > 0;

UPDATE `empresas` e
INNER JOIN (
  SELECT empresa_id, MIN(id) AS usuario_id
  FROM `usuarios`
  WHERE empresa_id IS NOT NULL AND empresa_id > 0
  GROUP BY empresa_id
) u ON u.empresa_id = e.id
SET e.created_by = COALESCE(e.created_by, u.usuario_id)
WHERE e.created_by IS NULL;
