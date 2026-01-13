-- Table pour stocker les reçus de vente
-- Script SQL pour créer la table stock_receipt
-- À exécuter dans votre base de données

CREATE TABLE IF NOT EXISTS `stock_receipt` (
  `id_receipt` INT(11) NOT NULL AUTO_INCREMENT,
  `id_vente` INT(11) DEFAULT NULL,
  `id_point_vente` INT(11) NOT NULL,
  `id_entreprise` INT(11) NOT NULL,
  `id_user` INT(11) NOT NULL,
  `date_vente` DATETIME NOT NULL,
  `point_vente_nom` VARCHAR(255) NOT NULL,
  `nombre_articles` INT(11) NOT NULL,
  `sous_total` DECIMAL(10,2) NOT NULL,
  `remise` DECIMAL(10,2) DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `produits_json` TEXT NOT NULL COMMENT 'JSON array des produits vendus',
  `receipt_data` TEXT NOT NULL COMMENT 'Données complètes du reçu en JSON',
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_receipt`),
  KEY `idx_vente` (`id_vente`),
  KEY `idx_point_vente` (`id_point_vente`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_date_vente` (`date_vente`),
  KEY `idx_user` (`id_user`),
  CONSTRAINT `fk_receipt_vente` FOREIGN KEY (`id_vente`) 
    REFERENCES `stock_vente` (`id_vente`) 
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_receipt_point_vente` FOREIGN KEY (`id_point_vente`) 
    REFERENCES `stock_point_vente` (`id_point_vente`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_receipt_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_receipt_user` FOREIGN KEY (`id_user`) 
    REFERENCES `stock_utilisateur` (`id_utilisateur`) 
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

