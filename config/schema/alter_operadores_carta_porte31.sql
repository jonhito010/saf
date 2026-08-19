-- Agrega campos necesarios para Carta Porte 3.1 (FiguraTransporte/Operador)
-- Nota: ajusta el nombre de tu BD si ejecutas con USE.

ALTER TABLE `operadores`
  ADD COLUMN `rfc` varchar(13) NULL AFTER `curp`,
  ADD COLUMN `num_licencia` varchar(30) NULL AFTER `rfc`,
  ADD COLUMN `num_reg_id_trib_figura` varchar(40) NULL AFTER `num_licencia`,
  ADD COLUMN `residencia_fiscal_figura` varchar(3) NULL AFTER `num_reg_id_trib_figura`,
  ADD COLUMN `domicilio_calle` varchar(255) NULL AFTER `residencia_fiscal_figura`,
  ADD COLUMN `domicilio_numero_exterior` varchar(50) NULL AFTER `domicilio_calle`,
  ADD COLUMN `domicilio_numero_interior` varchar(50) NULL AFTER `domicilio_numero_exterior`,
  ADD COLUMN `domicilio_colonia` varchar(120) NULL AFTER `domicilio_numero_interior`,
  ADD COLUMN `domicilio_localidad` varchar(120) NULL AFTER `domicilio_colonia`,
  ADD COLUMN `domicilio_referencia` varchar(255) NULL AFTER `domicilio_localidad`,
  ADD COLUMN `domicilio_municipio` varchar(120) NULL AFTER `domicilio_referencia`,
  ADD COLUMN `domicilio_estado` varchar(3) NULL AFTER `domicilio_municipio`,
  ADD COLUMN `domicilio_pais` varchar(3) NULL DEFAULT 'MEX' AFTER `domicilio_estado`,
  ADD COLUMN `domicilio_codigo_postal` varchar(10) NULL AFTER `domicilio_pais`;

