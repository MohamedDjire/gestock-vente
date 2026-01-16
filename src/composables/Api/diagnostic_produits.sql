-- =====================================================
-- DIAGNOSTIC PRODUITS ET ENTREPÔTS
-- =====================================================
-- Script pour vérifier pourquoi les produits ne s'affichent pas
-- À exécuter dans phpMyAdmin

-- 1. Vérifier l'utilisateur "toure" (ID 19)
SELECT 
    id_utilisateur,
    username,
    role,
    permissions_entrepots,
    permissions_points_vente
FROM stock_utilisateur
WHERE id_utilisateur = 19;

-- 2. Vérifier les permissions dans la table de liaison
SELECT 
    ue.id_utilisateur,
    ue.id_entrepot,
    e.nom_entrepot
FROM stock_utilisateur_entrepot ue
INNER JOIN stock_entrepot e ON ue.id_entrepot = e.id_entrepot
WHERE ue.id_utilisateur = 19;

-- 3. Vérifier les entrepôts de l'entreprise
SELECT 
    id_entrepot,
    nom_entrepot,
    id_entreprise,
    actif
FROM stock_entrepot
WHERE id_entreprise = 1;

-- 4. Vérifier les produits de l'entreprise avec leur entrepôt
SELECT 
    id_produit,
    nom,
    entrepot,
    actif,
    quantite_stock,
    id_entreprise
FROM stock_produit
WHERE id_entreprise = 1
ORDER BY entrepot, nom;

-- 5. Vérifier combien de produits sont dans chaque entrepôt
SELECT 
    entrepot,
    COUNT(*) as nombre_produits,
    SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as produits_actifs
FROM stock_produit
WHERE id_entreprise = 1
GROUP BY entrepot;

-- 6. Vérifier si les noms d'entrepôts correspondent
SELECT DISTINCT
    e.id_entrepot,
    e.nom_entrepot as nom_entrepot_table,
    p.entrepot as nom_entrepot_produit,
    COUNT(p.id_produit) as nombre_produits
FROM stock_entrepot e
LEFT JOIN stock_produit p ON LOWER(TRIM(e.nom_entrepot)) = LOWER(TRIM(p.entrepot)) AND p.id_entreprise = e.id_entreprise
WHERE e.id_entreprise = 1
GROUP BY e.id_entrepot, e.nom_entrepot, p.entrepot;

-- 7. Vérifier les produits pour l'entrepôt ID 2 (si c'est l'entrepôt de "toure")
SELECT 
    p.id_produit,
    p.nom,
    p.entrepot,
    p.actif,
    e.id_entrepot,
    e.nom_entrepot
FROM stock_produit p
LEFT JOIN stock_entrepot e ON LOWER(TRIM(p.entrepot)) = LOWER(TRIM(e.nom_entrepot)) AND e.id_entreprise = p.id_entreprise
WHERE e.id_entrepot = 2 OR p.entrepot LIKE '%meuble%'
ORDER BY p.entrepot, p.nom;

