<?php
/**
 * Script temporaire pour réinitialiser le mot de passe d'un utilisateur
 * À SUPPRIMER après utilisation pour des raisons de sécurité
 * Endpoint: /api-stock/reset_password.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration base de données
$db_name = 'aliad2663340';
$db_user = 'aliad2663340';
$db_pass = 'Stock2025@';
$db_charset = 'utf8mb4';

// Connexion à la base de données
$connectionMethods = [
    ['host' => '127.0.0.1', 'socket' => null],
    ['host' => 'localhost', 'socket' => null],
    ['host' => 'localhost', 'socket' => '/var/run/mysqld/mysqld.sock'],
    ['host' => 'localhost', 'socket' => '/tmp/mysql.sock'],
    ['host' => 'mysql4202.lwspanel.com', 'socket' => null],
];

$bdd = null;
$lastError = null;

foreach ($connectionMethods as $method) {
    try {
        if ($method['socket']) {
            $dsn = "mysql:unix_socket={$method['socket']};dbname={$db_name};charset={$db_charset}";
        } else {
            $dsn = "mysql:host={$method['host']};dbname={$db_name};charset={$db_charset}";
        }
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $bdd = new PDO($dsn, $db_user, $db_pass, $options);
        break;
    } catch (PDOException $e) {
        $lastError = $e;
        continue;
    }
}

if ($bdd === null) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de connexion à la base de données'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée. Utilisez POST.");
    }
    
    // Vérifier les données requises
    if (!isset($data['email']) || !isset($data['new_password'])) {
        throw new Exception("Email et nouveau mot de passe requis");
    }
    
    $email = $data['email'];
    $newPassword = $data['new_password'];
    
    // Vérifier que l'utilisateur existe
    $stmt = $bdd->prepare("SELECT id_utilisateur FROM stock_utilisateur WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Aucun utilisateur trouvé avec cet email");
    }
    
    // Hasher le nouveau mot de passe
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Mettre à jour le mot de passe
    $updateStmt = $bdd->prepare("UPDATE stock_utilisateur SET mot_de_passe = :password WHERE email = :email");
    $updateStmt->execute([
        'password' => $hashedPassword,
        'email' => $email
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mot de passe réinitialisé avec succès',
        'email' => $email,
        'new_password' => $newPassword // À retirer en production
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
