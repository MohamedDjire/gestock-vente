-- =====================================================
-- MISE À JOUR DES FORFAITS AVEC NOUVEAUX PRIX ET LIMITES
-- =====================================================
-- Ce script met à jour la table stock_forfait pour ajouter les colonnes de limites
-- et crée les 4 nouveaux forfaits avec leurs caractéristiques

-- 1. Ajouter les colonnes de limites si elles n'existent pas
-- NOTE: Si vous obtenez une erreur "Duplicate column name", exécutez d'abord add_forfait_columns.sql
-- ou ignorez cette erreur si les colonnes existent déjà

-- Colonne max_utilisateurs
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'stock_forfait' 
    AND COLUMN_NAME = 'max_utilisateurs');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `stock_forfait` ADD COLUMN `max_utilisateurs` INT(11) DEFAULT NULL COMMENT ''Nombre maximum d''utilisateurs (en plus de l''admin)''',
    'SELECT 1');
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
    'SELECT 1');
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
    'SELECT 1');
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
    'SELECT 1');
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
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Désactiver les anciens forfaits
UPDATE `stock_forfait` SET `actif` = 0 WHERE `actif` = 1;

-- 3. Insérer les 4 nouveaux forfaits
INSERT INTO `stock_forfait` (
    `nom_forfait`, 
    `prix`, 
    `duree_jours`, 
    `description`, 
    `max_utilisateurs`, 
    `max_entrepots`, 
    `max_points_vente`, 
    `peut_nommer_admin`, 
    `fonctionnalites_avancees`,
    `actif`
) VALUES
-- Forfait Starter - 2500 FCFA
(
    'Starter',
    2500.00,
    30,
    'Forfait idéal pour démarrer : 1 utilisateur + admin, 1 entrepôt, 1 point de vente',
    1,
    1,
    1,
    0,
    NULL,
    1
),
-- Forfait Standard - 4000 FCFA
(
    'Standard',
    4000.00,
    30,
    'Pour les petites équipes : 2-3 utilisateurs + admin, 2-3 entrepôts et points de vente',
    3,
    3,
    3,
    0,
    NULL,
    1
),
-- Forfait Business - 7000 FCFA
(
    'Business',
    7000.00,
    30,
    'Pour les entreprises en croissance : jusqu''à 6 utilisateurs + admin, 5-6 entrepôts et points de vente, fonctionnalités avancées',
    6,
    6,
    6,
    0,
    '["Rapports avancés", "Export de données", "API access", "Support prioritaire"]',
    1
),
-- Forfait Enterprise - 10000 FCFA
(
    'Enterprise',
    10000.00,
    30,
    'Solution complète pour grandes entreprises : jusqu''à 10-15 utilisateurs + admin, nommer un autre admin, 10-15 entrepôts et points de vente, toutes les fonctionnalités',
    15,
    15,
    15,
    1,
    '["Rapports avancés", "Export de données", "API access", "Support prioritaire", "Gestion multi-admins", "Personnalisation avancée", "Intégrations tierces"]',
    1
)
ON DUPLICATE KEY UPDATE 
    `prix` = VALUES(`prix`),
    `description` = VALUES(`description`),
    `max_utilisateurs` = VALUES(`max_utilisateurs`),
    `max_entrepots` = VALUES(`max_entrepots`),
    `max_points_vente` = VALUES(`max_points_vente`),
    `peut_nommer_admin` = VALUES(`peut_nommer_admin`),
    `fonctionnalites_avancees` = VALUES(`fonctionnalites_avancees`),
    `actif` = VALUES(`actif`);

-- 4. Afficher les forfaits créés
SELECT 
    id_forfait,
    nom_forfait,
    prix,
    duree_jours,
    max_utilisateurs,
    max_entrepots,
    max_points_vente,
    peut_nommer_admin,
    fonctionnalites_avancees,
    actif
FROM stock_forfait
WHERE actif = 1
ORDER BY prix ASC;

