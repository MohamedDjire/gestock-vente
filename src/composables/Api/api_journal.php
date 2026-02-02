<?php
require_once __DIR__ . '/cors.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
header('Content-Type: application/json; charset=utf-8');

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
    // Si le client envoie enterprise_id, il doit correspondre à l'utilisateur connecté (sécurité)
    $clientEnterpriseId = isset($_GET['enterprise_id']) ? (int)$_GET['enterprise_id'] : null;
    if ($clientEnterpriseId !== null && $clientEnterpriseId !== $enterpriseId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé à cette entreprise'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Toujours filtrer par entreprise : une entreprise ne voit que son propre journal
    $checkColumn = $bdd->query("SHOW COLUMNS FROM stock_journal LIKE 'id_entreprise'");
    $hasEnterpriseColumn = $checkColumn->rowCount() > 0;

    if ($hasEnterpriseColumn) {
        $sql = "SELECT * FROM stock_journal WHERE id_entreprise = :id_entreprise ORDER BY date DESC LIMIT 500";
        $stmt = $bdd->prepare($sql);
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = [];
    }

    echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $checkColumn = $bdd->query("SHOW COLUMNS FROM stock_journal LIKE 'id_entreprise'");
    $hasEnterpriseColumn = $checkColumn->rowCount() > 0;

    if ($hasEnterpriseColumn) {
        $sql = "INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), :user, :action, :details, :id_entreprise)";
        $stmt = $bdd->prepare($sql);
        $stmt->bindValue(':user', $data['user'] ?? 'inconnu', PDO::PARAM_STR);
        $stmt->bindValue(':action', $data['action'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':details', $data['details'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':id_entreprise', $enterpriseId, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql = "INSERT INTO stock_journal (date, user, action, details) VALUES (NOW(), :user, :action, :details)";
        $stmt = $bdd->prepare($sql);
        $stmt->bindValue(':user', $data['user'] ?? 'inconnu', PDO::PARAM_STR);
        $stmt->bindValue(':action', $data['action'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':details', $data['details'] ?? '', PDO::PARAM_STR);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Méthode non supportée']);
exit;
