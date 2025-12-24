-- =====================================================
-- AJOUT COLONNE ENTREPOT_DESTINATION
-- =====================================================
-- Script SQL pour ajouter la colonne entrepot_destination à la table stock_sortie
-- À exécuter dans votre base de données

ALTER TABLE `stock_sortie` 
ADD COLUMN IF NOT EXISTS `entrepot_destination` VARCHAR(100) DEFAULT NULL 
AFTER `motif`;
