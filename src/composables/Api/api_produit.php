<?php
/**
 * API Stock Produit - Gestion des produits (stock_produit)
 * Endpoint: /api-stock/api_produit.php
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
    @http_response_code(500);
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
 * @param int|null $idPointVente Si fourni, ne retourne que les produits disponibles dans ce point de vente
 */
function getAllProducts($bdd, $enterpriseId, $idPointVente = null, $idEntrepots = null) {
    $sql = "
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
            p.image,
            COALESCE(p.entrepot, 'Magasin') AS entrepot,
            (p.prix_vente - p.prix_achat) AS marge_beneficiaire,
            ((p.prix_vente - p.prix_achat) * p.quantite_stock) AS valeur_stock,
            CASE 
                WHEN p.quantite_stock <= p.seuil_minimum THEN 'alerte'
                WHEN p.quantite_stock = 0 THEN 'rupture'
                ELSE 'normal'
            END AS statut_stock
        FROM stock_produit p
        WHERE p.id_entreprise = ?
    ";
    
    $params = [$enterpriseId];
    
    // Filtrer par entrepôts assignés si fourni (POUR LES AGENTS SEULEMENT)
    if ($idEntrepots !== null && is_array($idEntrepots) && count($idEntrepots) > 0) {
        error_log("🔍 [API Produit] Filtrage par entrepôts demandé. IDs: " . json_encode($idEntrepots));
        
        // Récupérer les noms des entrepôts assignés
        $idsAutorises = array_map('intval', $idEntrepots);
        $idsAutorises = array_filter($idsAutorises, function($id) { return $id > 0; });
        
        error_log("🔍 [API Produit] IDs autorisés après filtrage: " . json_encode($idsAutorises));
        
        if (count($idsAutorises) > 0) {
            $in = implode(',', array_fill(0, count($idsAutorises), '?'));
            $stmtEntrepots = $bdd->prepare("SELECT nom_entrepot FROM stock_entrepot WHERE id_entrepot IN ($in) AND id_entreprise = ?");
            $paramsEntrepots = array_values($idsAutorises);
            $paramsEntrepots[] = $enterpriseId;
            $stmtEntrepots->execute($paramsEntrepots);
            $nomsEntrepots = $stmtEntrepots->fetchAll(PDO::FETCH_COLUMN);
            
            error_log("🔍 [API Produit] Noms d'entrepôts trouvés: " . json_encode($nomsEntrepots));
            
            if (count($nomsEntrepots) > 0) {
                // Filtrer les produits par nom d'entrepôt (insensible à la casse)
                $placeholders = implode(',', array_fill(0, count($nomsEntrepots), '?'));
                $sql .= " AND LOWER(TRIM(COALESCE(p.entrepot, 'Magasin'))) IN ($placeholders)";
                foreach ($nomsEntrepots as $nom) {
                    $params[] = strtolower(trim($nom));
                }
                error_log("✅ [API Produit] Filtre appliqué avec " . count($nomsEntrepots) . " entrepôts");
            } else {
                // Si aucun entrepôt n'est trouvé, ne retourner aucun produit
                $sql .= " AND 1 = 0";
                error_log("⚠️ [API Produit] Aucun entrepôt trouvé, aucun produit retourné");
            }
        }
    } else {
        error_log("ℹ️ [API Produit] Pas de filtre par entrepôts (admin ou pas de permissions)");
    }
    
    // Filtrer par point de vente si fourni
    if ($idPointVente !== null) {
        // Vérifier si la table de liaison existe
        $checkTable = $bdd->query("SHOW TABLES LIKE 'stock_produit_point_vente'");
        if ($checkTable->rowCount() > 0) {
            // Utiliser la table de liaison
            $sql .= " AND EXISTS (
                SELECT 1 FROM stock_produit_point_vente ppv
                WHERE ppv.id_produit = p.id_produit
                AND ppv.id_point_vente = ?
                AND ppv.id_entreprise = ?
                AND ppv.actif = 1
            )";
            $params[] = (int)$idPointVente;
            $params[] = $enterpriseId;
        } else {
            // Fallback : utiliser l'entrepôt du point de vente
            $sql .= " AND EXISTS (
                SELECT 1 FROM stock_point_vente pv
                INNER JOIN stock_entrepot e ON e.id_entrepot = pv.id_entrepot
                WHERE pv.id_point_vente = ?
                AND pv.id_entreprise = ?
                AND (p.entrepot IS NULL OR LOWER(TRIM(p.entrepot)) = LOWER(TRIM(e.nom_entrepot)))
            )";
            $params[] = (int)$idPointVente;
            $params[] = $enterpriseId;
        }
    }
    
    $sql .= " ORDER BY p.date_creation DESC";
    
    error_log("🔍 [API Produit] Requête SQL finale: " . $sql);
    error_log("🔍 [API Produit] Paramètres: " . json_encode($params));
    error_log("🔍 [API Produit] Enterprise ID: " . $enterpriseId);
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("✅ [API Produit] Nombre de produits retournés: " . count($result));
    if (count($result) > 0) {
        $entrepotsDansResultats = array_unique(array_column($result, 'entrepot'));
        error_log("📦 [API Produit] Entrepôts dans les résultats: " . json_encode($entrepotsDansResultats));
        error_log("📦 [API Produit] Exemples de produits (3 premiers): " . json_encode(array_slice($result, 0, 3)));
    } else {
        error_log("⚠️ [API Produit] AUCUN PRODUIT RETOURNÉ!");
        // Vérifier combien de produits existent au total pour cette entreprise
        $stmtTotal = $bdd->prepare("SELECT COUNT(*) as total FROM stock_produit WHERE id_entreprise = ?");
        $stmtTotal->execute([$enterpriseId]);
        $total = $stmtTotal->fetch(PDO::FETCH_ASSOC);
        error_log("📊 [API Produit] Total produits dans l'entreprise: " . $total['total']);
        
        // Vérifier les entrepôts des produits existants
        $stmtEntrepots = $bdd->prepare("SELECT DISTINCT entrepot FROM stock_produit WHERE id_entreprise = ? AND actif = 1");
        $stmtEntrepots->execute([$enterpriseId]);
        $entrepotsProduits = $stmtEntrepots->fetchAll(PDO::FETCH_COLUMN);
        error_log("📦 [API Produit] Entrepôts dans les produits de l'entreprise: " . json_encode($entrepotsProduits));
    }
    
    return $result;
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
            quantite_stock, seuil_minimum, date_expiration, entrepot, actif, id_entreprise, image
        ) VALUES (
            :code_produit, :nom, :id_categorie, :prix_achat, :prix_vente,
            :quantite_stock, :seuil_minimum, :date_expiration, :entrepot, :actif, :id_entreprise, :image
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
        'id_entreprise' => $enterpriseId,
        'image' => $data['image'] ?? null
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
                     'quantite_stock', 'seuil_minimum', 'date_expiration', 'entrepot', 'actif', 'image'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            // Pour le champ entrepot, s'assurer qu'il a toujours une valeur
            if ($field === 'entrepot' && (empty($data[$field]) || trim($data[$field]) === '')) {
                $params[$field] = 'Magasin';
            } else {
                $params[$field] = $data[$field];
            }
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
                // Récupérer le paramètre id_point_vente si fourni
                $idPointVente = isset($_GET['id_point_vente']) ? (int)$_GET['id_point_vente'] : null;
                // Récupérer les IDs d'entrepôts si fournis (pour les agents)
                $idEntrepots = null;
                if (isset($_GET['id_entrepots']) && !empty($_GET['id_entrepots'])) {
                    $idEntrepots = is_array($_GET['id_entrepots']) ? $_GET['id_entrepots'] : explode(',', $_GET['id_entrepots']);
                    $idEntrepots = array_map('intval', $idEntrepots);
                    $idEntrepots = array_filter($idEntrepots, function($id) { return $id > 0; });
                    $idEntrepots = array_values($idEntrepots); // Réindexer
                }
                $resultat = getAllProducts($bdd, $enterpriseId, $idPointVente, $idEntrepots);
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
    
} catch (PDOException $e) {
    if (isset($bdd) && $bdd->inTransaction()) {
        $bdd->rollBack();
    }
    @http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($bdd) && $bdd->inTransaction()) {
        $bdd->rollBack();
    }
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() ?: 'Erreur serveur',
        'error' => $e->getMessage(),
        'code' => $code
    ], JSON_UNESCAPED_UNICODE);
}








