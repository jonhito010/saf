-- Campo para indicar si el CFDI incluye complemento Carta Porte
ALTER TABLE `comprobantes`
  ADD COLUMN `llevaCartaPorte` tinyint(1) NOT NULL DEFAULT 0
  COMMENT '1 = incluye complemento Carta Porte'
  AFTER `formaPago`;

ALTER TABLE `comprobantes`
  ADD KEY `idx_comprobantes_carta_porte` (`llevaCartaPorte`);
