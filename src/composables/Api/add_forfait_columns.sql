-- =====================================================
-- AJOUT DES COLONNES DE LIMITES À LA TABLE stock_forfait
-- =====================================================
-- Ce script ajoute les colonnes nécessaires pour gérer les limites des forfaits
-- À exécuter AVANT update_forfaits.sql si les colonnes n'existent pas encore

-- Vérifier et ajouter chaque colonne individuellement
-- (MySQL ne supporte pas IF NOT EXISTS pour ALTER TABLE ADD COLUMN)

-- Colonne max_utilisateurs
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'stock_forfait' 
    AND COLUMN_NAME = 'max_utilisateurs');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `stock_forfait` ADD COLUMN `max_utilisateurs` INT(11) DEFAULT NULL COMMENT ''Nombre maximum d''utilisateurs (en plus de l''admin)''',
    'SELECT ''Colonne max_utilisateurs existe déjà'' AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colonne max_entrepots
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'stock_forfait' 
    AND COLUMN_NAME = 'max_entrepots');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `stock_forfait` ADD COLUMN `max_entrepots` INT(11) DEFAULT NULL COMMENT ''Nombre maximum d''entrepôts''',
    'SELECT ''Colonne max_entrepots existe déjà'' AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colonne max_points_vente
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'stock_forfait' 
    AND COLUMN_NAME = 'max_points_vente');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `stock_forfait` ADD COLUMN `max_points_vente` INT(11) DEFAULT NULL COMMENT ''Nombre maximum de points de vente''',
    'SELECT ''Colonne max_points_vente existe déjà'' AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colonne peut_nommer_admin
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'stock_forfait' 
    AND COLUMN_NAME = 'peut_nommer_admin');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `stock_forfait` ADD COLUMN `peut_nommer_admin` TINYINT(1) DEFAULT 0 COMMENT ''Peut nommer un autre administrateur''',
    'SELECT ''Colonne peut_nommer_admin existe déjà'' AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colonne fonctionnalites_avancees
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'stock_forfait' 
    AND COLUMN_NAME = 'fonctionnalites_avancees');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `stock_forfait` ADD COLUMN `fonctionnalites_avancees` TEXT DEFAULT NULL COMMENT ''Liste des fonctionnalités avancées (JSON)''',
    'SELECT ''Colonne fonctionnalites_avancees existe déjà'' AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Colonnes ajoutées avec succès' AS resultat;

