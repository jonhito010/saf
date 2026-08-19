ALTER TABLE `ubicaciones`
  ADD COLUMN `pais` VARCHAR(3) NULL DEFAULT 'MEX' COMMENT 'Clave ISO 3166-1 alpha-3 (SAT c_Pais)' AFTER `localidad`;
