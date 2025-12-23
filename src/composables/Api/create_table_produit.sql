-- =====================================================
-- TABLE PRODUIT
-- =====================================================
-- Script SQL pour créer la table PRODUIT
-- À exécuter dans votre base de données

CREATE TABLE IF NOT EXISTS `stock_produit` (
  `id_produit` INT(11) NOT NULL AUTO_INCREMENT,
  `code_produit` VARCHAR(50) NOT NULL,
  `nom` VARCHAR(200) NOT NULL,
  `id_categorie` INT(11) DEFAULT NULL,
  `prix_achat` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `prix_vente` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantite_stock` INT(11) NOT NULL DEFAULT 0,
  `seuil_minimum` INT(11) NOT NULL DEFAULT 0,
  `date_expiration` DATE DEFAULT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `actif` BOOLEAN NOT NULL DEFAULT 1,
  `id_entreprise` INT(11) NOT NULL,
  PRIMARY KEY (`id_produit`),
  UNIQUE KEY `unique_code_produit_entreprise` (`code_produit`, `id_entreprise`),
  KEY `idx_categorie` (`id_categorie`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_actif` (`actif`),
  KEY `idx_code_produit` (`code_produit`),
  CONSTRAINT `fk_produit_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INDEX POUR OPTIMISATION
-- =====================================================
CREATE INDEX `idx_produit_stock_faible` ON `stock_produit` (`quantite_stock`, `seuil_minimum`, `actif`);
CREATE INDEX `idx_produit_expiration` ON `stock_produit` (`date_expiration`, `actif`);
