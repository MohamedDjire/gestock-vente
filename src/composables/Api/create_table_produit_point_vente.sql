-- Table de liaison entre produits et points de vente
-- Permet de définir quels produits sont disponibles dans quels points de vente
-- À exécuter dans votre base de données

CREATE TABLE IF NOT EXISTS `stock_produit_point_vente` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_produit` INT(11) NOT NULL,
  `id_point_vente` INT(11) NOT NULL,
  `id_entreprise` INT(11) NOT NULL,
  `quantite_disponible` INT(11) DEFAULT 0 COMMENT 'Quantité disponible spécifiquement dans ce point de vente',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_produit_point_vente` (`id_produit`, `id_point_vente`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_point_vente` (`id_point_vente`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_ppv_produit` FOREIGN KEY (`id_produit`) 
    REFERENCES `stock_produit` (`id_produit`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ppv_point_vente` FOREIGN KEY (`id_point_vente`) 
    REFERENCES `stock_point_vente` (`id_point_vente`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ppv_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initialiser avec les produits existants basés sur l'entrepôt du point de vente
-- Si un point de vente a un entrepôt associé, tous les produits de cet entrepôt sont disponibles dans ce point de vente
INSERT INTO `stock_produit_point_vente` (`id_produit`, `id_point_vente`, `id_entreprise`, `actif`)
SELECT DISTINCT 
  p.id_produit,
  pv.id_point_vente,
  p.id_entreprise,
  1
FROM `stock_produit` p
INNER JOIN `stock_point_vente` pv ON pv.id_entreprise = p.id_entreprise
INNER JOIN `stock_entrepot` e ON e.id_entrepot = pv.id_entrepot AND e.id_entreprise = p.id_entreprise
WHERE p.actif = 1
  AND pv.actif = 1
  AND (p.entrepot IS NULL OR LOWER(TRIM(p.entrepot)) = LOWER(TRIM(e.nom_entrepot)))
ON DUPLICATE KEY UPDATE `actif` = 1;

