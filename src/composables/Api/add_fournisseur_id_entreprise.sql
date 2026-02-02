-- =====================================================
-- ISOLATION PAR ENTREPRISE : FOURNISSEURS
-- =====================================================
-- À exécuter sur la base de données pour que chaque
-- entreprise ne voie que ses propres fournisseurs.
-- Tables concernées : stock_fournisseurs (ou fournisseurs)

-- Si la table s'appelle stock_fournisseurs :
ALTER TABLE `stock_fournisseurs`
ADD COLUMN `id_entreprise` INT UNSIGNED NULL DEFAULT NULL AFTER `id`,
ADD INDEX `idx_fournisseurs_id_entreprise` (`id_entreprise`);

-- Attribuer les fournisseurs existants à l'entreprise 1 (à adapter si besoin)
-- UPDATE `stock_fournisseurs` SET `id_entreprise` = 1 WHERE `id_entreprise` IS NULL;

-- Si la table s'appelle fournisseurs (sans préfixe stock_) :
-- ALTER TABLE `fournisseurs`
-- ADD COLUMN `id_entreprise` INT UNSIGNED NULL DEFAULT NULL AFTER `id`,
-- ADD INDEX `idx_fournisseurs_id_entreprise` (`id_entreprise`);
