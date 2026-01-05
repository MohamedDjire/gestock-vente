<?php
/**
 * Helper pour vérifier le forfait d'une entreprise
 * À inclure dans les APIs après l'authentification
 */

if (!function_exists('checkForfaitActif')) {
    /**
     * Vérifie si l'entreprise a un forfait actif
     * Cette fonction doit être appelée pour toutes les actions sauf la connexion
     */
    function checkForfaitActif($bdd, $enterpriseId) {
        // Vérifier s'il y a un abonnement actif
        $stmt = $bdd->prepare("
            SELECT id_abonnement, date_fin, statut 
            FROM stock_abonnement 
            WHERE id_entreprise = :id_entreprise 
            AND statut = 'actif'
            ORDER BY date_fin DESC 
            LIMIT 1
        ");
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $abonnement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$abonnement) {
            throw new Exception("Aucun forfait actif. Veuillez souscrire à un forfait pour continuer.", 403);
        }
        
        // Vérifier si l'abonnement est expiré
        $dateFin = new DateTime($abonnement['date_fin']);
        $now = new DateTime();
        
        if ($dateFin < $now) {
            // Mettre à jour le statut
            $updateStmt = $bdd->prepare("
                UPDATE stock_abonnement 
                SET statut = 'expire' 
                WHERE id_abonnement = :id_abonnement
            ");
            $updateStmt->execute(['id_abonnement' => $abonnement['id_abonnement']]);
            
            throw new Exception("Votre forfait a expiré. Veuillez renouveler votre abonnement pour continuer.", 403);
        }
        
        return true;
    }
}
