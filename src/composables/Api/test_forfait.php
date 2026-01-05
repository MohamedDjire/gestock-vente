<?php
/**
 * Script de test pour créer un forfait de test
 * À exécuter une seule fois pour tester le système
 */

require_once __DIR__ . '/config/database.php';

try {
    $bdd = createDatabaseConnection();
    
    // Récupérer la première entreprise (pour test)
    $stmt = $bdd->query("SELECT id_entreprise FROM stock_entreprise LIMIT 1");
    $entreprise = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entreprise) {
        die("Aucune entreprise trouvée. Créez d'abord une entreprise.\n");
    }
    
    $idEntreprise = $entreprise['id_entreprise'];
    echo "Entreprise trouvée: ID = $idEntreprise\n";
    
    // Récupérer le premier forfait disponible
    $stmt = $bdd->query("SELECT id_forfait, prix, duree_jours FROM stock_forfait WHERE actif = 1 LIMIT 1");
    $forfait = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$forfait) {
        // Créer les forfaits s'ils n'existent pas
        $bdd->exec("
            INSERT INTO stock_forfait (nom_forfait, prix, duree_jours, description, actif) VALUES
            ('Forfait Basique', 3000.00, 30, 'Forfait mensuel de base', 1),
            ('Forfait Premium', 5000.00, 30, 'Forfait mensuel premium', 1)
            ON DUPLICATE KEY UPDATE nom_forfait = VALUES(nom_forfait)
        ");
        $stmt = $bdd->query("SELECT id_forfait, prix, duree_jours FROM stock_forfait WHERE actif = 1 LIMIT 1");
        $forfait = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo "Forfait trouvé: ID = {$forfait['id_forfait']}, Prix = {$forfait['prix']}\n";
    
    // Vérifier si l'entreprise a déjà un abonnement actif
    $stmt = $bdd->prepare("
        SELECT id_abonnement FROM stock_abonnement 
        WHERE id_entreprise = :id_entreprise AND statut = 'actif'
    ");
    $stmt->execute(['id_entreprise' => $idEntreprise]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "L'entreprise a déjà un abonnement actif (ID: {$existing['id_abonnement']})\n";
        echo "Pour créer un nouvel abonnement, annulez d'abord l'ancien.\n";
        exit;
    }
    
    // Créer un abonnement de test (30 jours à partir d'aujourd'hui)
    $dateDebut = new DateTime();
    $dateFin = clone $dateDebut;
    $dateFin->modify('+' . $forfait['duree_jours'] . ' days');
    
    $stmt = $bdd->prepare("
        INSERT INTO stock_abonnement (
            id_entreprise, id_forfait, date_debut, date_fin, prix_paye, statut
        ) VALUES (
            :id_entreprise, :id_forfait, :date_debut, :date_fin, :prix_paye, 'actif'
        )
    ");
    
    $stmt->execute([
        'id_entreprise' => $idEntreprise,
        'id_forfait' => $forfait['id_forfait'],
        'date_debut' => $dateDebut->format('Y-m-d H:i:s'),
        'date_fin' => $dateFin->format('Y-m-d H:i:s'),
        'prix_paye' => $forfait['prix']
    ]);
    
    $idAbonnement = $bdd->lastInsertId();
    
    echo "\n✅ Abonnement créé avec succès!\n";
    echo "ID Abonnement: $idAbonnement\n";
    echo "Date début: {$dateDebut->format('Y-m-d H:i:s')}\n";
    echo "Date fin: {$dateFin->format('Y-m-d H:i:s')}\n";
    echo "Prix payé: {$forfait['prix']}\n";
    echo "\nLe forfait devrait maintenant s'afficher dans l'interface.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
