<?php
/**
 * API Stock Produit - Gestion des produits (stock_produit)
 * Endpoint: /api-stock/api_produit.php
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
 * Récupérer tous les produits d'une entreprise
 */
function getAllProducts($bdd, $enterpriseId) {
    $stmt = $bdd->prepare("
        SELECT 
            p.id_produit,
            p.code_produit,
            p.nom,
            p.id_categorie,
            p.prix_achat,
            p.prix_vente,
            p.quantite_stock,
            p.seuil_minimum,
            p.date_expiration,
            p.date_creation,
            p.date_modification,
            p.actif,
            p.id_entreprise,
            COALESCE(p.entrepot, 'Magasin') AS entrepot,
            (p.prix_vente - p.prix_achat) AS marge_beneficiaire,
            ((p.prix_vente - p.prix_achat) * p.quantite_stock) AS valeur_stock,
            CASE 
                WHEN p.quantite_stock <= p.seuil_minimum THEN 'alerte'
                WHEN p.quantite_stock = 0 THEN 'rupture'
                ELSE 'normal'
            END AS statut_stock
        FROM stock_produit p
        WHERE p.id_entreprise = :enterprise_id
        ORDER BY p.date_creation DESC
    ");
    $stmt->execute(['enterprise_id' => $enterpriseId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupérer un produit par son ID
 */
function getProductById($bdd, $productId, $enterpriseId) {
    $stmt = $bdd->prepare("
        SELECT 
            p.*,
            (p.prix_vente - p.prix_achat) AS marge_beneficiaire,
            ((p.prix_vente - p.prix_achat) * p.quantite_stock) AS valeur_stock,
            CASE 
                WHEN p.quantite_stock <= p.seuil_minimum THEN 'alerte'
                WHEN p.quantite_stock = 0 THEN 'rupture'
                ELSE 'normal'
            END AS statut_stock
        FROM stock_produit p
        WHERE p.id_produit = :id AND p.id_entreprise = :enterprise_id
    ");
    $stmt->execute(['id' => $productId, 'enterprise_id' => $enterpriseId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new Exception("Produit non trouvé", 404);
    }
    return $product;
}

/**
 * Rechercher des produits
 */
function searchProducts($bdd, $query, $enterpriseId) {
    $searchTerm = '%' . $query . '%';
    $stmt = $bdd->prepare("
        SELECT 
            p.*,
            (p.prix_vente - p.prix_achat) AS marge_beneficiaire,
            CASE 
                WHEN p.quantite_stock <= p.seuil_minimum THEN 'alerte'
                WHEN p.quantite_stock = 0 THEN 'rupture'
                ELSE 'normal'
            END AS statut_stock
        FROM stock_produit p
        WHERE p.id_entreprise = :enterprise_id
        AND (p.nom LIKE :query OR p.code_produit LIKE :query)
        ORDER BY p.nom
    ");
    $stmt->execute([
        'enterprise_id' => $enterpriseId,
        'query' => $searchTerm
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Créer un nouveau produit
 */
function createProduct($bdd, $data, $enterpriseId) {
    // Validation des données
    if (empty($data['nom']) || empty($data['code_produit'])) {
        throw new Exception("Le nom et le code produit sont obligatoires");
    }
    
    if (!isset($data['prix_achat']) || !isset($data['prix_vente'])) {
        throw new Exception("Les prix d'achat et de vente sont obligatoires");
    }
    
    // Vérifier que le code produit est unique pour cette entreprise
    $checkStmt = $bdd->prepare("
        SELECT id_produit FROM stock_produit 
        WHERE code_produit = :code AND id_entreprise = :enterprise_id
    ");
    $checkStmt->execute([
        'code' => $data['code_produit'],
        'enterprise_id' => $enterpriseId
    ]);
    if ($checkStmt->fetch()) {
        throw new Exception("Le code produit existe déjà pour cette entreprise");
    }
    
    // Insérer le produit
    $stmt = $bdd->prepare("
        INSERT INTO stock_produit (
            code_produit, nom, id_categorie, prix_achat, prix_vente,
            quantite_stock, seuil_minimum, date_expiration, entrepot, actif, id_entreprise
        ) VALUES (
            :code_produit, :nom, :id_categorie, :prix_achat, :prix_vente,
            :quantite_stock, :seuil_minimum, :date_expiration, :entrepot, :actif, :id_entreprise
        )
    ");
    
    $stmt->execute([
        'code_produit' => $data['code_produit'],
        'nom' => $data['nom'],
        'id_categorie' => $data['id_categorie'] ?? null,
        'prix_achat' => $data['prix_achat'],
        'prix_vente' => $data['prix_vente'],
        'quantite_stock' => $data['quantite_stock'] ?? 0,
        'seuil_minimum' => $data['seuil_minimum'] ?? 0,
        'date_expiration' => !empty($data['date_expiration']) ? $data['date_expiration'] : null,
        'entrepot' => $data['entrepot'] ?? 'Magasin',
        'actif' => isset($data['actif']) ? (int)$data['actif'] : 1,
        'id_entreprise' => $enterpriseId
    ]);
    
    $productId = $bdd->lastInsertId();
    return getProductById($bdd, $productId, $enterpriseId);
}

/**
 * Mettre à jour un produit
 */
function updateProduct($bdd, $productId, $data, $enterpriseId) {
    // Vérifier que le produit existe et appartient à l'entreprise
    $product = getProductById($bdd, $productId, $enterpriseId);
    
    // Si le code produit change, vérifier qu'il est unique
    if (isset($data['code_produit']) && $data['code_produit'] !== $product['code_produit']) {
        $checkStmt = $bdd->prepare("
            SELECT id_produit FROM stock_produit 
            WHERE code_produit = :code AND id_entreprise = :enterprise_id AND id_produit != :id
        ");
        $checkStmt->execute([
            'code' => $data['code_produit'],
            'enterprise_id' => $enterpriseId,
            'id' => $productId
        ]);
        if ($checkStmt->fetch()) {
            throw new Exception("Le code produit existe déjà pour cette entreprise");
        }
    }
    
    // Construire la requête de mise à jour dynamiquement
    $fields = [];
    $params = ['id' => $productId, 'enterprise_id' => $enterpriseId];
    
    $allowedFields = ['code_produit', 'nom', 'id_categorie', 'prix_achat', 'prix_vente', 
                     'quantite_stock', 'seuil_minimum', 'date_expiration', 'actif'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[$field] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception("Aucune donnée à mettre à jour");
    }
    
    $sql = "UPDATE stock_produit SET " . implode(', ', $fields) . 
           " WHERE id_produit = :id AND id_entreprise = :enterprise_id";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    
    return getProductById($bdd, $productId, $enterpriseId);
}

/**
 * Supprimer un produit (soft delete en mettant actif à 0)
 */
function deleteProduct($bdd, $productId, $enterpriseId) {
    $product = getProductById($bdd, $productId, $enterpriseId);
    
    $stmt = $bdd->prepare("
        UPDATE stock_produit 
        SET actif = 0 
        WHERE id_produit = :id AND id_entreprise = :enterprise_id
    ");
    $stmt->execute(['id' => $productId, 'enterprise_id' => $enterpriseId]);
    
    return ['message' => 'Produit désactivé avec succès', 'product_id' => $productId];
}

/**
 * Supprimer définitivement un produit
 */
function hardDeleteProduct($bdd, $productId, $enterpriseId) {
    $product = getProductById($bdd, $productId, $enterpriseId);
    
    $stmt = $bdd->prepare("
        DELETE FROM stock_produit 
        WHERE id_produit = :id AND id_entreprise = :enterprise_id
    ");
    $stmt->execute(['id' => $productId, 'enterprise_id' => $enterpriseId]);
    
    return ['message' => 'Produit supprimé définitivement', 'product_id' => $productId];
}

// =====================================================
// TRAITEMENT DES REQUÊTES
// =====================================================

$data = json_decode(file_get_contents("php://input"), true);
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$query = isset($_GET['query']) ? $_GET['query'] : null;

try {
    $bdd->beginTransaction();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'all') {
                $resultat = getAllProducts($bdd, $enterpriseId);
            } elseif ($action === 'single' && $id !== null) {
                $resultat = getProductById($bdd, $id, $enterpriseId);
            } elseif ($action === 'search' && $query !== null) {
                $resultat = searchProducts($bdd, $query, $enterpriseId);
            } else {
                throw new Exception("Action non valide ou paramètres manquants");
            }
            break;
            
        case 'POST':
            if (empty($data)) {
                throw new Exception("Données manquantes");
            }
            $resultat = createProduct($bdd, $data, $enterpriseId);
            break;
            
        case 'PUT':
            if ($id === null || empty($data)) {
                throw new Exception("ID ou données manquantes");
            }
            $resultat = updateProduct($bdd, $id, $data, $enterpriseId);
            break;
            
        case 'DELETE':
            if ($id === null) {
                throw new Exception("ID manquant");
            }
            // Vérifier si c'est une suppression définitive
            $hardDelete = isset($_GET['hard']) && $_GET['hard'] === 'true';
            if ($hardDelete) {
                $resultat = hardDeleteProduct($bdd, $id, $enterpriseId);
            } else {
                $resultat = deleteProduct($bdd, $id, $enterpriseId);
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
    $bdd->rollBack();
    @http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


