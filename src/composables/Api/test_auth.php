<?php
/**
 * Script de test pour diagnostiquer les problèmes d'authentification
 * Endpoint: /api-stock/test_auth.php
 */

// Headers CORS
@header('Content-Type: application/json');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
@header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    @http_response_code(200);
    exit;
}

$debug = [];

// 1. Vérifier les headers reçus
$debug['headers_received'] = [];

// Méthode 1: getallheaders()
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $debug['headers_received']['getallheaders'] = $headers;
    $debug['headers_received']['Authorization_getallheaders'] = isset($headers['Authorization']) ? $headers['Authorization'] : 
                                                               (isset($headers['authorization']) ? $headers['authorization'] : 'NON TROUVÉ');
} else {
    $debug['headers_received']['getallheaders'] = 'Fonction non disponible';
}

// Méthode 2: $_SERVER
$debug['headers_received']['HTTP_AUTHORIZATION'] = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : 'NON TROUVÉ';
$debug['headers_received']['REDIRECT_HTTP_AUTHORIZATION'] = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : 'NON TROUVÉ';

// Méthode 3: apache_request_headers()
if (function_exists('apache_request_headers')) {
    $apacheHeaders = apache_request_headers();
    $debug['headers_received']['apache_request_headers'] = $apacheHeaders;
    $debug['headers_received']['Authorization_apache'] = isset($apacheHeaders['Authorization']) ? $apacheHeaders['Authorization'] : 
                                                          (isset($apacheHeaders['authorization']) ? $apacheHeaders['authorization'] : 'NON TROUVÉ');
} else {
    $debug['headers_received']['apache_request_headers'] = 'Fonction non disponible';
}

// 2. Tous les headers $_SERVER commençant par HTTP_
$debug['all_http_headers'] = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $debug['all_http_headers'][$key] = $value;
    }
}

// 3. Tester la récupération du token
$token = null;
$authHeader = '';

// Essayer toutes les méthodes
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                 (isset($headers['authorization']) ? $headers['authorization'] : '');
}

if (empty($authHeader)) {
    $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : 
                 (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '');
}

if (empty($authHeader) && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                 (isset($headers['authorization']) ? $headers['authorization'] : '');
}

$debug['auth_header_found'] = !empty($authHeader);
$debug['auth_header_value'] = $authHeader ? substr($authHeader, 0, 50) . '...' : 'VIDE';

// Extraire le token si présent
if (!empty($authHeader) && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    $token = $matches[1];
    $debug['token_extracted'] = true;
    $debug['token_preview'] = substr($token, 0, 20) . '...';
} else {
    $debug['token_extracted'] = false;
}

// 4. Informations sur le serveur
$debug['server_info'] = [
    'PHP_VERSION' => PHP_VERSION,
    'SERVER_SOFTWARE' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'NON DÉFINI',
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'function_getallheaders_exists' => function_exists('getallheaders'),
    'function_apache_request_headers_exists' => function_exists('apache_request_headers'),
];

echo json_encode([
    'success' => true,
    'message' => 'Diagnostic d\'authentification',
    'debug' => $debug,
    'token_received' => !empty($token)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
