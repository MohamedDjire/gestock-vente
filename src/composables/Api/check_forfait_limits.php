<?php
/**
 * Fonctions pour vérifier les limites des forfaits
 * À inclure dans les APIs après l'authentification
 */

if (!function_exists('checkForfaitLimits')) {
    /**
     * Vérifie les limites du forfait actif d'une entreprise
     * @param PDO $bdd Connexion à la base de données
     * @param int $enterpriseId ID de l'entreprise
     * @return array|null Tableau avec les limites ou null si aucun forfait actif
     */
    function checkForfaitLimits($bdd, $enterpriseId) {
        // Récupérer le forfait actif de l'entreprise
        $stmt = $bdd->prepare("
            SELECT 
                f.id_forfait,
                f.nom_forfait,
                f.max_utilisateurs,
                f.max_entrepots,
                f.max_points_vente,
                f.peut_nommer_admin,
                f.fonctionnalites_avancees
            FROM stock_abonnement a
            INNER JOIN stock_forfait f ON a.id_forfait = f.id_forfait
            WHERE a.id_entreprise = :id_entreprise 
            AND a.statut = 'actif'
            AND f.actif = 1
            ORDER BY a.date_fin DESC
            LIMIT 1
        ");
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $forfait = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$forfait) {
            return null;
        }
        
        return [
            'id_forfait' => $forfait['id_forfait'],
            'nom_forfait' => $forfait['nom_forfait'],
            'max_utilisateurs' => $forfait['max_utilisateurs'],
            'max_entrepots' => $forfait['max_entrepots'],
            'max_points_vente' => $forfait['max_points_vente'],
            'peut_nommer_admin' => (bool)$forfait['peut_nommer_admin'],
            'fonctionnalites_avancees' => $forfait['fonctionnalites_avancees']
        ];
    }
}

if (!function_exists('checkUserLimit')) {
    /**
     * Vérifie si l'entreprise peut ajouter un utilisateur
     * @param PDO $bdd Connexion à la base de données
     * @param int $enterpriseId ID de l'entreprise
     * @return array ['allowed' => bool, 'current' => int, 'max' => int|null, 'message' => string]
     */
    function checkUserLimit($bdd, $enterpriseId) {
        $limits = checkForfaitLimits($bdd, $enterpriseId);
        
        if (!$limits) {
            return [
                'allowed' => false,
                'current' => 0,
                'max' => null,
                'message' => 'Aucun forfait actif. Veuillez souscrire à un forfait pour ajouter des utilisateurs.'
            ];
        }
        
        // Compter les utilisateurs actifs (en excluant l'admin principal)
        $stmt = $bdd->prepare("
            SELECT COUNT(*) as total
            FROM stock_utilisateur
            WHERE id_entreprise = :id_entreprise
            AND statut = 'actif'
            AND role != 'admin'
            AND role != 'superadmin'
        ");
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentUsers = (int)$result['total'];
        
        $maxUsers = $limits['max_utilisateurs'];
        
        if ($maxUsers === null) {
            // Pas de limite définie
            return [
                'allowed' => true,
                'current' => $currentUsers,
                'max' => null,
                'message' => 'Limite non définie'
            ];
        }
        
        if ($currentUsers >= $maxUsers) {
            return [
                'allowed' => false,
                'current' => $currentUsers,
                'max' => $maxUsers,
                'message' => "Limite d'utilisateurs atteinte. Votre forfait permet {$maxUsers} utilisateur(s) en plus de l'admin. Vous avez actuellement {$currentUsers} utilisateur(s)."
            ];
        }
        
        return [
            'allowed' => true,
            'current' => $currentUsers,
            'max' => $maxUsers,
            'message' => "Vous pouvez ajouter " . ($maxUsers - $currentUsers) . " utilisateur(s) supplémentaire(s)."
        ];
    }
}

if (!function_exists('checkEntrepotLimit')) {
    /**
     * Vérifie si l'entreprise peut ajouter un entrepôt
     * @param PDO $bdd Connexion à la base de données
     * @param int $enterpriseId ID de l'entreprise
     * @return array ['allowed' => bool, 'current' => int, 'max' => int|null, 'message' => string]
     */
    function checkEntrepotLimit($bdd, $enterpriseId) {
        $limits = checkForfaitLimits($bdd, $enterpriseId);
        
        if (!$limits) {
            return [
                'allowed' => false,
                'current' => 0,
                'max' => null,
                'message' => 'Aucun forfait actif. Veuillez souscrire à un forfait pour ajouter des entrepôts.'
            ];
        }
        
        // Compter les entrepôts actifs
        $stmt = $bdd->prepare("
            SELECT COUNT(*) as total
            FROM stock_entrepot
            WHERE id_entreprise = :id_entreprise
            AND statut = 'actif'
        ");
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentEntrepots = (int)$result['total'];
        
        $maxEntrepots = $limits['max_entrepots'];
        
        if ($maxEntrepots === null) {
            return [
                'allowed' => true,
                'current' => $currentEntrepots,
                'max' => null,
                'message' => 'Limite non définie'
            ];
        }
        
        if ($currentEntrepots >= $maxEntrepots) {
            return [
                'allowed' => false,
                'current' => $currentEntrepots,
                'max' => $maxEntrepots,
                'message' => "Limite d'entrepôts atteinte. Votre forfait permet {$maxEntrepots} entrepôt(s). Vous avez actuellement {$currentEntrepots} entrepôt(s)."
            ];
        }
        
        return [
            'allowed' => true,
            'current' => $currentEntrepots,
            'max' => $maxEntrepots,
            'message' => "Vous pouvez ajouter " . ($maxEntrepots - $currentEntrepots) . " entrepôt(s) supplémentaire(s)."
        ];
    }
}

if (!function_exists('checkPointVenteLimit')) {
    /**
     * Vérifie si l'entreprise peut ajouter un point de vente
     * @param PDO $bdd Connexion à la base de données
     * @param int $enterpriseId ID de l'entreprise
     * @return array ['allowed' => bool, 'current' => int, 'max' => int|null, 'message' => string]
     */
    function checkPointVenteLimit($bdd, $enterpriseId) {
        $limits = checkForfaitLimits($bdd, $enterpriseId);
        
        if (!$limits) {
            return [
                'allowed' => false,
                'current' => 0,
                'max' => null,
                'message' => 'Aucun forfait actif. Veuillez souscrire à un forfait pour ajouter des points de vente.'
            ];
        }
        
        // Compter les points de vente actifs
        $stmt = $bdd->prepare("
            SELECT COUNT(*) as total
            FROM stock_point_vente
            WHERE id_entreprise = :id_entreprise
            AND statut = 'actif'
        ");
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentPV = (int)$result['total'];
        
        $maxPV = $limits['max_points_vente'];
        
        if ($maxPV === null) {
            return [
                'allowed' => true,
                'current' => $currentPV,
                'max' => null,
                'message' => 'Limite non définie'
            ];
        }
        
        if ($currentPV >= $maxPV) {
            return [
                'allowed' => false,
                'current' => $currentPV,
                'max' => $maxPV,
                'message' => "Limite de points de vente atteinte. Votre forfait permet {$maxPV} point(s) de vente. Vous avez actuellement {$currentPV} point(s) de vente."
            ];
        }
        
        return [
            'allowed' => true,
            'current' => $currentPV,
            'max' => $maxPV,
            'message' => "Vous pouvez ajouter " . ($maxPV - $currentPV) . " point(s) de vente supplémentaire(s)."
        ];
    }
}

if (!function_exists('checkCanNommerAdmin')) {
    /**
     * Vérifie si l'entreprise peut nommer un autre administrateur
     * @param PDO $bdd Connexion à la base de données
     * @param int $enterpriseId ID de l'entreprise
     * @return bool
     */
    function checkCanNommerAdmin($bdd, $enterpriseId) {
        $limits = checkForfaitLimits($bdd, $enterpriseId);
        
        if (!$limits) {
            return false;
        }
        
        return (bool)$limits['peut_nommer_admin'];
    }
}

