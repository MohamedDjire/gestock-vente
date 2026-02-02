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
// Pour création entreprise (inscription Admin)
$functionsEntreprise = __DIR__ . '/functions_entreprise.php';
if (!file_exists($functionsEntreprise)) {
    $functionsEntreprise = __DIR__ . '/functions/functions_entreprise.php';
}
if (file_exists($functionsEntreprise)) {
    require_once $functionsEntreprise;
}

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
 * Politique mot de passe:
 * - min 6 caractères
 * - au moins 1 lettre
 * - au moins 1 chiffre
 * - caractères spéciaux: autorisés mais non obligatoires
 */
function validatePasswordPolicy($password) {
    $password = (string)$password;
    if (strlen($password) < 6) {
        throw new Exception("Mot de passe invalide: minimum 6 caractères.");
    }
    if (!preg_match('/[A-Za-z]/', $password)) {
        throw new Exception("Mot de passe invalide: doit contenir au moins une lettre.");
    }
    if (!preg_match('/\d/', $password)) {
        throw new Exception("Mot de passe invalide: doit contenir au moins un chiffre.");
    }
    return true;
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
 * Résout l'identifiant entreprise : accepte un id numérique ou un code alphanumérique (slug, 8 caractères).
 */
function resolveIdEntreprise($bdd, $value) {
    if ($value === null || $value === '') {
        return null;
    }
    if (is_numeric($value)) {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }
    $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', (string)$value)));
    if (strlen($code) < 1) {
        return null;
    }
    $stmt = $bdd->prepare("SELECT id_entreprise FROM stock_entreprise WHERE slug = :slug LIMIT 1");
    $stmt->execute(['slug' => $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['id_entreprise'] : null;
}

/**
 * Créer un nouvel utilisateur (inscription publique)
 * - Admin : nom_entreprise requis ; id_entreprise et code (slug) sont générés automatiquement à la création de l'entreprise.
 * - Agent : code entreprise ou id entreprise requis (fourni par l'administrateur).
 */
function registerUser($bdd, $data) {
    // Champs requis communs
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
    
    if (emailExists($bdd, $data['email'])) {
        throw new Exception("Cet email est déjà utilisé");
    }
    if (usernameExists($bdd, $data['username'])) {
        throw new Exception("Ce nom d'utilisateur est déjà utilisé");
    }
    
    // Décision Admin vs Agent : si nom_entreprise est fourni, c'est TOUJOURS une inscription Admin (jamais demander id_entreprise)
    $hasNomEntreprise = !empty(trim((string)($data['nom_entreprise'] ?? '')));
    $role = isset($data['role']) ? trim((string)$data['role']) : '';
    $role = in_array($role, ['Admin', 'admin', 'Agent', 'agent'], true) ? $role : 'Agent';
    $isAdmin = $hasNomEntreprise || (strtolower($role) === 'admin');

    $idEntreprise = null;

    if ($isAdmin) {
        // Inscription Admin : créer l'entreprise d'abord
        if (!$hasNomEntreprise) {
            throw new Exception("Le nom de l'entreprise est requis pour un compte administrateur.");
        }
        if (!function_exists('createEnterprise')) {
            throw new Exception("Création d'entreprise non disponible sur ce serveur.");
        }
        $entrepriseData = [
            'nom_entreprise' => $data['nom_entreprise'],
            'sigle' => $data['sigle'] ?? null,
            'telephone' => $data['telephone_entreprise'] ?? $data['telephone'] ?? null,
            'email' => $data['email_entreprise'] ?? $data['email'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'ville' => $data['ville'] ?? null,
            'pays' => $data['pays'] ?? 'France',
            'code_postal' => $data['code_postal'] ?? null,
            'statut' => 'actif'
        ];
        $resultEnt = createEnterprise($bdd, $entrepriseData);
        $idEntreprise = (int) $resultEnt['id_entreprise'];
    } else {
        // Agent : code entreprise 8 caractères alphanumériques (fourni par l'admin)
        $codeRaw = $data['id_entreprise'] ?? $data['code_entreprise'] ?? null;
        if ($codeRaw === null || $codeRaw === '') {
            throw new Exception("Le code entreprise est requis. L'administrateur vous fournit ce code (8 caractères alphanumériques).");
        }
        $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', (string)$codeRaw)));
        if (strlen($code) !== 8) {
            throw new Exception("Le code entreprise doit comporter exactement 8 caractères alphanumériques.");
        }
        $idEntreprise = resolveIdEntreprise($bdd, $code);
        if ($idEntreprise === null || $idEntreprise <= 0) {
            throw new Exception("Ce code entreprise n'existe pas ou n'est pas valide. Vérifiez le code fourni par l'administrateur.");
        }
    }
    
    $password = $data['password'] ?? $data['mot_de_passe'];
    validatePasswordPolicy($password);
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    $nom = $data['nom'] ?? $data['user_last_name'];
    $prenom = $data['prenom'] ?? $data['user_first_name'];
    $roleStored = $isAdmin ? 'Admin' : 'Agent';
    
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
        'id_entreprise' => $idEntreprise,
        'nom' => $nom,
        'prenom' => $prenom,
        'username' => $data['username'],
        'email' => $data['email'],
        'telephone' => $data['telephone'] ?? null,
        'mot_de_passe' => $hashedPassword,
        'role' => $roleStored,
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








