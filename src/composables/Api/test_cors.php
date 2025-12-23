<?php
/**
 * Fichier de test CORS - Pour diagnostiquer le problème
 */

// Définir les headers CORS AVANT toute autre opération
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');
header('Content-Type: application/json; charset=utf-8');

// Répondre immédiatement aux requêtes OPTIONS (préflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header('Content-Length: 0');
    exit;
}

// Test simple
echo json_encode([
    'success' => true,
    'message' => 'Test CORS réussi',
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
