<?php
/**
 * Test simple pour vérifier que les headers CORS fonctionnent
 * Endpoint: /api-stock/test_cors_simple.php
 */

// Headers CORS - SANS @ pour voir les erreurs
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');

// Répondre immédiatement aux requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Réponse simple pour tester
echo json_encode([
    'success' => true,
    'message' => 'Test CORS réussi',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD']
], JSON_UNESCAPED_UNICODE);
