<?php
/**
 * API de Login - Connexion utilisateur uniquement
 * Endpoint: /api-stock/login.php
 */

// Activer la gestion des erreurs et définir les headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Répondre immédiatement aux requêtes OPTIONS (préflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =====================================================
// CONFIGURATION BASE DE DONNÉES
// =====================================================
$db_host = '127.0.0.1';
$db_name = 'aliad2663340';
$db_user = 'aliad2663340';
$db_pass = 'Stock2025@';
$db_charset = 'utf8mb4';

// Essayer plusieurs méthodes de connexion
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
        'message' => 'Erreur de connexion à la base de données',
        'error' => $lastError ? $lastError->getMessage() : 'Aucune méthode de connexion n\'a fonctionné'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

/**
 * Générer un token d'authentification avec slug de l'entreprise
 */
function generateAuthToken($userId, $slug) {
    $timestamp = time();
    $tokenData = $userId . ':' . $slug . ':' . $timestamp;
    return base64_encode($tokenData);
}

/**
 * Login utilisateur
 */
function loginUser($bdd, $email, $password) {
    $stmt = $bdd->prepare("
        SELECT 
            u.id_utilisateur,
            u.id_entreprise,
            u.nom,
            u.prenom,
            u.username,
            u.email,
            u.telephone,
            u.role,
            u.photo,
            u.statut,
            u.fonction,
            u.mot_de_passe,
            u.date_naissance,
            u.genre,
            e.nom_entreprise,
            e.sigle,
            e.slug,
            e.statut AS entreprise_statut
        FROM stock_utilisateur u
        INNER JOIN stock_entreprise e ON u.id_entreprise = e.id_entreprise
        WHERE u.email = :email
    ");
    
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Email ou mot de passe incorrect");
    }
    
    // Vérifier le statut de l'utilisateur
    if ($user['statut'] !== 'actif') {
        if ($user['statut'] === 'bloque') {
            throw new Exception("Votre compte a été bloqué par l'administrateur. Contactez le support.");
        }
        throw new Exception("Votre compte n'est pas autorisé à se connecter. Statut: " . $user['statut']);
    }
    
    // Vérifier le statut de l'entreprise
    if ($user['entreprise_statut'] !== 'actif') {
        throw new Exception("L'entreprise associée à ce compte est " . $user['entreprise_statut']);
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $user['mot_de_passe'])) {
        throw new Exception("Email ou mot de passe incorrect");
    }
    
    // Mettre à jour la date de dernière connexion
    $updateStmt = $bdd->prepare("UPDATE stock_utilisateur SET dernier_login = NOW() WHERE id_utilisateur = :id");
    $updateStmt->execute(['id' => $user['id_utilisateur']]);
    
    // Retirer le mot de passe de la réponse
    unset($user['mot_de_passe']);
    
    return $user;
}

// =====================================================
// TRAITEMENT DE LA REQUÊTE
// =====================================================

$data = json_decode(file_get_contents("php://input"), true);

try {
    $bdd->beginTransaction();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée. Utilisez POST.");
    }
    
    // Vérifier les données requises
    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception("Email et mot de passe requis");
    }
    
    // Login utilisateur
    $user = loginUser($bdd, $data['email'], $data['password']);
    $slug = $user['slug'] ?? '';
    $token = generateAuthToken($user['id_utilisateur'], $slug);
    
    $resultat = [
        'user' => $user,
        'token' => $token,
        'expires_in' => 86400 // 24 heures en secondes
    ];
    
    $bdd->commit();
    
    echo json_encode([
        'success' => true,
        'data' => $resultat
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if ($bdd->inTransaction()) {
        $bdd->rollBack();
    }
    
    // Déterminer le code HTTP approprié
    $httpCode = 400;
    if (strpos($e->getMessage(), "bloqué") !== false) {
        $httpCode = 403; // Forbidden
    } elseif (strpos($e->getMessage(), "Email ou mot de passe") !== false) {
        $httpCode = 401; // Unauthorized
    }
    
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
