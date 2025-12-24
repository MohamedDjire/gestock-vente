-- =====================================================
-- AJOUT COLONNE ENTREPOT
-- =====================================================
-- Script SQL pour ajouter la colonne entrepot à la table stock_produit
-- À exécuter dans votre base de données

ALTER TABLE `stock_produit` 
ADD COLUMN IF NOT EXISTS `entrepot` VARCHAR(100) NOT NULL DEFAULT 'Magasin' 
AFTER `actif`;

-- Mettre à jour les produits existants pour qu'ils aient "Magasin" par défaut
UPDATE `stock_produit` SET `entrepot` = 'Magasin' WHERE `entrepot` IS NULL OR `entrepot` = '';
