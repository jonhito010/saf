-- Campos opcionales para Carta Porte / registro vehicular (CESDIA)
ALTER TABLE `vehiculos`
  ADD COLUMN `modalidad` varchar(80) NULL DEFAULT NULL AFTER `tipo_carga`,
  ADD COLUMN `tipo_servicio` varchar(80) NULL DEFAULT NULL AFTER `modalidad`;
