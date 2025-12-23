-- =====================================================
-- TABLES POUR LA GESTION DU STOCK
-- =====================================================
-- Script SQL pour créer les tables de gestion de stock
-- À exécuter dans votre base de données

-- =====================================================
-- TABLE ENTREE_STOCK
-- =====================================================
CREATE TABLE IF NOT EXISTS `stock_entree` (
  `id_entree` INT(11) NOT NULL AUTO_INCREMENT,
  `id_produit` INT(11) NOT NULL,
  `quantite` INT(11) NOT NULL,
  `prix_unitaire` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `date_entree` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_fournisseur` INT(11) DEFAULT NULL,
  `id_user` INT(11) NOT NULL,
  `id_entreprise` INT(11) NOT NULL,
  `numero_bon` VARCHAR(50) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_entree`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_fournisseur` (`id_fournisseur`),
  KEY `idx_user` (`id_user`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_date_entree` (`date_entree`),
  CONSTRAINT `fk_entree_produit` FOREIGN KEY (`id_produit`) 
    REFERENCES `stock_produit` (`id_produit`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_entree_user` FOREIGN KEY (`id_user`) 
    REFERENCES `stock_utilisateur` (`id_utilisateur`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_entree_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE SORTIE_STOCK
-- =====================================================
CREATE TABLE IF NOT EXISTS `stock_sortie` (
  `id_sortie` INT(11) NOT NULL AUTO_INCREMENT,
  `id_produit` INT(11) NOT NULL,
  `quantite` INT(11) NOT NULL,
  `type_sortie` ENUM('vente', 'perte', 'transfert', 'retour', 'autre') NOT NULL DEFAULT 'vente',
  `date_sortie` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motif` TEXT DEFAULT NULL,
  `id_user` INT(11) NOT NULL,
  `id_entreprise` INT(11) NOT NULL,
  `prix_unitaire` DECIMAL(10,2) DEFAULT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_sortie`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_user` (`id_user`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_type_sortie` (`type_sortie`),
  KEY `idx_date_sortie` (`date_sortie`),
  CONSTRAINT `fk_sortie_produit` FOREIGN KEY (`id_produit`) 
    REFERENCES `stock_produit` (`id_produit`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sortie_user` FOREIGN KEY (`id_user`) 
    REFERENCES `stock_utilisateur` (`id_utilisateur`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sortie_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE ALERTE
-- =====================================================
CREATE TABLE IF NOT EXISTS `stock_alerte` (
  `id_alerte` INT(11) NOT NULL AUTO_INCREMENT,
  `id_produit` INT(11) NOT NULL,
  `id_entreprise` INT(11) NOT NULL,
  `type_alerte` ENUM('stock_faible', 'rupture', 'expiration', 'autre') NOT NULL,
  `message` TEXT NOT NULL,
  `date_alerte` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `vue` BOOLEAN NOT NULL DEFAULT 0,
  `date_vue` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_alerte`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_type_alerte` (`type_alerte`),
  KEY `idx_vue` (`vue`),
  KEY `idx_date_alerte` (`date_alerte`),
  CONSTRAINT `fk_alerte_produit` FOREIGN KEY (`id_produit`) 
    REFERENCES `stock_produit` (`id_produit`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_alerte_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS POUR MISE À JOUR AUTOMATIQUE DU STOCK
-- =====================================================

-- Trigger après insertion d'une entrée : augmenter le stock
DELIMITER $$
CREATE TRIGGER `trg_after_entree_stock` 
AFTER INSERT ON `stock_entree`
FOR EACH ROW
BEGIN
  UPDATE stock_produit 
  SET quantite_stock = quantite_stock + NEW.quantite
  WHERE id_produit = NEW.id_produit;
END$$

-- Trigger après insertion d'une sortie : diminuer le stock
CREATE TRIGGER `trg_after_sortie_stock` 
AFTER INSERT ON `stock_sortie`
FOR EACH ROW
BEGIN
  UPDATE stock_produit 
  SET quantite_stock = GREATEST(0, quantite_stock - NEW.quantite)
  WHERE id_produit = NEW.id_produit;
END$$

-- Trigger pour créer des alertes automatiques
CREATE TRIGGER `trg_check_stock_after_update` 
AFTER UPDATE ON `stock_produit`
FOR EACH ROW
BEGIN
  DECLARE entreprise_id INT;
  SET entreprise_id = NEW.id_entreprise;
  
  -- Alerte si stock en rupture
  IF NEW.quantite_stock = 0 AND OLD.quantite_stock > 0 THEN
    INSERT INTO stock_alerte (id_produit, id_entreprise, type_alerte, message)
    VALUES (NEW.id_produit, entreprise_id, 'rupture', 
      CONCAT('Rupture de stock pour le produit: ', NEW.nom));
  END IF;
  
  -- Alerte si stock faible (atteint le seuil minimum)
  IF NEW.quantite_stock <= NEW.seuil_minimum AND NEW.quantite_stock > 0 
     AND (OLD.quantite_stock > NEW.seuil_minimum OR OLD.quantite_stock IS NULL) THEN
    INSERT INTO stock_alerte (id_produit, id_entreprise, type_alerte, message)
    VALUES (NEW.id_produit, entreprise_id, 'stock_faible', 
      CONCAT('Stock faible pour le produit: ', NEW.nom, ' (', NEW.quantite_stock, ' unités restantes)'));
  END IF;
  
  -- Alerte si date d'expiration proche (dans les 7 jours)
  IF NEW.date_expiration IS NOT NULL 
     AND NEW.date_expiration <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
     AND NEW.date_expiration > CURDATE() THEN
    INSERT INTO stock_alerte (id_produit, id_entreprise, type_alerte, message)
    VALUES (NEW.id_produit, entreprise_id, 'expiration', 
      CONCAT('Produit ', NEW.nom, ' expire le ', NEW.date_expiration));
  END IF;
END$$
DELIMITER ;
