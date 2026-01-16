-- =====================================================
-- SYNCHRONISATION DES PERMISSIONS
-- =====================================================
-- Ce script synchronise les permissions stockées en JSON
-- dans stock_utilisateur.permissions_entrepots et permissions_points_vente
-- vers les tables de liaison stock_utilisateur_entrepot et stock_utilisateur_point_vente
-- À exécuter si les permissions ne sont pas synchronisées

-- 1. Synchroniser les permissions d'entrepôts
-- Supprimer les anciennes permissions pour éviter les doublons
DELETE FROM stock_utilisateur_entrepot;

-- Insérer les permissions depuis le JSON
INSERT INTO stock_utilisateur_entrepot (id_utilisateur, id_entrepot)
SELECT 
    u.id_utilisateur,
    CAST(JSON_EXTRACT(u.permissions_entrepots, CONCAT('$[', idx.n, ']')) AS UNSIGNED) AS id_entrepot
FROM stock_utilisateur u
CROSS JOIN (
    SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION 
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION
    SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
) idx
WHERE u.permissions_entrepots IS NOT NULL 
  AND u.permissions_entrepots != 'NULL'
  AND u.permissions_entrepots != '[]'
  AND JSON_EXTRACT(u.permissions_entrepots, CONCAT('$[', idx.n, ']')) IS NOT NULL
  AND CAST(JSON_EXTRACT(u.permissions_entrepots, CONCAT('$[', idx.n, ']')) AS UNSIGNED) > 0;

-- 2. Synchroniser les permissions de points de vente
-- Supprimer les anciennes permissions pour éviter les doublons
DELETE FROM stock_utilisateur_point_vente;

-- Insérer les permissions depuis le JSON
INSERT INTO stock_utilisateur_point_vente (id_utilisateur, id_point_vente)
SELECT 
    u.id_utilisateur,
    CAST(JSON_EXTRACT(u.permissions_points_vente, CONCAT('$[', idx.n, ']')) AS UNSIGNED) AS id_point_vente
FROM stock_utilisateur u
CROSS JOIN (
    SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION 
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION
    SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
) idx
WHERE u.permissions_points_vente IS NOT NULL 
  AND u.permissions_points_vente != 'NULL'
  AND u.permissions_points_vente != '[]'
  AND JSON_EXTRACT(u.permissions_points_vente, CONCAT('$[', idx.n, ']')) IS NOT NULL
  AND CAST(JSON_EXTRACT(u.permissions_points_vente, CONCAT('$[', idx.n, ']')) AS UNSIGNED) > 0;

-- Vérification
SELECT 
    u.id_utilisateur,
    u.username,
    u.permissions_entrepots AS json_entrepots,
    GROUP_CONCAT(ue.id_entrepot) AS entrepots_table,
    u.permissions_points_vente AS json_points_vente,
    GROUP_CONCAT(upv.id_point_vente) AS points_vente_table
FROM stock_utilisateur u
LEFT JOIN stock_utilisateur_entrepot ue ON u.id_utilisateur = ue.id_utilisateur
LEFT JOIN stock_utilisateur_point_vente upv ON u.id_utilisateur = upv.id_utilisateur
WHERE u.role != 'Admin'
GROUP BY u.id_utilisateur;

