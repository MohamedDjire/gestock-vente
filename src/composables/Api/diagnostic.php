<?php
/**
 * Fichier de diagnostic - Pour identifier le problème
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$diagnostic = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'current_dir' => __DIR__,
    'files' => []
];

// Vérifier les fichiers
$filesToCheck = [
    'config/database.php' => __DIR__ . '/config/database.php',
    'database.php' => __DIR__ . '/database.php',
    'functions/middleware_auth.php' => __DIR__ . '/functions/middleware_auth.php',
    'middleware_auth.php' => __DIR__ . '/middleware_auth.php',
    'config.php' => __DIR__ . '/config.php',
    '../config.php' => __DIR__ . '/../config.php'
];

foreach ($filesToCheck as $name => $path) {
    $diagnostic['files'][$name] = [
        'exists' => file_exists($path),
        'path' => $path,
        'readable' => file_exists($path) ? is_readable($path) : false
    ];
}

// Tester le chargement de config/database.php
if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        require_once __DIR__ . '/config/database.php';
        $diagnostic['database_config'] = [
            'loaded' => true,
            'function_exists' => function_exists('createDatabaseConnection')
        ];
    } catch (Exception $e) {
        $diagnostic['database_config'] = [
            'loaded' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Tester le chargement de functions/middleware_auth.php
if (file_exists(__DIR__ . '/functions/middleware_auth.php')) {
    try {
        require_once __DIR__ . '/functions/middleware_auth.php';
        $diagnostic['middleware'] = [
            'loaded' => true,
            'function_exists' => function_exists('authenticateAndAuthorize')
        ];
    } catch (Exception $e) {
        $diagnostic['middleware'] = [
            'loaded' => false,
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode($diagnostic, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
