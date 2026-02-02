<?php
/**
 * CORS central - À inclure au tout début de chaque fichier API.
 * Jamais Access-Control-Allow-Origin: * avec Credentials: true (interdit par le navigateur).
 */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost:8080',
    'https://aliadjame.com'
];
if (in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
