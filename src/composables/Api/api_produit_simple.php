<?php
/**
 * API Stock Produit - Version simplifiée pour diagnostic
 * Endpoint: /api-stock/api_produit_simple.php
 */

// ENVOYER LES HEADERS CORS EN PREMIER (même avant les erreurs)
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
@header('Access-Control-Allow-Headers: Content-Type, Authorization');
@header('Content-Type: application/json');

// Répondre immédiatement aux requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    @http_response_code(200);
    exit;
}

// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Fonction pour envoyer une erreur avec CORS
function sendError($message, $code = 500) {
    @header('Access-Control-Allow-Origin: *');
    @header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    @header('Access-Control-Allow-Headers: Content-Type, Authorization');
    @header('Content-Type: application/json');
    @http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// Vérifier et charger config/database.php
$dbPath = __DIR__ . '/config/database.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/database.php';
}
if (!file_exists($dbPath)) {
    sendError('Fichier database.php introuvable. Vérifiez: config/database.php ou database.php');
}
@require_once $dbPath;

// Vérifier que la fonction existe
if (!function_exists('createDatabaseConnection')) {
    sendError('Fonction createDatabaseConnection() introuvable dans database.php');
}

// Connexion à la base de données
try {
    $bdd = createDatabaseConnection();
} catch (Exception $e) {
    sendError('Erreur de connexion DB: ' . $e->getMessage());
}

// Vérifier et charger functions/middleware_auth.php
$middlewarePath = __DIR__ . '/functions/middleware_auth.php';
if (!file_exists($middlewarePath)) {
    $middlewarePath = __DIR__ . '/middleware_auth.php';
}
if (!file_exists($middlewarePath)) {
    sendError('Fichier middleware_auth.php introuvable. Vérifiez: functions/middleware_auth.php ou middleware_auth.php');
}
@require_once $middlewarePath;

// Vérifier que la fonction existe
if (!function_exists('authenticateAndAuthorize')) {
    sendError('Fonction authenticateAndAuthorize() introuvable dans middleware_auth.php');
}

// Authentification
try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = $currentUser['enterprise_id'];
} catch (Exception $e) {
    @http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Si on arrive ici, tout fonctionne
echo json_encode([
    'success' => true,
    'message' => 'API fonctionne correctement',
    'enterprise_id' => $enterpriseId
], JSON_UNESCAPED_UNICODE);

