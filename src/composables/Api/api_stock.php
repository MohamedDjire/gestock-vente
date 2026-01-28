<?php
/**
 * API Stock - Gestion des entrées et sorties de stock
 * Endpoint: /api-stock/api_stock.php
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
// Sur le serveur: config/database.php
// En local: database.php (à la racine)
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
// Sur le serveur: functions/middleware_auth.php
// En local: middleware_auth.php (à la racine)
$middlewareFile = __DIR__ . '/functions/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier functions/middleware_auth.php introuvable sur le serveur',
        'error' => 'Le fichier doit être déployé dans: /api-stock/functions/middleware_auth.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $middlewareFile;

if (!function_exists('authenticateAndAuthorize')) {
    http_response_code(500);
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
    $userId = $currentUser['user_id'];
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
 * Récupérer toutes les entrées de stock
 */
function getAllEntrees($bdd, $enterpriseId, $productId = null) {
    $sql = "
        SELECT 
            e.*,
            p.nom AS produit_nom,
            p.code_produit,
            u.prenom AS user_prenom,
            u.nom AS user_nom
        FROM stock_entree e
        INNER JOIN stock_produit p ON e.id_produit = p.id_produit
        INNER JOIN stock_utilisateur u ON e.id_user = u.id_utilisateur
        WHERE e.id_entreprise = :enterprise_id
    ";
    
    $params = ['enterprise_id' => $enterpriseId];
    
    if ($productId !== null) {
        $sql .= " AND e.id_produit = :product_id";
        $params['product_id'] = $productId;
    }
    
    $sql .= " ORDER BY e.date_entree DESC";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupérer toutes les sorties de stock
 */
function getAllSorties($bdd, $enterpriseId, $productId = null) {
    $sql = "
        SELECT 
            s.*,
            p.nom AS produit_nom,
            p.code_produit,
            u.prenom AS user_prenom,
            u.nom AS user_nom
        FROM stock_sortie s
        INNER JOIN stock_produit p ON s.id_produit = p.id_produit
        INNER JOIN stock_utilisateur u ON s.id_user = u.id_utilisateur
        WHERE s.id_entreprise = :enterprise_id
    ";
    
    $params = ['enterprise_id' => $enterpriseId];
    
    if ($productId !== null) {
        $sql .= " AND s.id_produit = :product_id";
        $params['product_id'] = $productId;
    }
    
    $sql .= " ORDER BY s.date_sortie DESC";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupérer l'historique complet d'un produit
 */
function getProductHistory($bdd, $productId, $enterpriseId) {
    // Vérifier que le produit appartient à l'entreprise
    $checkStmt = $bdd->prepare("SELECT id_produit FROM stock_produit WHERE id_produit = :id AND id_entreprise = :enterprise_id");
    $checkStmt->execute(['id' => $productId, 'enterprise_id' => $enterpriseId]);
    if (!$checkStmt->fetch()) {
        throw new Exception("Produit non trouvé", 404);
    }
    
    $entrees = getAllEntrees($bdd, $enterpriseId, $productId);
    $sorties = getAllSorties($bdd, $enterpriseId, $productId);
    
    // Combiner et trier par date
    $history = [];
    foreach ($entrees as $entree) {
        $history[] = array_merge($entree, ['type' => 'entree']);
    }
    foreach ($sorties as $sortie) {
        $history[] = array_merge($sortie, ['type' => 'sortie']);
    }
    
    usort($history, function($a, $b) {
        $dateA = $a['type'] === 'entree' ? $a['date_entree'] : $a['date_sortie'];
        $dateB = $b['type'] === 'entree' ? $b['date_entree'] : $b['date_sortie'];
        return strtotime($dateB) - strtotime($dateA);
    });
    
    return $history;
}

/**
 * Créer une entrée de stock
 */
function createEntree($bdd, $data, $enterpriseId, $userId) {
    // Validation
    if (empty($data['id_produit']) || !isset($data['quantite']) || !isset($data['prix_unitaire'])) {
        throw new Exception("Les champs id_produit, quantite et prix_unitaire sont obligatoires");
    }
    
    // Vérifier que le produit appartient à l'entreprise
    $checkStmt = $bdd->prepare("SELECT id_produit FROM stock_produit WHERE id_produit = :id AND id_entreprise = :enterprise_id");
    $checkStmt->execute(['id' => $data['id_produit'], 'enterprise_id' => $enterpriseId]);
    if (!$checkStmt->fetch()) {
        throw new Exception("Produit non trouvé", 404);
    }
    
    $stmt = $bdd->prepare("
        INSERT INTO stock_entree (
            id_produit, quantite, prix_unitaire, id_fournisseur, 
            id_user, id_entreprise, numero_bon, notes
        ) VALUES (
            :id_produit, :quantite, :prix_unitaire, :id_fournisseur,
            :id_user, :id_entreprise, :numero_bon, :notes
        )
    ");
    
    $stmt->execute([
        'id_produit' => $data['id_produit'],
        'quantite' => $data['quantite'],
        'prix_unitaire' => $data['prix_unitaire'],
        'id_fournisseur' => $data['id_fournisseur'] ?? null,
        'id_user' => $userId,
        'id_entreprise' => $enterpriseId,
        'numero_bon' => $data['numero_bon'] ?? null,
        'notes' => $data['notes'] ?? null
    ]);
    
    $entreeId = $bdd->lastInsertId();
    
    // Récupérer l'entrée créée
    $getStmt = $bdd->prepare("
        SELECT e.*, p.nom AS produit_nom, p.code_produit,
               u.prenom AS user_prenom, u.nom AS user_nom
        FROM stock_entree e
        INNER JOIN stock_produit p ON e.id_produit = p.id_produit
        INNER JOIN stock_utilisateur u ON e.id_user = u.id_utilisateur
        WHERE e.id_entree = :id
    ");
    $getStmt->execute(['id' => $entreeId]);
    return $getStmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Créer une sortie de stock
 */
function createSortie($bdd, $data, $enterpriseId, $userId) {
    // Validation
    if (empty($data['id_produit']) || !isset($data['quantite']) || empty($data['type_sortie'])) {
        throw new Exception("Les champs id_produit, quantite et type_sortie sont obligatoires");
    }
    
    // Vérifier que le produit appartient à l'entreprise
    $checkStmt = $bdd->prepare("SELECT id_produit, quantite_stock FROM stock_produit WHERE id_produit = :id AND id_entreprise = :enterprise_id");
    $checkStmt->execute(['id' => $data['id_produit'], 'enterprise_id' => $enterpriseId]);
    $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("Produit non trouvé", 404);
    }
    
    // Vérifier que le stock est suffisant (sauf pour les pertes)
    if ($data['type_sortie'] !== 'perte' && $product['quantite_stock'] < $data['quantite']) {
        throw new Exception("Stock insuffisant. Stock disponible: " . $product['quantite_stock']);
    }
    
    // Construire la requête selon si c'est un transfert avec entrepot_destination
    $hasEntrepot = isset($data['entrepot_destination']) && !empty($data['entrepot_destination']);
    
    if ($hasEntrepot) {
        $stmt = $bdd->prepare("
            INSERT INTO stock_sortie (
                id_produit, quantite, type_sortie, motif,
                id_user, id_entreprise, prix_unitaire, entrepot_destination
            ) VALUES (
                :id_produit, :quantite, :type_sortie, :motif,
                :id_user, :id_entreprise, :prix_unitaire, :entrepot_destination
            )
        ");
        $stmt->execute([
            'id_produit' => $data['id_produit'],
            'quantite' => $data['quantite'],
            'type_sortie' => $data['type_sortie'],
            'motif' => $data['motif'] ?? null,
            'id_user' => $userId,
            'id_entreprise' => $enterpriseId,
            'prix_unitaire' => $data['prix_unitaire'] ?? null,
            'entrepot_destination' => $data['entrepot_destination']
        ]);
    } else {
        $stmt = $bdd->prepare("
            INSERT INTO stock_sortie (
                id_produit, quantite, type_sortie, motif,
                id_user, id_entreprise, prix_unitaire
            ) VALUES (
                :id_produit, :quantite, :type_sortie, :motif,
                :id_user, :id_entreprise, :prix_unitaire
            )
        ");
        $stmt->execute([
            'id_produit' => $data['id_produit'],
            'quantite' => $data['quantite'],
            'type_sortie' => $data['type_sortie'],
            'motif' => $data['motif'] ?? null,
            'id_user' => $userId,
            'id_entreprise' => $enterpriseId,
            'prix_unitaire' => $data['prix_unitaire'] ?? null
        ]);
    }
    
    $sortieId = $bdd->lastInsertId();
    
    // Transfert vers point de vente : mettre à jour stock_produit_point_vente
    if (($data['type_sortie'] ?? '') === 'transfert' && !empty($data['point_vente_destination'])) {
        $idPv = (int)$data['point_vente_destination'];
        error_log("📦 [api_stock] Transfert vers point de vente: id_pv=$idPv, id_produit={$data['id_produit']}, quantite={$data['quantite']}, entreprise=$enterpriseId");
        
        if ($idPv > 0) {
            $chk = $bdd->prepare("SELECT id_point_vente, nom_point_vente FROM stock_point_vente WHERE id_point_vente = ? AND id_entreprise = ?");
            $chk->execute([$idPv, $enterpriseId]);
            $pvExists = $chk->fetch(PDO::FETCH_ASSOC);
            
            if ($pvExists) {
                error_log("✅ [api_stock] Point de vente $idPv trouvé: " . ($pvExists['nom_point_vente'] ?? ''));
                $checkTable = $bdd->query("SHOW TABLES LIKE 'stock_produit_point_vente'");
                if ($checkTable && $checkTable->rowCount() > 0) {
                    error_log("✅ [api_stock] Table stock_produit_point_vente existe");
                    try {
                        $ins = $bdd->prepare("INSERT INTO stock_produit_point_vente (id_produit, id_point_vente, id_entreprise, quantite_disponible, actif) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE quantite_disponible = quantite_disponible + ?");
                        $ins->execute([$data['id_produit'], $idPv, $enterpriseId, (int)$data['quantite'], (int)$data['quantite']]);
                        $rowsAffected = $ins->rowCount();
                        error_log("✅ [api_stock] INSERT/UPDATE stock_produit_point_vente: rows_affected=$rowsAffected");
                        
                        // Vérifier que l'insertion a bien fonctionné
                        $verify = $bdd->prepare("SELECT quantite_disponible FROM stock_produit_point_vente WHERE id_produit = ? AND id_point_vente = ? AND id_entreprise = ?");
                        $verify->execute([$data['id_produit'], $idPv, $enterpriseId]);
                        $verifData = $verify->fetch(PDO::FETCH_ASSOC);
                        if ($verifData) {
                            error_log("✅ [api_stock] Vérification: quantite_disponible=" . ($verifData['quantite_disponible'] ?? 0));
                        } else {
                            error_log("❌ [api_stock] ERREUR: Ligne non trouvée après INSERT/UPDATE!");
                        }
                    } catch (PDOException $e) {
                        error_log("❌ [api_stock] Erreur PDO lors de l'insertion: " . $e->getMessage());
                        throw new Exception("Erreur lors de l'ajout au point de vente: " . $e->getMessage());
                    }
                } else {
                    error_log("⚠️ [api_stock] Table stock_produit_point_vente n'existe pas - création nécessaire");
                    throw new Exception("La table stock_produit_point_vente n'existe pas. Exécutez create_table_produit_point_vente.sql");
                }
            } else {
                error_log("❌ [api_stock] Point de vente $idPv non trouvé pour entreprise $enterpriseId");
                throw new Exception("Point de vente non trouvé ou non autorisé");
            }
        } else {
            error_log("❌ [api_stock] ID point de vente invalide: $idPv");
            throw new Exception("ID point de vente invalide");
        }
    }
    
    // Récupérer la sortie créée
    $getStmt = $bdd->prepare("
        SELECT s.*, p.nom AS produit_nom, p.code_produit,
               u.prenom AS user_prenom, u.nom AS user_nom
        FROM stock_sortie s
        INNER JOIN stock_produit p ON s.id_produit = p.id_produit
        INNER JOIN stock_utilisateur u ON s.id_user = u.id_utilisateur
        WHERE s.id_sortie = :id
    ");
    $getStmt->execute(['id' => $sortieId]);
    return $getStmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Récupérer les statistiques de stock
 */
function getStockStats($bdd, $enterpriseId, $productId = null) {
    $params = ['enterprise_id' => $enterpriseId];
    $productFilter = '';
    
    if ($productId !== null) {
        $productFilter = ' AND e.id_produit = :product_id';
        $params['product_id'] = $productId;
    }
    
    // Total entrées
    $entreesStmt = $bdd->prepare("
        SELECT 
            COUNT(*) AS total_entrees,
            SUM(quantite) AS total_quantite_entree,
            SUM(quantite * prix_unitaire) AS total_valeur_entree
        FROM stock_entree e
        WHERE e.id_entreprise = :enterprise_id $productFilter
    ");
    $entreesStmt->execute($params);
    $entrees = $entreesStmt->fetch(PDO::FETCH_ASSOC);
    
    // Total sorties
    $sortiesStmt = $bdd->prepare("
        SELECT 
            COUNT(*) AS total_sorties,
            SUM(quantite) AS total_quantite_sortie,
            SUM(quantite * COALESCE(prix_unitaire, 0)) AS total_valeur_sortie
        FROM stock_sortie s
        WHERE s.id_entreprise = :enterprise_id $productFilter
    ");
    $sortiesStmt->execute($params);
    $sorties = $sortiesStmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'entrees' => $entrees,
        'sorties' => $sorties,
        'difference' => [
            'quantite' => ($entrees['total_quantite_entree'] ?? 0) - ($sorties['total_quantite_sortie'] ?? 0),
            'valeur' => ($entrees['total_valeur_entree'] ?? 0) - ($sorties['total_valeur_sortie'] ?? 0)
        ]
    ];
}

// =====================================================
// TRAITEMENT DES REQUÊTES
// =====================================================

$data = json_decode(file_get_contents("php://input"), true);
$action = isset($_GET['action']) ? $_GET['action'] : null;
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null; // 'entree' ou 'sortie'

try {
    $bdd->beginTransaction();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'entrees') {
                $resultat = getAllEntrees($bdd, $enterpriseId, $productId);
            } elseif ($action === 'sorties') {
                $resultat = getAllSorties($bdd, $enterpriseId, $productId);
            } elseif ($action === 'history' && $productId !== null) {
                $resultat = getProductHistory($bdd, $productId, $enterpriseId);
            } elseif ($action === 'stats') {
                $resultat = getStockStats($bdd, $enterpriseId, $productId);
            } else {
                throw new Exception("Action non valide ou paramètres manquants");
            }
            break;
            
        case 'POST':
            if (empty($data)) {
                throw new Exception("Données manquantes");
            }
            
            if ($type === 'entree') {
                $resultat = createEntree($bdd, $data, $enterpriseId, $userId);
            } elseif ($type === 'sortie') {
                $resultat = createSortie($bdd, $data, $enterpriseId, $userId);
            } else {
                throw new Exception("Type manquant. Utilisez ?type=entree ou ?type=sortie");
            }
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
    http_response_code($e->getCode() ?: 400);
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur PHP: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}








