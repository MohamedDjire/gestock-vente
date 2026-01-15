-- Script SQL pour ajouter la colonne image à la table stock_produit
-- À exécuter dans votre base de données

ALTER TABLE `stock_produit` 
ADD COLUMN IF NOT EXISTS `image` VARCHAR(500) DEFAULT NULL COMMENT 'URL de l\'image du produit stockée sur Cloudinary';

-- Ajouter un index pour améliorer les performances de recherche
CREATE INDEX IF NOT EXISTS `idx_produit_image` ON `stock_produit` (`image`);

