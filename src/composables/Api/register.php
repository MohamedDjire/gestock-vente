<?php
/**
 * API de Registration - Inscription utilisateur publique
 * Endpoint: /api-stock/register.php
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
    ], JSON_UNESCAPED_UNICODE);
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
 * Vérifier si un email existe déjà
 */
function emailExists($bdd, $email) {
    $stmt = $bdd->prepare("SELECT id_utilisateur FROM stock_utilisateur WHERE email = :email");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch() !== false;
}

/**
 * Vérifier si un username existe déjà
 */
function usernameExists($bdd, $username) {
    $stmt = $bdd->prepare("SELECT id_utilisateur FROM stock_utilisateur WHERE username = :username");
    $stmt->execute(['username' => $username]);
    return $stmt->fetch() !== false;
}

/**
 * Récupérer un utilisateur par ID
 */
function getUserById($bdd, $userId) {
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
            e.nom_entreprise,
            e.sigle,
            e.slug,
            e.statut AS entreprise_statut
        FROM stock_utilisateur u
        INNER JOIN stock_entreprise e ON u.id_entreprise = e.id_entreprise
        WHERE u.id_utilisateur = :id
    ");
    
    $stmt->execute(['id' => $userId]);
    return $stmt->fetch();
}

/**
 * Créer un nouvel utilisateur (inscription publique)
 * Note: Pour l'inscription publique, l'utilisateur doit fournir un id_entreprise valide
 * ou nous devons créer une entreprise par défaut
 */
function registerUser($bdd, $data) {
    // Vérifier les champs requis
    if (empty($data['nom']) && empty($data['user_last_name'])) {
        throw new Exception("Le nom est requis");
    }
    if (empty($data['prenom']) && empty($data['user_first_name'])) {
        throw new Exception("Le prénom est requis");
    }
    if (empty($data['email'])) {
        throw new Exception("L'email est requis");
    }
    if (empty($data['username'])) {
        throw new Exception("Le nom d'utilisateur est requis");
    }
    if (empty($data['password']) && empty($data['mot_de_passe'])) {
        throw new Exception("Le mot de passe est requis");
    }
    
    // Vérifier si l'email existe déjà
    if (emailExists($bdd, $data['email'])) {
        throw new Exception("Cet email est déjà utilisé");
    }
    
    // Vérifier si le username existe déjà
    if (usernameExists($bdd, $data['username'])) {
        throw new Exception("Ce nom d'utilisateur est déjà utilisé");
    }
    
    // Récupérer ou créer une entreprise par défaut
    // Pour l'instant, on exige un id_entreprise
    // Vous pouvez modifier cela pour créer une entreprise par défaut si nécessaire
    if (empty($data['id_entreprise'])) {
        // Option 1: Créer une entreprise par défaut pour les nouveaux utilisateurs
        // Option 2: Exiger un id_entreprise (actuel)
        throw new Exception("L'ID entreprise est requis pour l'inscription. Contactez l'administrateur.");
    }
    
    // Hasher le mot de passe
    $password = $data['password'] ?? $data['mot_de_passe'];
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Préparer les données
    $nom = $data['nom'] ?? $data['user_last_name'];
    $prenom = $data['prenom'] ?? $data['user_first_name'];
    
    // Insérer l'utilisateur
    $stmt = $bdd->prepare("
        INSERT INTO stock_utilisateur (
            id_entreprise, nom, prenom, username, email, telephone,
            mot_de_passe, role, photo, statut, date_naissance, genre, fonction
        ) VALUES (
            :id_entreprise, :nom, :prenom, :username, :email, :telephone,
            :mot_de_passe, :role, :photo, :statut, :date_naissance, :genre, :fonction
        )
    ");
    
    $stmt->execute([
        'id_entreprise' => $data['id_entreprise'],
        'nom' => $nom,
        'prenom' => $prenom,
        'username' => $data['username'],
        'email' => $data['email'],
        'telephone' => $data['telephone'] ?? null,
        'mot_de_passe' => $hashedPassword,
        'role' => $data['role'] ?? 'Agent',
        'photo' => $data['photo'] ?? null,
        'statut' => $data['statut'] ?? 'actif',
        'date_naissance' => $data['date_naissance'] ?? null,
        'genre' => $data['genre'] ?? null,
        'fonction' => $data['fonction'] ?? null
    ]);
    
    $userId = $bdd->lastInsertId();
    return getUserById($bdd, $userId);
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
    
    // Enregistrer l'utilisateur
    $user = registerUser($bdd, $data);
    
    // Générer le token
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
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
