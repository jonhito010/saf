-- Amplía tipo_documento: el catálogo en PHP supera el ENUM legacy (solo 4 valores).
-- Ejecutar una vez en la BD de SAF (phpMyAdmin o mysql CLI).

ALTER TABLE `documentos_empresa` MODIFY COLUMN `tipo_documento` VARCHAR(60) NOT NULL;
ALTER TABLE `documentos_vehiculo` MODIFY COLUMN `tipo_documento` VARCHAR(60) NOT NULL;
ALTER TABLE `documentos_operador` MODIFY COLUMN `tipo_documento` VARCHAR(60) NOT NULL;