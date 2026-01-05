<?php
/**
 * API de Registration - Inscription utilisateur publique
 * Endpoint: /api-stock/register.php
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
require_once __DIR__ . '/config/database.php';

try {
    $bdd = createDatabaseConnection();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de connexion à la base de données',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
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
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>








