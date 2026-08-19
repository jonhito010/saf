-- Foto del operador (ruta relativa bajo webroot/uploads)
ALTER TABLE `operadores`
  ADD COLUMN `foto_ruta` varchar(255) NULL DEFAULT NULL AFTER `email`;
