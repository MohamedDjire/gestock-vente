<?php
/**
 * API Alerte - Gestion des alertes de stock
 * Endpoint: /api-stock/api_alerte.php
 */

// Activer la gestion des erreurs et définir les headers CORS AVANT TOUT
@header('Content-Type: application/json');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
@header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Répondre immédiatement aux requêtes OPTIONS (préflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    @http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =====================================================
// CONFIGURATION BASE DE DONNÉES
// =====================================================
// Sur le serveur: config/database.php
// En local: database.php (à la racine)
$dbFile = __DIR__ . '/config/database.php';
if (!file_exists($dbFile)) {
    @http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier config/database.php introuvable sur le serveur',
        'error' => 'Le fichier doit être déployé dans: /api-stock/config/database.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $dbFile;

if (!function_exists('createDatabaseConnection')) {
    @http_response_code(500);
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
// Sur le serveur: functions/middleware_auth.php
// En local: middleware_auth.php (à la racine)
$middlewareFile = __DIR__ . '/functions/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    @http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier functions/middleware_auth.php introuvable sur le serveur',
        'error' => 'Le fichier doit être déployé dans: /api-stock/functions/middleware_auth.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $middlewareFile;

if (!function_exists('authenticateAndAuthorize')) {
    @http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fonction authenticateAndAuthorize() introuvable',
        'error' => 'Vérifiez que le fichier functions/middleware_auth.php contient cette fonction'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Authentifier l'utilisateur
try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = $currentUser['enterprise_id'];
} catch (Exception $e) {
    @http_response_code(401);
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
 * Récupérer toutes les alertes
 */
function getAllAlertes($bdd, $enterpriseId, $productId = null, $nonVuesSeulement = false) {
    $sql = "
        SELECT 
            a.*,
            p.nom AS produit_nom,
            p.code_produit,
            p.quantite_stock,
            p.seuil_minimum
        FROM stock_alerte a
        INNER JOIN stock_produit p ON a.id_produit = p.id_produit
        WHERE a.id_entreprise = :enterprise_id
    ";
    
    $params = ['enterprise_id' => $enterpriseId];
    
    if ($productId !== null) {
        $sql .= " AND a.id_produit = :product_id";
        $params['product_id'] = $productId;
    }
    
    if ($nonVuesSeulement) {
        $sql .= " AND a.vue = 0";
    }
    
    $sql .= " ORDER BY a.date_alerte DESC";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Créer une alerte manuelle
 */
function createAlerte($bdd, $data, $enterpriseId) {
    // Validation
    if (empty($data['id_produit']) || empty($data['type_alerte']) || empty($data['message'])) {
        throw new Exception("Les champs id_produit, type_alerte et message sont obligatoires");
    }
    
    // Vérifier que le produit appartient à l'entreprise
    $checkStmt = $bdd->prepare("SELECT id_produit FROM stock_produit WHERE id_produit = :id AND id_entreprise = :enterprise_id");
    $checkStmt->execute(['id' => $data['id_produit'], 'enterprise_id' => $enterpriseId]);
    if (!$checkStmt->fetch()) {
        throw new Exception("Produit non trouvé", 404);
    }
    
    $stmt = $bdd->prepare("
        INSERT INTO stock_alerte (id_produit, id_entreprise, type_alerte, message)
        VALUES (:id_produit, :id_entreprise, :type_alerte, :message)
    ");
    
    $stmt->execute([
        'id_produit' => $data['id_produit'],
        'id_entreprise' => $enterpriseId,
        'type_alerte' => $data['type_alerte'],
        'message' => $data['message']
    ]);
    
    $alerteId = $bdd->lastInsertId();
    
    // Récupérer l'alerte créée
    $getStmt = $bdd->prepare("
        SELECT a.*, p.nom AS produit_nom, p.code_produit
        FROM stock_alerte a
        INNER JOIN stock_produit p ON a.id_produit = p.id_produit
        WHERE a.id_alerte = :id
    ");
    $getStmt->execute(['id' => $alerteId]);
    return $getStmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Marquer une alerte comme vue
 */
function markAlerteAsVue($bdd, $alerteId, $enterpriseId) {
    $stmt = $bdd->prepare("
        UPDATE stock_alerte 
        SET vue = 1, date_vue = NOW()
        WHERE id_alerte = :id AND id_entreprise = :enterprise_id
    ");
    $stmt->execute(['id' => $alerteId, 'enterprise_id' => $enterpriseId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Alerte non trouvée", 404);
    }
    
    return ['message' => 'Alerte marquée comme vue'];
}

/**
 * Marquer toutes les alertes comme vues
 */
function markAllAlertesAsVue($bdd, $enterpriseId) {
    $stmt = $bdd->prepare("
        UPDATE stock_alerte 
        SET vue = 1, date_vue = NOW()
        WHERE id_entreprise = :enterprise_id AND vue = 0
    ");
    $stmt->execute(['enterprise_id' => $enterpriseId]);
    
    return ['message' => $stmt->rowCount() . ' alerte(s) marquée(s) comme vue(s)'];
}

/**
 * Supprimer une alerte
 */
function deleteAlerte($bdd, $alerteId, $enterpriseId) {
    $stmt = $bdd->prepare("
        DELETE FROM stock_alerte 
        WHERE id_alerte = :id AND id_entreprise = :enterprise_id
    ");
    $stmt->execute(['id' => $alerteId, 'enterprise_id' => $enterpriseId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Alerte non trouvée", 404);
    }
    
    return ['message' => 'Alerte supprimée'];
}

/**
 * Générer automatiquement les alertes pour tous les produits
 */
function generateAlertes($bdd, $enterpriseId) {
    // Alertes de rupture
    $ruptureStmt = $bdd->prepare("
        SELECT id_produit, nom, quantite_stock
        FROM stock_produit
        WHERE id_entreprise = :enterprise_id 
        AND quantite_stock = 0
        AND actif = 1
    ");
    $ruptureStmt->execute(['enterprise_id' => $enterpriseId]);
    $ruptures = $ruptureStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Alertes de stock faible
    $faibleStmt = $bdd->prepare("
        SELECT id_produit, nom, quantite_stock, seuil_minimum
        FROM stock_produit
        WHERE id_entreprise = :enterprise_id 
        AND quantite_stock > 0
        AND quantite_stock <= seuil_minimum
        AND actif = 1
    ");
    $faibleStmt->execute(['enterprise_id' => $enterpriseId]);
    $faibles = $faibleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Alertes d'expiration (dans les 7 jours)
    $expirationStmt = $bdd->prepare("
        SELECT id_produit, nom, date_expiration
        FROM stock_produit
        WHERE id_entreprise = :enterprise_id 
        AND date_expiration IS NOT NULL
        AND date_expiration <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND date_expiration > CURDATE()
        AND actif = 1
    ");
    $expirationStmt->execute(['enterprise_id' => $enterpriseId]);
    $expirations = $expirationStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $created = 0;
    
    // Créer les alertes de rupture (si elles n'existent pas déjà)
    foreach ($ruptures as $rupture) {
        $checkStmt = $bdd->prepare("
            SELECT id_alerte FROM stock_alerte 
            WHERE id_produit = :id AND type_alerte = 'rupture' AND vue = 0
        ");
        $checkStmt->execute(['id' => $rupture['id_produit']]);
        if (!$checkStmt->fetch()) {
            $stmt = $bdd->prepare("
                INSERT INTO stock_alerte (id_produit, id_entreprise, type_alerte, message)
                VALUES (:id_produit, :id_entreprise, 'rupture', :message)
            ");
            $stmt->execute([
                'id_produit' => $rupture['id_produit'],
                'id_entreprise' => $enterpriseId,
                'message' => "Rupture de stock pour le produit: " . $rupture['nom']
            ]);
            $created++;
        }
    }
    
    // Créer les alertes de stock faible
    foreach ($faibles as $faible) {
        $checkStmt = $bdd->prepare("
            SELECT id_alerte FROM stock_alerte 
            WHERE id_produit = :id AND type_alerte = 'stock_faible' AND vue = 0
        ");
        $checkStmt->execute(['id' => $faible['id_produit']]);
        if (!$checkStmt->fetch()) {
            $stmt = $bdd->prepare("
                INSERT INTO stock_alerte (id_produit, id_entreprise, type_alerte, message)
                VALUES (:id_produit, :id_entreprise, 'stock_faible', :message)
            ");
            $stmt->execute([
                'id_produit' => $faible['id_produit'],
                'id_entreprise' => $enterpriseId,
                'message' => "Stock faible pour le produit: " . $faible['nom'] . " (" . $faible['quantite_stock'] . " unités restantes)"
            ]);
            $created++;
        }
    }
    
    // Créer les alertes d'expiration
    foreach ($expirations as $expiration) {
        $checkStmt = $bdd->prepare("
            SELECT id_alerte FROM stock_alerte 
            WHERE id_produit = :id AND type_alerte = 'expiration' AND vue = 0
        ");
        $checkStmt->execute(['id' => $expiration['id_produit']]);
        if (!$checkStmt->fetch()) {
            $stmt = $bdd->prepare("
                INSERT INTO stock_alerte (id_produit, id_entreprise, type_alerte, message)
                VALUES (:id_produit, :id_entreprise, 'expiration', :message)
            ");
            $stmt->execute([
                'id_produit' => $expiration['id_produit'],
                'id_entreprise' => $enterpriseId,
                'message' => "Produit " . $expiration['nom'] . " expire le " . $expiration['date_expiration']
            ]);
            $created++;
        }
    }
    
    return ['message' => "$created alerte(s) générée(s)", 'created' => $created];
}

// =====================================================
// TRAITEMENT DES REQUÊTES
// =====================================================

$data = json_decode(file_get_contents("php://input"), true);
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
$nonVuesSeulement = isset($_GET['non_vues']) && $_GET['non_vues'] === 'true';

try {
    $bdd->beginTransaction();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'all') {
                $resultat = getAllAlertes($bdd, $enterpriseId, $productId, $nonVuesSeulement);
            } elseif ($action === 'generate') {
                $resultat = generateAlertes($bdd, $enterpriseId);
            } else {
                throw new Exception("Action non valide");
            }
            break;
            
        case 'POST':
            if (empty($data)) {
                throw new Exception("Données manquantes");
            }
            $resultat = createAlerte($bdd, $data, $enterpriseId);
            break;
            
        case 'PUT':
            if ($id === null) {
                throw new Exception("ID manquant");
            }
            if ($action === 'vue') {
                $resultat = markAlerteAsVue($bdd, $id, $enterpriseId);
            } elseif ($action === 'vue_all') {
                $resultat = markAllAlertesAsVue($bdd, $enterpriseId);
            } else {
                throw new Exception("Action non valide");
            }
            break;
            
        case 'DELETE':
            if ($id === null) {
                throw new Exception("ID manquant");
            }
            $resultat = deleteAlerte($bdd, $id, $enterpriseId);
            break;
            
        default:
            throw new Exception("Méthode HTTP non supportée");
    }
    
    $bdd->commit();
    
    echo json_encode([
        'success' => true,
        'data' => $resultat
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if (isset($bdd) && $bdd->inTransaction()) {
        $bdd->rollBack();
    }
    @http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    if (isset($bdd) && $bdd->inTransaction()) {
        $bdd->rollBack();
    }
    @http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur PHP: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}


