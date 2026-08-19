CREATE TABLE `catalogo_mercancias` (
  `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `bienes_transp`         VARCHAR(8)      NOT NULL COMMENT 'Clave SAT c_ClaveProdServCP',
  `descripcion`           VARCHAR(255)    NOT NULL,
  `clave_unidad`          VARCHAR(3)      NOT NULL COMMENT 'Clave SAT c_ClaveUnidad',
  `peso_en_kg`            DECIMAL(12,3)   DEFAULT NULL COMMENT 'Peso unitario de referencia en kg',
  `material_peligroso`    TINYINT(1)      NOT NULL DEFAULT 0,
  `cve_material_peligroso` VARCHAR(4)     DEFAULT NULL COMMENT 'Clave SAT c_MaterialPeligroso (solo si es peligroso)',
  `embalaje`              VARCHAR(4)      DEFAULT NULL COMMENT 'Clave SAT c_TipoEmbalaje (solo si es peligroso)',
  `created`               DATETIME        DEFAULT NULL,
  `modified`              DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bienes_transp` (`bienes_transp`),
  KEY `idx_descripcion`   (`descripcion`(80))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo propio de mercancías para Carta Porte autotransporte';
