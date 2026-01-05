<?php
/**
 * Script de test pour vérifier que l'API forfait fonctionne
 * À accéder directement via navigateur : https://aliadjame.com/api-stock/test_api_forfait.php
 */

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

echo json_encode([
    'success' => true,
    'message' => 'API Forfait est accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
], JSON_UNESCAPED_UNICODE);
