-- Transporte de materiales / residuos peligrosos (cumplimiento SICT)
ALTER TABLE empresas
  ADD COLUMN transporte_materiales_peligrosos TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = empresa sujeta a requisitos MP/residuos peligrosos'
  AFTER estatus;
