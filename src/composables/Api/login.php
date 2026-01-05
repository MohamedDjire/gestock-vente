<?php
/**
 * API de Login - Connexion utilisateur uniquement
 * Endpoint: /api-stock/login.php
 */

// =====================================================
// CORS - OBLIGATOIRE EN PREMIER (avant tout autre code)
// =====================================================
// Inclure le fichier CORS central qui gère tous les headers
require_once __DIR__ . '/cors.php';

// Pour les autres requêtes, définir le Content-Type
header('Content-Type: application/json');

// Désactiver l'affichage des erreurs pour éviter qu'elles n'interfèrent avec les headers CORS
// Les erreurs seront quand même loggées
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// =====================================================
// CONFIGURATION BASE DE DONNÉES
// =====================================================
// Sur le serveur: config/database.php
// En local: database.php (à la racine)
require_once __DIR__ . '/config/database.php';

try {
    $bdd = createDatabaseConnection();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de connexion à la base de données',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

// Inclure la configuration
require_once __DIR__ . '/config.php';

// Inclure middleware_auth pour utiliser generateJWT
require_once __DIR__ . '/functions/middleware_auth.php';

/**
 * Générer un token JWT sécurisé à partir des données utilisateur
 */
function generateAuthToken($userData) {
    $secret = JWT_SECRET;
    
    // Préparer les données utilisateur au format attendu
    $userDataFormatted = [
        'user_id' => (int)$userData['id_utilisateur'],
        'user_first_name' => $userData['prenom'] ?? '',
        'user_email' => $userData['email'],
        'user_role' => strtolower($userData['role']),
        'user_enterprise_id' => (int)$userData['id_entreprise']
    ];
    
    return generateJWT($userDataFormatted, $secret);
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
    $token = generateAuthToken($user);
    
    $resultat = [
        'user' => $user,
        'token' => $token,
        'expires_in' => JWT_EXPIRATION // Durée d'expiration en secondes
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
