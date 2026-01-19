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
    
    // Charger les permissions d'accès (entrepôts et points de vente) depuis les tables de liaison
    $permissions_entrepots = [];
    $permissions_points_vente = [];
    
    try {
        // Essayer de charger depuis les tables de liaison
        $stmtE = $bdd->prepare("SELECT id_entrepot FROM stock_utilisateur_entrepot WHERE id_utilisateur = :id");
        $stmtE->execute(['id' => $user['id_utilisateur']]);
        $permissions_entrepots = $stmtE->fetchAll(PDO::FETCH_COLUMN);
        // Convertir en entiers pour éviter les problèmes de type
        $permissions_entrepots = array_map('intval', $permissions_entrepots);
    } catch (PDOException $e) {
        // Si la table n'existe pas ou erreur, utiliser un tableau vide
        error_log("⚠️ [Login] Erreur lors du chargement des permissions entrepôts: " . $e->getMessage());
        $permissions_entrepots = [];
    } catch (Exception $e) {
        error_log("⚠️ [Login] Erreur lors du chargement des permissions entrepôts: " . $e->getMessage());
        $permissions_entrepots = [];
    }
    
    try {
        // Essayer de charger depuis les tables de liaison
        $stmtPV = $bdd->prepare("SELECT id_point_vente FROM stock_utilisateur_point_vente WHERE id_utilisateur = :id");
        $stmtPV->execute(['id' => $user['id_utilisateur']]);
        $permissions_points_vente = $stmtPV->fetchAll(PDO::FETCH_COLUMN);
        // Convertir en entiers pour éviter les problèmes de type
        $permissions_points_vente = array_map('intval', $permissions_points_vente);
    } catch (PDOException $e) {
        // Si la table n'existe pas ou erreur, utiliser un tableau vide
        error_log("⚠️ [Login] Erreur lors du chargement des permissions points de vente: " . $e->getMessage());
        $permissions_points_vente = [];
    } catch (Exception $e) {
        error_log("⚠️ [Login] Erreur lors du chargement des permissions points de vente: " . $e->getMessage());
        $permissions_points_vente = [];
    }
    
    // Si les tables de liaison sont vides, essayer de lire depuis le JSON (fallback)
    if (empty($permissions_entrepots)) {
        $jsonEntrepots = $user['permissions_entrepots'] ?? null;
        if ($jsonEntrepots && $jsonEntrepots !== 'NULL' && $jsonEntrepots !== '[]') {
            $decoded = json_decode($jsonEntrepots, true);
            if (is_array($decoded) && !empty($decoded)) {
                $permissions_entrepots = array_map('intval', $decoded);
                $permissions_entrepots = array_filter($permissions_entrepots, function($id) { return $id > 0; });
                $permissions_entrepots = array_values($permissions_entrepots);
            }
        }
    }
    
    if (empty($permissions_points_vente)) {
        $jsonPointsVente = $user['permissions_points_vente'] ?? null;
        if ($jsonPointsVente && $jsonPointsVente !== 'NULL' && $jsonPointsVente !== '[]') {
            $decoded = json_decode($jsonPointsVente, true);
            if (is_array($decoded) && !empty($decoded)) {
                $permissions_points_vente = array_map('intval', $decoded);
                $permissions_points_vente = array_filter($permissions_points_vente, function($id) { return $id > 0; });
                $permissions_points_vente = array_values($permissions_points_vente);
            }
        }
    }
    
    // Ajouter les permissions à l'utilisateur
    $user['permissions_entrepots'] = $permissions_entrepots;
    $user['permissions_points_vente'] = $permissions_points_vente;
    
    // Log pour debug
    error_log("🔐 [Login] Utilisateur: " . $user['username'] . " (ID: " . $user['id_utilisateur'] . ")");
    error_log("🔐 [Login] Permissions entrepôts: " . json_encode($permissions_entrepots));
    error_log("🔐 [Login] Permissions points de vente: " . json_encode($permissions_points_vente));
    
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
    
    // Vérifier que les permissions sont bien présentes
    error_log("🔐 [Login Response] User ID: " . $user['id_utilisateur']);
    error_log("🔐 [Login Response] Permissions entrepôts: " . json_encode($user['permissions_entrepots'] ?? 'NON DÉFINI'));
    error_log("🔐 [Login Response] Permissions points de vente: " . json_encode($user['permissions_points_vente'] ?? 'NON DÉFINI'));
    
    $resultat = [
        'user' => $user,
        'token' => $token,
        'expires_in' => JWT_EXPIRATION // Durée d'expiration en secondes
    ];
    
    // Log de la réponse complète (sans le mot de passe)
    $responseLog = $resultat;
    unset($responseLog['user']['mot_de_passe']);
    error_log("🔐 [Login Response] Réponse complète: " . json_encode($responseLog, JSON_UNESCAPED_UNICODE));
    
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
