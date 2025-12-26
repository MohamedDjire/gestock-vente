-- =====================================================
-- TABLE POINT DE VENTE
-- =====================================================
-- Script SQL pour créer la table des points de vente
-- Chaque point de vente est relié à un entrepôt

CREATE TABLE IF NOT EXISTS `stock_point_vente` (
  `id_point_vente` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_point_vente` VARCHAR(100) NOT NULL,
  `id_entrepot` INT(11) DEFAULT NULL,
  `adresse` TEXT DEFAULT NULL,
  `ville` VARCHAR(100) DEFAULT NULL,
  `pays` VARCHAR(100) DEFAULT NULL,
  `telephone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `responsable` VARCHAR(100) DEFAULT NULL,
  `id_entreprise` INT(11) NOT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_point_vente`),
  KEY `idx_entrepot` (`id_entrepot`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_point_vente_entrepot` FOREIGN KEY (`id_entrepot`) 
    REFERENCES `stock_entrepot` (`id_entrepot`) 
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_point_vente_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les commandes/ventes liées aux points de vente
CREATE TABLE IF NOT EXISTS `stock_vente` (
  `id_vente` INT(11) NOT NULL AUTO_INCREMENT,
  `id_point_vente` INT(11) NOT NULL,
  `id_produit` INT(11) NOT NULL,
  `quantite` INT(11) NOT NULL,
  `prix_unitaire` DECIMAL(10,2) NOT NULL,
  `montant_total` DECIMAL(10,2) NOT NULL,
  `date_vente` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type_vente` ENUM('vente', 'commande', 'retour', 'livraison', 'expedition') NOT NULL DEFAULT 'vente',
  `statut` ENUM('en_attente', 'en_cours', 'livre', 'annule', 'retourne') NOT NULL DEFAULT 'en_cours',
  `id_client` INT(11) DEFAULT NULL,
  `id_user` INT(11) NOT NULL,
  `id_entreprise` INT(11) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_vente`),
  KEY `idx_point_vente` (`id_point_vente`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_date_vente` (`date_vente`),
  KEY `idx_type_vente` (`type_vente`),
  KEY `idx_statut` (`statut`),
  KEY `idx_entreprise` (`id_entreprise`),
  CONSTRAINT `fk_vente_point_vente` FOREIGN KEY (`id_point_vente`) 
    REFERENCES `stock_point_vente` (`id_point_vente`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_vente_produit` FOREIGN KEY (`id_produit`) 
    REFERENCES `stock_produit` (`id_produit`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_vente_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
