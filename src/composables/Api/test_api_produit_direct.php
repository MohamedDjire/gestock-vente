<?php
/**
 * Test direct de l'API produit pour voir les erreurs
 * Endpoint: /api-stock/test_api_produit_direct.php
 */

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$errors = [];
$steps = [];

// Étape 1: Vérifier database.php
$steps[] = 'Étape 1: Vérification database.php';
$dbFile = __DIR__ . '/config/database.php';
if (!file_exists($dbFile)) {
    $errors[] = 'Fichier config/database.php introuvable';
} else {
    $steps[] = '✓ database.php trouvé';
    try {
        require_once $dbFile;
        $steps[] = '✓ database.php chargé';
        
        if (!function_exists('createDatabaseConnection')) {
            $errors[] = 'Fonction createDatabaseConnection() introuvable';
        } else {
            $steps[] = '✓ createDatabaseConnection() existe';
            
            try {
                $bdd = createDatabaseConnection();
                $steps[] = '✓ Connexion DB réussie';
            } catch (Exception $e) {
                $errors[] = 'Erreur connexion DB: ' . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $errors[] = 'Erreur lors du chargement de database.php: ' . $e->getMessage();
    }
}

// Étape 2: Vérifier middleware_auth.php
$steps[] = 'Étape 2: Vérification middleware_auth.php';
$middlewareFile = __DIR__ . '/functions/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    $errors[] = 'Fichier functions/middleware_auth.php introuvable';
} else {
    $steps[] = '✓ middleware_auth.php trouvé';
    try {
        require_once $middlewareFile;
        $steps[] = '✓ middleware_auth.php chargé';
        
        if (!function_exists('authenticateAndAuthorize')) {
            $errors[] = 'Fonction authenticateAndAuthorize() introuvable';
        } else {
            $steps[] = '✓ authenticateAndAuthorize() existe';
        }
    } catch (Exception $e) {
        $errors[] = 'Erreur lors du chargement de middleware_auth.php: ' . $e->getMessage();
    }
}

// Étape 3: Tester l'authentification
if (isset($bdd) && function_exists('authenticateAndAuthorize')) {
    $steps[] = 'Étape 3: Test authentification';
    
    // Vérifier les headers reçus
    $authHeader = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                     (isset($headers['authorization']) ? $headers['authorization'] : '');
        if (isset($headers['X-Auth-Token'])) {
            $authHeader = 'Bearer ' . $headers['X-Auth-Token'];
        }
    }
    if (empty($authHeader)) {
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : 
                     (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '');
    }
    if (empty($authHeader) && isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $authHeader = 'Bearer ' . $_SERVER['HTTP_X_AUTH_TOKEN'];
    }
    
    $steps[] = 'Header Authorization reçu: ' . (!empty($authHeader) ? substr($authHeader, 0, 30) . '...' : 'AUCUN');
    
    try {
        $currentUser = authenticateAndAuthorize($bdd);
        $steps[] = '✓ Authentification réussie';
        $steps[] = 'Enterprise ID: ' . $currentUser['enterprise_id'];
    } catch (Exception $e) {
        $errors[] = 'Erreur authentification: ' . $e->getMessage();
        $errors[] = 'Header reçu: ' . (!empty($authHeader) ? 'OUI' : 'NON');
    }
}

// Étape 4: Tester la requête produits
if (isset($bdd) && isset($currentUser)) {
    $steps[] = 'Étape 4: Test requête produits';
    try {
        $enterpriseId = $currentUser['enterprise_id'];
        $stmt = $bdd->prepare("SELECT COUNT(*) as total FROM stock_produit WHERE id_entreprise = :id");
        $stmt->execute(['id' => $enterpriseId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $steps[] = '✓ Produits trouvés pour entreprise ' . $enterpriseId . ': ' . $count['total'];
    } catch (Exception $e) {
        $errors[] = 'Erreur requête produits: ' . $e->getMessage();
    }
}

echo json_encode([
    'success' => empty($errors),
    'steps' => $steps,
    'errors' => $errors
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
