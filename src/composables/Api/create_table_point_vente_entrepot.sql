-- =====================================================
-- TABLE RAVITAILLEMENT : Point de vente ↔ Entrepôts
-- =====================================================
-- Un point de vente peut se ravitailler dans plusieurs entrepôts.
-- Un entrepôt peut servir plusieurs points de vente.

CREATE TABLE IF NOT EXISTS `stock_point_vente_entrepot` (
  `id_point_vente` INT(11) NOT NULL,
  `id_entrepot` INT(11) NOT NULL,
  PRIMARY KEY (`id_point_vente`, `id_entrepot`),
  KEY `idx_pve_entrepot` (`id_entrepot`),
  CONSTRAINT `fk_pve_point_vente` FOREIGN KEY (`id_point_vente`) 
    REFERENCES `stock_point_vente` (`id_point_vente`) ON DELETE CASCADE,
  CONSTRAINT `fk_pve_entrepot` FOREIGN KEY (`id_entrepot`) 
    REFERENCES `stock_entrepot` (`id_entrepot`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
