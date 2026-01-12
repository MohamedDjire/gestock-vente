<?php
/**
 * API Vente - Gestion des ventes
 * Endpoint: /api-stock/api_vente.php
 */

// Activer la gestion des erreurs et définir les headers CORS AVANT TOUT
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');

// Répondre immédiatement aux requêtes OPTIONS (préflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =====================================================
// CONFIGURATION BASE DE DONNÉES
// =====================================================
$dbFile = __DIR__ . '/config/database.php';
if (!file_exists($dbFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier config/database.php introuvable sur le serveur',
        'error' => 'Le fichier doit être déployé dans: /api-stock/config/database.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $dbFile;

if (!function_exists('createDatabaseConnection')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fonction createDatabaseConnection() introuvable',
        'error' => 'Vérifiez que le fichier config/database.php contient cette fonction'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $bdd = createDatabaseConnection();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de connexion à la base de données',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================
// MIDDLEWARE AUTHENTIFICATION
// =====================================================
$middlewareFile = __DIR__ . '/functions/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    $middlewareFile = __DIR__ . '/middleware_auth.php';
}

if (!file_exists($middlewareFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier middleware_auth.php introuvable',
        'error' => 'Le fichier doit être déployé'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $middlewareFile;

if (!function_exists('authenticateAndAuthorize')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fonction authenticateAndAuthorize() introuvable',
        'error' => 'Vérifiez que le fichier middleware_auth.php contient cette fonction'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Authentifier l'utilisateur
try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = $currentUser['enterprise_id'];
    $userId = $currentUser['id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

/**
 * Créer une vente
 */
function createVente($bdd, $data, $enterpriseId, $userId, $currentUser = null) {
    // Validation
    if (empty($data['produits']) || !is_array($data['produits']) || count($data['produits']) === 0) {
        throw new Exception("Au moins un produit est requis pour la vente");
    }
    
    if (empty($data['id_point_vente'])) {
        throw new Exception("Le point de vente est requis");
    }
    
    $bdd->beginTransaction();
    
    try {
        $idPointVente = (int)$data['id_point_vente'];
        $idClient = isset($data['id_client']) ? (int)$data['id_client'] : null;
        $notes = $data['notes'] ?? null;
        $montantTotal = 0;
        $ventesCreees = [];
        
        // Vérifier que le point de vente appartient à l'entreprise
        $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE id_point_vente = :id AND id_entreprise = :id_entreprise");
        $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
        if (!$stmt->fetch()) {
            throw new Exception("Point de vente non trouvé", 404);
        }
        
        // Traiter chaque produit
        foreach ($data['produits'] as $produit) {
            $idProduit = (int)$produit['id_produit'];
            $quantite = (int)$produit['quantite'];
            $prixUnitaire = (float)$produit['prix_unitaire'];
            
            if ($quantite <= 0) {
                throw new Exception("La quantité doit être supérieure à 0");
            }
            
            // Vérifier que le produit existe et appartient à l'entreprise
            $stmt = $bdd->prepare("
                SELECT id_produit, nom, prix_vente, quantite_stock 
                FROM stock_produit 
                WHERE id_produit = :id AND id_entreprise = :id_entreprise AND actif = 1
            ");
            $stmt->execute(['id' => $idProduit, 'id_entreprise' => $enterpriseId]);
            $produitData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$produitData) {
                throw new Exception("Produit non trouvé ou inactif: ID {$idProduit}");
            }
            
            // Vérifier le stock disponible
            if ($produitData['quantite_stock'] < $quantite) {
                throw new Exception("Stock insuffisant pour le produit '{$produitData['nom']}'. Stock disponible: {$produitData['quantite_stock']}");
            }
            
            // Utiliser le prix fourni ou le prix de vente du produit
            if ($prixUnitaire <= 0) {
                $prixUnitaire = (float)$produitData['prix_vente'];
            }
            
            $montantTotalProduit = $prixUnitaire * $quantite;
            $montantTotal += $montantTotalProduit;
            
            // Créer la vente
            $stmt = $bdd->prepare("
                INSERT INTO stock_vente (
                    id_point_vente, id_produit, quantite, prix_unitaire, montant_total,
                    type_vente, statut, id_client, id_user, id_entreprise, notes
                ) VALUES (
                    :id_point_vente, :id_produit, :quantite, :prix_unitaire, :montant_total,
                    'vente', 'en_cours', :id_client, :id_user, :id_entreprise, :notes
                )
            ");
            
            $stmt->execute([
                'id_point_vente' => $idPointVente,
                'id_produit' => $idProduit,
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire,
                'montant_total' => $montantTotalProduit,
                'id_client' => $idClient,
                'id_user' => $userId,
                'id_entreprise' => $enterpriseId,
                'notes' => $notes
            ]);
            
            $idVente = $bdd->lastInsertId();
            
            // Déduire le stock
            $updateStmt = $bdd->prepare("
                UPDATE stock_produit 
                SET quantite_stock = quantite_stock - :quantite,
                    date_modification = NOW()
                WHERE id_produit = :id_produit AND id_entreprise = :id_entreprise
            ");
            $updateStmt->execute([
                'quantite' => $quantite,
                'id_produit' => $idProduit,
                'id_entreprise' => $enterpriseId
            ]);
            
            // Créer une sortie de stock
            $sortieStmt = $bdd->prepare("
                INSERT INTO stock_sortie (
                    id_produit, quantite, motif, id_user, id_entreprise, type_sortie
                ) VALUES (
                    :id_produit, :quantite, 'Vente', :id_user, :id_entreprise, 'vente'
                )
            ");
            $sortieStmt->execute([
                'id_produit' => $idProduit,
                'quantite' => $quantite,
                'id_user' => $userId,
                'id_entreprise' => $enterpriseId
            ]);
            
            // Récupérer les détails de la vente créée
            $getStmt = $bdd->prepare("
                SELECT v.*, p.nom AS produit_nom, p.code_produit
                FROM stock_vente v
                INNER JOIN stock_produit p ON v.id_produit = p.id_produit
                WHERE v.id_vente = :id
            ");
            $getStmt->execute(['id' => $idVente]);
            $ventesCreees[] = $getStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Appliquer la remise globale si fournie
        $remise = isset($data['remise']) ? (float)$data['remise'] : 0;
        $montantTotalFinal = $montantTotal - $remise;
        
        // Enregistrer dans le journal
        try {
            $userName = 'Utilisateur';
            if ($currentUser) {
                $userName = $currentUser['nom'] ?? $currentUser['prenom'] ?? $currentUser['email'] ?? 'Utilisateur';
            } else {
                // Récupérer le nom de l'utilisateur depuis la base
                $stmtUser = $bdd->prepare("SELECT nom, prenom, email FROM stock_utilisateur WHERE id_utilisateur = :id");
                $stmtUser->execute(['id' => $userId]);
                $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                if ($userData) {
                    $userName = $userData['nom'] ?? $userData['prenom'] ?? $userData['email'] ?? 'Utilisateur';
                }
            }
            $pointVenteName = '';
            $stmtPv = $bdd->prepare("SELECT nom_point_vente FROM stock_point_vente WHERE id_point_vente = :id");
            $stmtPv->execute(['id' => $idPointVente]);
            $pvData = $stmtPv->fetch(PDO::FETCH_ASSOC);
            if ($pvData) {
                $pointVenteName = $pvData['nom_point_vente'];
            }
            
            $details = sprintf(
                "Vente de %d produit(s) au point de vente '%s'. Total: %.2f FCFA%s",
                count($ventesCreees),
                $pointVenteName,
                $montantTotalFinal,
                $remise > 0 ? sprintf(" (Remise: %.2f FCFA)", $remise) : ""
            );
            
            // Vérifier si la table stock_journal existe
            $checkTable = $bdd->query("SHOW TABLES LIKE 'stock_journal'");
            if ($checkTable->rowCount() > 0) {
                $journalStmt = $bdd->prepare("
                    INSERT INTO stock_journal (date, user, action, details, id_entreprise)
                    VALUES (NOW(), :user, 'Vente', :details, :id_entreprise)
                ");
                $journalStmt->execute([
                    'user' => $userName,
                    'details' => $details,
                    'id_entreprise' => $enterpriseId
                ]);
            }
        } catch (Exception $e) {
            // Ne pas faire échouer la vente si l'enregistrement du journal échoue
            error_log("Erreur lors de l'enregistrement dans le journal: " . $e->getMessage());
        }
        
        $bdd->commit();
        
        return [
            'id_vente' => $ventesCreees[0]['id_vente'] ?? null,
            'ventes' => $ventesCreees,
            'montant_total' => $montantTotalFinal,
            'montant_sous_total' => $montantTotal,
            'remise' => $remise,
            'nombre_produits' => count($ventesCreees)
        ];
        
    } catch (Exception $e) {
        $bdd->rollBack();
        throw $e;
    }
}

/**
 * Récupérer les ventes
 */
function getVentes($bdd, $enterpriseId, $filters = []) {
    $where = ["v.id_entreprise = :id_entreprise"];
    $params = ['id_entreprise' => $enterpriseId];
    
    if (!empty($filters['date_debut'])) {
        $where[] = "DATE(v.date_vente) >= :date_debut";
        $params['date_debut'] = $filters['date_debut'];
    }
    
    if (!empty($filters['date_fin'])) {
        $where[] = "DATE(v.date_vente) <= :date_fin";
        $params['date_fin'] = $filters['date_fin'];
    }
    
    if (!empty($filters['id_point_vente'])) {
        $where[] = "v.id_point_vente = :id_point_vente";
        $params['id_point_vente'] = (int)$filters['id_point_vente'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    $stmt = $bdd->prepare("
        SELECT 
            v.*,
            p.nom AS produit_nom,
            p.code_produit,
            pv.nom_point_vente,
            c.nom AS client_nom,
            c.prenom AS client_prenom,
            u.nom AS user_nom,
            u.prenom AS user_prenom
        FROM stock_vente v
        INNER JOIN stock_produit p ON v.id_produit = p.id_produit
        INNER JOIN stock_point_vente pv ON v.id_point_vente = pv.id_point_vente
        INNER JOIN stock_utilisateur u ON v.id_user = u.id_utilisateur
        LEFT JOIN stock_client c ON v.id_client = c.id_client
        WHERE {$whereClause}
        ORDER BY v.date_vente DESC
        LIMIT 1000
    ");
    
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================
// GESTION DES REQUÊTES
// =====================================================

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            if ($action === 'all' || $action === '') {
                $filters = [
                    'date_debut' => $_GET['date_debut'] ?? null,
                    'date_fin' => $_GET['date_fin'] ?? null,
                    'id_point_vente' => $_GET['id_point_vente'] ?? null
                ];
                $resultat = getVentes($bdd, $enterpriseId, $filters);
            } else {
                throw new Exception("Action non valide");
            }
            break;
            
        case 'POST':
            if ($action === 'create' || $action === '') {
                $resultat = createVente($bdd, $data, $enterpriseId, $userId, $currentUser);
            } else {
                throw new Exception("Action non valide");
            }
            break;
            
        default:
            throw new Exception("Méthode HTTP non supportée");
    }
    
    echo json_encode([
        'success' => true,
        'data' => $resultat
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

