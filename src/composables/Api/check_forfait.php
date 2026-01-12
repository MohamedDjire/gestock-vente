<?php
/**
 * Helper pour vérifier le forfait d'une entreprise
 * À inclure dans les APIs après l'authentification
 * Gère les périodes de grâce (2 jours après expiration)
 */

if (!function_exists('checkForfaitActifWithGrace')) {
    /**
     * Vérifie si l'entreprise a un forfait actif avec gestion de la période de grâce
     * Cette fonction doit être appelée pour toutes les actions sauf la connexion
     * Retourne un tableau avec l'état du forfait ou lance une exception
     */
    function checkForfaitActifWithGrace($bdd, $enterpriseId, $allowGracePeriod = false) {
        // Vérifier s'il y a un abonnement actif ou expiré récemment
        $stmt = $bdd->prepare("
            SELECT id_abonnement, date_fin, statut 
            FROM stock_abonnement 
            WHERE id_entreprise = :id_entreprise 
            AND (statut = 'actif' OR statut = 'expire')
            ORDER BY date_fin DESC 
            LIMIT 1
        ");
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $abonnement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$abonnement) {
            throw new Exception("Aucun forfait actif. Veuillez souscrire à un forfait pour continuer.", 403);
        }
        
        $dateFin = new DateTime($abonnement['date_fin']);
        $now = new DateTime();
        $diff = $now->diff($dateFin);
        $joursRestants = (int)$diff->format('%r%a'); // Négatif si expiré
        
        // Calculer la date de fin de période de grâce (2 jours après expiration)
        $dateGraceFin = clone $dateFin;
        $dateGraceFin->modify('+2 days');
        
        // Si le forfait est expiré depuis plus de 2 jours, bloquer complètement
        if ($now > $dateGraceFin) {
            // Mettre à jour le statut si ce n'est pas déjà fait
            if ($abonnement['statut'] !== 'expire') {
                $updateStmt = $bdd->prepare("
                    UPDATE stock_abonnement 
                    SET statut = 'expire' 
                    WHERE id_abonnement = :id_abonnement
                ");
                $updateStmt->execute(['id_abonnement' => $abonnement['id_abonnement']]);
            }
            
            throw new Exception("Votre forfait a expiré et la période de grâce est terminée. Veuillez renouveler votre abonnement immédiatement pour continuer.", 403);
        }
        
        // Si le forfait est expiré mais dans la période de grâce (2 jours)
        if ($dateFin < $now && $now <= $dateGraceFin) {
            // Autoriser l'accès seulement si allowGracePeriod est true (pour certaines actions)
            if (!$allowGracePeriod) {
                throw new Exception("Votre forfait a expiré. Vous avez encore " . ($dateGraceFin->diff($now)->days + 1) . " jour(s) pour renouveler votre abonnement avant que toutes les fonctionnalités soient bloquées.", 403);
            }
            // Dans la période de grâce, on autorise mais avec un avertissement
            return [
                'actif' => false,
                'en_grace' => true,
                'jours_grace_restants' => $dateGraceFin->diff($now)->days + 1
            ];
        }
        
        // Forfait encore actif
        return [
            'actif' => true,
            'jours_restants' => $joursRestants
        ];
    }
    
    /**
     * Obtient l'état détaillé du forfait pour l'affichage
     */
    function getForfaitStatus($bdd, $enterpriseId) {
        $stmt = $bdd->prepare("
            SELECT id_abonnement, date_fin, statut 
            FROM stock_abonnement 
            WHERE id_entreprise = :id_entreprise 
            AND (statut = 'actif' OR statut = 'expire')
            ORDER BY date_fin DESC 
            LIMIT 1
        ");
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $abonnement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$abonnement) {
            return [
                'etat' => 'no_subscription',
                'actif' => false,
                'jours_restants' => null,
                'message' => 'Aucun forfait actif'
            ];
        }
        
        $dateFin = new DateTime($abonnement['date_fin']);
        $now = new DateTime();
        $diff = $now->diff($dateFin);
        $joursRestants = (int)$diff->format('%r%a');
        
        // Calculer la date de fin de période de grâce
        $dateGraceFin = clone $dateFin;
        $dateGraceFin->modify('+2 days');
        
        // État : bloqué (après période de grâce)
        if ($now > $dateGraceFin) {
            return [
                'etat' => 'bloque',
                'actif' => false,
                'jours_restants' => 0,
                'jours_grace_restants' => 0,
                'message' => 'Forfait expiré - Accès bloqué',
                'date_fin' => $abonnement['date_fin']
            ];
        }
        
        // État : période de grâce (expiré mais moins de 2 jours)
        if ($dateFin < $now && $now <= $dateGraceFin) {
            $joursGraceRestants = $dateGraceFin->diff($now)->days + 1;
            return [
                'etat' => 'grace',
                'actif' => false,
                'jours_restants' => 0,
                'jours_grace_restants' => $joursGraceRestants,
                'message' => "Période de grâce - {$joursGraceRestants} jour(s) restant(s)",
                'date_fin' => $abonnement['date_fin']
            ];
        }
        
        // État : warning (5 jours ou moins avant expiration)
        if ($joursRestants <= 5 && $joursRestants > 0) {
            return [
                'etat' => 'warning',
                'actif' => true,
                'jours_restants' => $joursRestants,
                'message' => "Attention : {$joursRestants} jour(s) restant(s)",
                'date_fin' => $abonnement['date_fin']
            ];
        }
        
        // État : actif normal
        return [
            'etat' => 'actif',
            'actif' => true,
            'jours_restants' => $joursRestants,
            'message' => 'Forfait actif',
            'date_fin' => $abonnement['date_fin']
        ];
    }
}
