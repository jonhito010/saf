-- Multiempresa: clientes y servicios por empresa
-- Ejecutar una vez. Si la columna ya existe, ignorar el error de "Duplicate column".

ALTER TABLE `clientes`
  ADD COLUMN `empresa_id` int unsigned NULL DEFAULT NULL AFTER `id`;

ALTER TABLE `clientes`
  ADD KEY `idx_clientes_empresa` (`empresa_id`);

ALTER TABLE `servicios`
  ADD COLUMN `empresa_id` int unsigned NULL DEFAULT NULL AFTER `id`;

ALTER TABLE `servicios`
  ADD KEY `idx_servicios_empresa` (`empresa_id`);

UPDATE `clientes` c
SET c.empresa_id = (
  SELECT e.id FROM `empresas` e WHERE e.estatus = 'activa' ORDER BY e.id ASC LIMIT 1
)
WHERE c.empresa_id IS NULL;

UPDATE `servicios` s
SET s.empresa_id = (
  SELECT e.id FROM `empresas` e WHERE e.estatus = 'activa' ORDER BY e.id ASC LIMIT 1
)
WHERE s.empresa_id IS NULL;
