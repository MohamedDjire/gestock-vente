<?php
/**
 * Script de test pour diagnostiquer les problèmes de chargement des produits
 * Endpoint: /api-stock/test_api_produit.php
 */

// Headers CORS
@header('Content-Type: application/json');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
@header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    @http_response_code(200);
    exit;
}

$debug = [];

// 1. Vérifier la connexion à la base de données
try {
    $dbFile = __DIR__ . '/config/database.php';
    if (!file_exists($dbFile)) {
        $debug['database'] = ['error' => 'Fichier config/database.php introuvable'];
    } else {
        require_once $dbFile;
        if (!function_exists('createDatabaseConnection')) {
            $debug['database'] = ['error' => 'Fonction createDatabaseConnection() introuvable'];
        } else {
            $bdd = createDatabaseConnection();
            $debug['database'] = ['status' => 'Connexion réussie'];
            
            // Compter les produits dans la base
            $stmt = $bdd->query("SELECT COUNT(*) as total FROM stock_produit");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $debug['products_count'] = $count['total'];
            
            // Lister les produits avec leur id_entreprise
            $stmt = $bdd->query("SELECT id_produit, code_produit, nom, id_entreprise FROM stock_produit LIMIT 10");
            $debug['products_sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    $debug['database'] = ['error' => $e->getMessage()];
}

// 2. Vérifier l'authentification
try {
    $middlewareFile = __DIR__ . '/functions/middleware_auth.php';
    if (!file_exists($middlewareFile)) {
        $debug['auth'] = ['error' => 'Fichier middleware_auth.php introuvable'];
    } else {
        require_once $middlewareFile;
        
        // Essayer d'authentifier
        try {
            $currentUser = authenticateAndAuthorize($bdd);
            $debug['auth'] = [
                'status' => 'Authentification réussie',
                'user_id' => $currentUser['id'],
                'enterprise_id' => $currentUser['enterprise_id'],
                'username' => $currentUser['username']
            ];
            
            // Vérifier les produits pour cette entreprise
            $enterpriseId = $currentUser['enterprise_id'];
            $stmt = $bdd->prepare("SELECT COUNT(*) as total FROM stock_produit WHERE id_entreprise = :id");
            $stmt->execute(['id' => $enterpriseId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $debug['products_for_enterprise'] = [
                'enterprise_id' => $enterpriseId,
                'count' => $count['total']
            ];
            
            // Lister les produits pour cette entreprise
            $stmt = $bdd->prepare("SELECT id_produit, code_produit, nom FROM stock_produit WHERE id_entreprise = :id LIMIT 10");
            $stmt->execute(['id' => $enterpriseId]);
            $debug['products_for_enterprise_list'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $debug['auth'] = [
                'status' => 'Échec authentification',
                'error' => $e->getMessage()
            ];
            
            // Vérifier les headers reçus
            $authHeader = '';
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                             (isset($headers['authorization']) ? $headers['authorization'] : '');
            }
            if (empty($authHeader)) {
                $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : 
                            (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '');
            }
            if (empty($authHeader) && isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
                $authHeader = 'Bearer ' . $_SERVER['HTTP_X_AUTH_TOKEN'];
            }
            
            $debug['auth']['header_received'] = !empty($authHeader) ? substr($authHeader, 0, 50) . '...' : 'AUCUN HEADER';
        }
    }
} catch (Exception $e) {
    $debug['auth'] = ['error' => $e->getMessage()];
}

// 3. Vérifier les entreprises
try {
    if (isset($bdd)) {
        $stmt = $bdd->query("SELECT id_entreprise, nom_entreprise, sigle FROM stock_entreprise LIMIT 10");
        $debug['enterprises'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $debug['enterprises'] = ['error' => $e->getMessage()];
}

echo json_encode([
    'success' => true,
    'message' => 'Diagnostic API Produit',
    'debug' => $debug
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
