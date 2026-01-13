<?php
// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

// src/composables/api/api_journal.php
// API pour journaliser et récupérer les mouvements de l'application

require_once __DIR__ . '/config/database.php';
$bdd = createDatabaseConnection();

// =====================================================
// MIDDLEWARE AUTHENTIFICATION
// =====================================================
$middlewareFile = __DIR__ . '/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    $middlewareFile = __DIR__ . '/functions/middleware_auth.php';
}

if (!file_exists($middlewareFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier middleware_auth.php introuvable'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $middlewareFile;

if (!function_exists('authenticateAndAuthorize')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fonction authenticateAndAuthorize() introuvable'
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Vérifier si la colonne id_entreprise existe
    $checkColumn = $bdd->query("SHOW COLUMNS FROM stock_journal LIKE 'id_entreprise'");
    $hasEnterpriseColumn = $checkColumn->rowCount() > 0;
    
    if ($hasEnterpriseColumn) {
        // Récupérer l'historique filtré par entreprise
        $sql = "SELECT * FROM stock_journal WHERE id_entreprise = :id_entreprise ORDER BY date DESC LIMIT 500";
        $stmt = $bdd->prepare($sql);
        $stmt->execute(['id_entreprise' => $enterpriseId]);
    } else {
        // Fallback : récupérer tous les journaux (pour compatibilité avec anciennes installations)
        $sql = "SELECT * FROM stock_journal ORDER BY date DESC LIMIT 500";
        $stmt = $bdd->prepare($sql);
        $stmt->execute();
    }
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    // Ajouter un mouvement
    $data = json_decode(file_get_contents('php://input'), true);
    $sql = "INSERT INTO stock_journal (date, user, action, details) VALUES (NOW(), :user, :action, :details)";
    $stmt = $bdd->prepare($sql);
    $stmt->bindValue(':user', $data['user'] ?? 'inconnu', PDO::PARAM_STR);
    $stmt->bindValue(':action', $data['action'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':details', $data['details'] ?? '', PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Méthode non supportée']);
exit;
