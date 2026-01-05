-- =====================================================
-- SCRIPT D'INSERTION D'UN FORFAIT DE TEST
-- =====================================================
-- Ce script crée un forfait de test pour une entreprise
-- À exécuter dans votre base de données après avoir créé les tables

-- 1. Vérifier/Créer les forfaits de base s'ils n'existent pas
INSERT INTO `stock_forfait` (`nom_forfait`, `prix`, `duree_jours`, `description`, `actif`) VALUES
('Forfait Basique', 3000.00, 30, 'Forfait mensuel de base avec fonctionnalités essentielles', 1),
('Forfait Premium', 5000.00, 30, 'Forfait mensuel premium avec toutes les fonctionnalités', 1)
ON DUPLICATE KEY UPDATE `nom_forfait` = VALUES(`nom_forfait`);

-- 2. Créer un abonnement de test pour la première entreprise trouvée
-- Remplacez 1 par l'ID de votre entreprise si nécessaire
SET @id_entreprise = (SELECT id_entreprise FROM stock_entreprise LIMIT 1);
SET @id_forfait = (SELECT id_forfait FROM stock_forfait WHERE nom_forfait = 'Forfait Basique' LIMIT 1);
SET @date_debut = NOW();
SET @date_fin = DATE_ADD(@date_debut, INTERVAL 30 DAY);

-- Vérifier si l'entreprise a déjà un abonnement actif
SET @abonnement_existant = (
    SELECT COUNT(*) 
    FROM stock_abonnement 
    WHERE id_entreprise = @id_entreprise 
    AND statut = 'actif'
);

-- Créer l'abonnement seulement s'il n'en existe pas déjà un
INSERT INTO `stock_abonnement` (
    `id_entreprise`, 
    `id_forfait`, 
    `date_debut`, 
    `date_fin`, 
    `prix_paye`, 
    `statut`
)
SELECT 
    @id_entreprise,
    @id_forfait,
    @date_debut,
    @date_fin,
    3000.00,
    'actif'
WHERE @abonnement_existant = 0;

-- Afficher le résultat
SELECT 
    a.id_abonnement,
    a.id_entreprise,
    e.nom_entreprise,
    f.nom_forfait,
    a.date_debut,
    a.date_fin,
    a.prix_paye,
    a.statut,
    DATEDIFF(a.date_fin, NOW()) AS jours_restants
FROM stock_abonnement a
INNER JOIN stock_entreprise e ON a.id_entreprise = e.id_entreprise
INNER JOIN stock_forfait f ON a.id_forfait = f.id_forfait
WHERE a.id_entreprise = @id_entreprise
ORDER BY a.date_fin DESC
LIMIT 1;
