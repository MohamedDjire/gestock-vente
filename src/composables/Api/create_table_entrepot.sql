-- =====================================================
-- TABLE STOCK_ENTREPOT
-- =====================================================
-- Script SQL pour créer la table stock_entrepot
-- À exécuter dans votre base de données

CREATE TABLE IF NOT EXISTS `stock_entrepot` (
  `id_entrepot` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_entrepot` VARCHAR(100) NOT NULL,
  `adresse` TEXT DEFAULT NULL,
  `ville` VARCHAR(100) DEFAULT NULL,
  `pays` VARCHAR(100) DEFAULT NULL,
  `telephone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `responsable` VARCHAR(100) DEFAULT NULL,
  `capacite_max` INT(11) DEFAULT NULL COMMENT 'Capacité maximale en unités',
  `id_entreprise` INT(11) NOT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_entrepot`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_entrepot_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS `idx_entrepot_entreprise_actif` 
  ON `stock_entrepot` (`id_entreprise`, `actif`);
