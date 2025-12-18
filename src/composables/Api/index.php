<?php
/**
 * API Stock Utilisateur - Gestion des utilisateurs (stock_utilisateur)
 * Endpoint: /api-stock/
 */

// Activer la gestion des erreurs et définir les headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
// Configuration base de données
$db_name = 'aliad2663340';
$db_user = 'aliad2663340';
$db_pass = 'Stock2025@';
$db_charset = 'utf8mb4';

// Essayer plusieurs méthodes de connexion
$connectionMethods = [
    ['host' => '127.0.0.1', 'socket' => null],  // TCP/IP avec 127.0.0.1 (essayer en premier)
    ['host' => 'localhost', 'socket' => null],  // localhost en TCP/IP
    ['host' => 'localhost', 'socket' => '/var/run/mysqld/mysqld.sock'],  // Socket Unix standard
    ['host' => 'localhost', 'socket' => '/tmp/mysql.sock'],  // Socket Unix alternatif
    ['host' => 'mysql4202.lwspanel.com', 'socket' => null],  // Nom d'hôte du serveur
];

$bdd = null;
$lastError = null;

foreach ($connectionMethods as $method) {
    try {
        if ($method['socket']) {
            // Connexion avec socket Unix spécifié
            $dsn = "mysql:unix_socket={$method['socket']};dbname={$db_name};charset={$db_charset}";
        } else {
            // Connexion TCP/IP
            $dsn = "mysql:host={$method['host']};dbname={$db_name};charset={$db_charset}";
        }
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $bdd = new PDO($dsn, $db_user, $db_pass, $options);
        // Si on arrive ici, la connexion a réussi
        break;
    } catch (PDOException $e) {
        $lastError = $e;
        continue; // Essayer la méthode suivante
    }
}

// Si aucune méthode n'a fonctionné, lancer l'erreur
if ($bdd === null) {
    http_response_code(500);
    $errorInfo = [
        'success' => false, 
        'message' => 'Erreur de connexion à la base de données',
        'error' => $lastError ? $lastError->getMessage() : 'Aucune méthode de connexion n\'a fonctionné',
        'error_code' => $lastError ? $lastError->getCode() : 0,
        'database' => $db_name,
        'user' => $db_user,
        'tried_methods' => array_map(function($m) {
            return $m['socket'] ? "socket: {$m['socket']}" : "host: {$m['host']}";
        }, $connectionMethods),
        'suggestion' => 'Vérifiez les paramètres de connexion dans votre panneau d\'hébergement ou contactez le support.'
    ];
    
    echo json_encode($errorInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
 * Authentifier et autoriser un utilisateur
 */
function authenticateAndAuthorize($bdd, $enterpriseId = null) {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }
    } elseif (isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    if (!$token) {
        throw new Exception("Token d'authentification requis", 401);
    }
    
    $decoded = base64_decode($token);
    $parts = explode(':', $decoded);
    
    // Format du token: userId:slug:timestamp
    if (count($parts) !== 3) {
        // Support de l'ancien format (userId:timestamp) pour compatibilité
        if (count($parts) === 2) {
            $userId = $parts[0];
            $timestamp = $parts[1];
            $slug = null; // Ancien format sans slug
        } else {
            throw new Exception("Token invalide", 401);
        }
    } else {
        $userId = $parts[0];
        $slug = $parts[1];
        $timestamp = $parts[2];
    }
    
    if (time() - $timestamp > 86400) {
        throw new Exception("Token expiré", 401);
    }
    
    $stmt = $bdd->prepare("
        SELECT 
            u.id_utilisateur,
            u.id_entreprise,
            u.nom,
            u.prenom,
            u.username,
            u.email,
            u.role,
            u.statut,
            e.statut AS entreprise_statut,
            e.slug AS entreprise_slug
        FROM stock_utilisateur u
        INNER JOIN stock_entreprise e ON u.id_entreprise = e.id_entreprise
        WHERE u.id_utilisateur = :id AND u.statut = 'actif'
    ");
    
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Utilisateur non trouvé ou inactif", 401);
    }
    
    if ($user['entreprise_statut'] !== 'actif') {
        throw new Exception("L'entreprise associée à ce compte est inactive", 403);
    }
    
    // Vérifier que le slug du token correspond au slug de l'entreprise
    if ($slug !== null && $user['entreprise_slug'] !== $slug) {
        throw new Exception("Token invalide - Le slug de l'entreprise ne correspond pas", 401);
    }
    
    if ($enterpriseId !== null && $user['id_entreprise'] != $enterpriseId) {
        if ($user['role'] !== 'Admin' && $user['role'] !== 'SuperAdmin') {
            throw new Exception("Accès non autorisé à cette entreprise", 403);
        }
    }
    
    return [
        'id' => $user['id_utilisateur'],
        'user_id' => $user['id_utilisateur'],
        'enterprise_id' => $user['id_entreprise'],
        'user_enterprise_id' => $user['id_entreprise'],
        'user_role' => strtolower($user['role']),
        'role' => $user['role'],
        'enterprise_slug' => $user['entreprise_slug'] ?? null
    ];
}

/**
 * Authentifier un utilisateur avec email et mot de passe
 */
function loginUser($bdd, $email, $password) {
    // D'abord vérifier si l'utilisateur existe (même inactif)
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
        throw new Exception("Aucun utilisateur trouvé avec cet email");
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
        // Pour le débogage, vérifier si le hash est valide
        $hashInfo = password_get_info($user['mot_de_passe']);
        if ($hashInfo['algo'] === null) {
            throw new Exception("Le mot de passe stocké n'est pas un hash valide. Contactez l'administrateur.");
        }
        throw new Exception("Mot de passe incorrect");
    }
    
    $updateStmt = $bdd->prepare("UPDATE stock_utilisateur SET dernier_login = NOW() WHERE id_utilisateur = :id");
    $updateStmt->execute(['id' => $user['id_utilisateur']]);
    
    unset($user['mot_de_passe']);
    return $user;
}

/**
 * Récupérer tous les utilisateurs d'une entreprise
 */
function getAllUsers($bdd, $enterpriseId) {
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
            u.dernier_login,
            u.date_naissance,
            u.genre,
            u.fonction,
            u.date_creation,
            u.date_modification,
            e.nom_entreprise,
            e.sigle
        FROM stock_utilisateur u
        INNER JOIN stock_entreprise e ON u.id_entreprise = e.id_entreprise
        WHERE u.id_entreprise = :enterprise_id
        ORDER BY u.nom, u.prenom
    ");
    
    $stmt->execute(['enterprise_id' => $enterpriseId]);
    return $stmt->fetchAll();
}

/**
 * Récupérer un utilisateur par son ID
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
            u.dernier_login,
            u.date_naissance,
            u.genre,
            u.fonction,
            u.date_creation,
            u.date_modification,
            e.nom_entreprise,
            e.sigle
        FROM stock_utilisateur u
        INNER JOIN stock_entreprise e ON u.id_entreprise = e.id_entreprise
        WHERE u.id_utilisateur = :id
    ");
    
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Utilisateur non trouvé");
    }
    
    return $user;
}

/**
 * Rechercher des utilisateurs
 */
function searchUsers($bdd, $query, $enterpriseId) {
    $searchTerm = "%{$query}%";
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
            e.nom_entreprise
        FROM stock_utilisateur u
        INNER JOIN stock_entreprise e ON u.id_entreprise = e.id_entreprise
        WHERE u.id_entreprise = :enterprise_id
        AND (
            u.nom LIKE :query 
            OR u.prenom LIKE :query 
            OR u.email LIKE :query 
            OR u.username LIKE :query
            OR u.fonction LIKE :query
        )
        ORDER BY u.nom, u.prenom
    ");
    
    $stmt->execute(['enterprise_id' => $enterpriseId, 'query' => $searchTerm]);
    return $stmt->fetchAll();
}

/**
 * Récupérer les utilisateurs par rôle
 */
function getUsersByRole($bdd, $role, $enterpriseId) {
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
            e.nom_entreprise
        FROM stock_utilisateur u
        INNER JOIN stock_entreprise e ON u.id_entreprise = e.id_entreprise
        WHERE u.id_entreprise = :enterprise_id AND u.role = :role
        ORDER BY u.nom, u.prenom
    ");
    
    $stmt->execute(['enterprise_id' => $enterpriseId, 'role' => $role]);
    return $stmt->fetchAll();
}

/**
 * Créer un nouvel utilisateur
 */
function createUser($bdd, $data) {
    $checkStmt = $bdd->prepare("SELECT id_utilisateur FROM stock_utilisateur WHERE email = :email");
    $checkStmt->execute(['email' => $data['email']]);
    if ($checkStmt->fetch()) {
        throw new Exception("Cet email est déjà utilisé");
    }
    
    $checkStmt = $bdd->prepare("SELECT id_utilisateur FROM stock_utilisateur WHERE username = :username");
    $checkStmt->execute(['username' => $data['username']]);
    if ($checkStmt->fetch()) {
        throw new Exception("Ce nom d'utilisateur est déjà utilisé");
    }
    
    $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
    
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
        'id_entreprise' => $data['id_entreprise'] ?? $data['enterprise_id'],
        'nom' => $data['nom'] ?? $data['user_last_name'],
        'prenom' => $data['prenom'] ?? $data['user_first_name'],
        'username' => $data['username'],
        'email' => $data['email'],
        'telephone' => $data['telephone'] ?? null,
        'mot_de_passe' => $hashedPassword,
        'role' => $data['role'] ?? 'Agent',
        'photo' => $data['photo'] ?? null,
        'statut' => $data['statut'] ?? 'actif', // Par défaut: autorisé à se connecter
        'date_naissance' => $data['date_naissance'] ?? null,
        'genre' => $data['genre'] ?? null,
        'fonction' => $data['fonction'] ?? null
    ]);
    
    $userId = $bdd->lastInsertId();
    return getUserById($bdd, $userId);
}

/**
 * Mettre à jour un utilisateur
 */
function updateUser($bdd, $userId, $data) {
    $fields = [];
    $params = ['id' => $userId];
    
    $allowedFields = ['nom', 'prenom', 'username', 'email', 'telephone', 'role', 'photo', 'statut', 'date_naissance', 'genre', 'fonction'];
    
    foreach ($allowedFields as $field) {
        $dataKey = $field;
        if ($field === 'nom' && isset($data['user_last_name'])) {
            $dataKey = 'user_last_name';
        }
        if ($field === 'prenom' && isset($data['user_first_name'])) {
            $dataKey = 'user_first_name';
        }
        
        if (isset($data[$dataKey])) {
            $fields[] = "{$field} = :{$field}";
            $params[$field] = $data[$dataKey];
        }
    }
    
    if (empty($fields)) {
        throw new Exception("Aucune donnée à mettre à jour");
    }
    
    if (isset($data['email'])) {
        $checkStmt = $bdd->prepare("SELECT id_utilisateur FROM stock_utilisateur WHERE email = :email AND id_utilisateur != :id");
        $checkStmt->execute(['email' => $data['email'], 'id' => $userId]);
        if ($checkStmt->fetch()) {
            throw new Exception("Cet email est déjà utilisé par un autre utilisateur");
        }
    }
    
    if (isset($data['username'])) {
        $checkStmt = $bdd->prepare("SELECT id_utilisateur FROM stock_utilisateur WHERE username = :username AND id_utilisateur != :id");
        $checkStmt->execute(['username' => $data['username'], 'id' => $userId]);
        if ($checkStmt->fetch()) {
            throw new Exception("Ce nom d'utilisateur est déjà utilisé");
        }
    }
    
    $fields[] = "date_modification = NOW()";
    $sql = "UPDATE stock_utilisateur SET " . implode(', ', $fields) . " WHERE id_utilisateur = :id";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    
    return getUserById($bdd, $userId);
}

/**
 * Mettre à jour le statut d'un utilisateur
 * Statuts possibles: 'actif' (autoriser à se connecter) ou 'bloque' (bloqué par l'admin)
 */
function updateUserStatus($bdd, $userId, $status) {
    $allowedStatuses = ['actif', 'bloque'];
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception("Statut invalide. Valeurs autorisées : " . implode(', ', $allowedStatuses) . ". Pour supprimer un utilisateur, utilisez l'action 'delete'.");
    }
    
    $stmt = $bdd->prepare("UPDATE stock_utilisateur SET statut = :statut, date_modification = NOW() WHERE id_utilisateur = :id");
    $stmt->execute(['statut' => $status, 'id' => $userId]);
    
    return getUserById($bdd, $userId);
}

/**
 * Bloquer un utilisateur (alias pour updateUserStatus avec 'bloque')
 */
function blockUser($bdd, $userId) {
    return updateUserStatus($bdd, $userId, 'bloque');
}

/**
 * Débloquer un utilisateur (alias pour updateUserStatus avec 'actif')
 */
function unblockUser($bdd, $userId) {
    return updateUserStatus($bdd, $userId, 'actif');
}

/**
 * Mettre à jour le mot de passe d'un utilisateur
 */
function updateUserPassword($bdd, $userId, $oldPassword, $newPassword) {
    $stmt = $bdd->prepare("SELECT mot_de_passe FROM stock_utilisateur WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $userId]);
    $userData = $stmt->fetch();
    
    if (!password_verify($oldPassword, $userData['mot_de_passe'])) {
        throw new Exception("Ancien mot de passe incorrect");
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    $updateStmt = $bdd->prepare("UPDATE stock_utilisateur SET mot_de_passe = :password, date_modification = NOW() WHERE id_utilisateur = :id");
    $updateStmt->execute(['password' => $hashedPassword, 'id' => $userId]);
    
    return ['message' => 'Mot de passe mis à jour avec succès'];
}

/**
 * Supprimer définitivement un utilisateur de la base de données
 * ATTENTION: Cette action est irréversible
 */
function deleteUser($bdd, $userId) {
    // Vérifier que l'utilisateur existe
    $user = getUserById($bdd, $userId);
    
    // Ne pas permettre la suppression de son propre compte
    // (Cette vérification sera faite dans le code principal)
    
    // Supprimer définitivement de la base de données
    $stmt = $bdd->prepare("DELETE FROM stock_utilisateur WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $userId]);
    
    return [
        'message' => 'Utilisateur supprimé définitivement de la base de données',
        'deleted_user' => [
            'id' => $userId,
            'email' => $user['email'] ?? null,
            'nom' => $user['nom'] ?? null,
            'prenom' => $user['prenom'] ?? null
        ]
    ];
}

/**
 * Supprimer plusieurs utilisateurs
 */
function bulkDeleteUsers($bdd, $userIds) {
    if (empty($userIds)) {
        throw new Exception("Aucun utilisateur à supprimer");
    }
    
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $bdd->prepare("DELETE FROM stock_utilisateur WHERE id_utilisateur IN ($placeholders)");
    $stmt->execute($userIds);
    
    return ['message' => count($userIds) . ' utilisateur(s) supprimé(s) avec succès'];
}

// =====================================================
// TRAITEMENT DES REQUÊTES
// =====================================================

$data = json_decode(file_get_contents("php://input"), true);
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$enterpriseId = isset($_GET['enterprise_id']) ? (int)$_GET['enterprise_id'] : null;

try {
    $bdd->beginTransaction();

    // Authentification non requise pour la connexion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
        if (!isset($data['email']) || !isset($data['password'])) {
            throw new Exception("Identifiants incomplets");
        }
        
        $user = loginUser($bdd, $data['email'], $data['password']);
        $slug = $user['slug'] ?? '';
        $token = generateAuthToken($user['id_utilisateur'], $slug);
        
        $resultat = ['user' => $user, 'token' => $token];
    } else {
        // Pour toutes les autres actions, authentification requise
        $currentUser = authenticateAndAuthorize($bdd, $enterpriseId);
        
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if ($action === 'all') {
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin' && $enterpriseId === null) {
                        $enterpriseId = $currentUser['enterprise_id'];
                    }
                    $resultat = getAllUsers($bdd, $enterpriseId ?? $currentUser['enterprise_id']);
                } elseif ($action === 'single' && $id !== null) {
                    $user = getUserById($bdd, $id);
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin' && $user['id_entreprise'] != $currentUser['enterprise_id']) {
                        throw new Exception("Accès non autorisé à cet utilisateur", 403);
                    }
                    $resultat = $user;
                } elseif ($action === 'search' && isset($_GET['query'])) {
                    $resultat = searchUsers($bdd, $_GET['query'], $enterpriseId ?? $currentUser['enterprise_id']);
                } elseif ($action === 'role' && isset($_GET['role'])) {
                    $resultat = getUsersByRole($bdd, $_GET['role'], $enterpriseId ?? $currentUser['enterprise_id']);
                } else {
                    throw new Exception("Action non valide ou paramètres manquants");
                }
                break;

            case 'POST':
                if ($action === 'create') {
                    if (!isset($data['nom']) && !isset($data['user_last_name'])) {
                        throw new Exception("Le nom est requis");
                    }
                    if (!isset($data['prenom']) && !isset($data['user_first_name'])) {
                        throw new Exception("Le prénom est requis");
                    }
                    if (!isset($data['email'])) {
                        throw new Exception("L'email est requis");
                    }
                    if (!isset($data['username'])) {
                        throw new Exception("Le nom d'utilisateur est requis");
                    }
                    if (!isset($data['mot_de_passe']) && !isset($data['password'])) {
                        throw new Exception("Le mot de passe est requis");
                    }
                    
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin') {
                        $data['id_entreprise'] = $currentUser['enterprise_id'];
                    } elseif (isset($enterpriseId)) {
                        $data['id_entreprise'] = $enterpriseId;
                    }
                    
                    if (isset($data['password'])) {
                        $data['mot_de_passe'] = $data['password'];
                    }
                    
                    $resultat = createUser($bdd, $data);
                } else {
                    throw new Exception("Action non valide pour la méthode POST");
                }
                break;

            case 'PUT':
                if ($action === 'update' && $id !== null) {
                    if (empty($data)) {
                        throw new Exception("Aucune donnée fournie pour la mise à jour");
                    }
                    
                    $targetUser = getUserById($bdd, $id);
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin' && $targetUser['id_entreprise'] != $currentUser['enterprise_id']) {
                        throw new Exception("Accès non autorisé à cet utilisateur", 403);
                    }
                    
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin' && isset($data['id_entreprise'])) {
                        unset($data['id_entreprise']);
                    }
                    
                    $resultat = updateUser($bdd, $id, $data);
                } elseif ($action === 'status' && $id !== null && isset($data['status'])) {
                    $targetUser = getUserById($bdd, $id);
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin' && $targetUser['id_entreprise'] != $currentUser['enterprise_id']) {
                        throw new Exception("Accès non autorisé à cet utilisateur", 403);
                    }
                    $resultat = updateUserStatus($bdd, $id, $data['status']);
                } elseif ($action === 'block' && $id !== null) {
                    // Bloquer un utilisateur
                    $targetUser = getUserById($bdd, $id);
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin' && $targetUser['id_entreprise'] != $currentUser['enterprise_id']) {
                        throw new Exception("Accès non autorisé à cet utilisateur", 403);
                    }
                    if ($id == $currentUser['id']) {
                        throw new Exception("Vous ne pouvez pas bloquer votre propre compte", 403);
                    }
                    $resultat = blockUser($bdd, $id);
                } elseif ($action === 'unblock' && $id !== null) {
                    // Débloquer un utilisateur
                    $targetUser = getUserById($bdd, $id);
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin' && $targetUser['id_entreprise'] != $currentUser['enterprise_id']) {
                        throw new Exception("Accès non autorisé à cet utilisateur", 403);
                    }
                    $resultat = unblockUser($bdd, $id);
                } elseif ($action === 'password' && $id !== null) {
                    if (!isset($data['password']) || !isset($data['new_password'])) {
                        throw new Exception("Données de mot de passe incomplètes");
                    }
                    
                    $targetUser = getUserById($bdd, $id);
                    if ($id != $currentUser['id'] && $currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin' && $targetUser['id_entreprise'] != $currentUser['enterprise_id']) {
                        throw new Exception("Accès non autorisé à cet utilisateur", 403);
                    }
                    
                    $resultat = updateUserPassword($bdd, $id, $data['password'], $data['new_password']);
                } else {
                    throw new Exception("Action non valide ou paramètres manquants pour la méthode PUT");
                }
                break;

            case 'DELETE':
                if ($action === 'delete' && $id !== null) {
                    $targetUser = getUserById($bdd, $id);
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin') {
                        throw new Exception("Accès non autorisé - Seuls les administrateurs peuvent supprimer des utilisateurs", 403);
                    }
                    // Ne pas permettre la suppression de son propre compte
                    if ($id == $currentUser['id']) {
                        throw new Exception("Vous ne pouvez pas supprimer votre propre compte", 403);
                    }
                    $resultat = deleteUser($bdd, $id);
                } elseif ($action === 'bulk' && isset($data['ids']) && is_array($data['ids'])) {
                    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin') {
                        foreach ($data['ids'] as $userId) {
                            $targetUser = getUserById($bdd, $userId);
                            if ($targetUser['id_entreprise'] != $currentUser['enterprise_id']) {
                                throw new Exception("Accès non autorisé à un ou plusieurs utilisateurs", 403);
                            }
                        }
                    }
                    $resultat = bulkDeleteUsers($bdd, $data['ids']);
                } else {
                    throw new Exception("Action non valide ou paramètres manquants pour la méthode DELETE");
                }
                break;

            default:
                throw new Exception("Méthode HTTP non supportée");
        }
    }

    $bdd->commit();

    echo json_encode([
        'success' => true,
        'data' => $resultat
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($bdd->inTransaction()) {
        $bdd->rollBack();
    }

    $httpCode = 400;
    if (strpos($e->getMessage(), "non autorisé") !== false || strpos($e->getMessage(), "Accès non autorisé") !== false) {
        $httpCode = 403;
    } elseif (strpos($e->getMessage(), "Authentification requise") !== false || strpos($e->getMessage(), "Token invalide") !== false || strpos($e->getMessage(), "Token expiré") !== false) {
        $httpCode = 401;
    } elseif (strpos($e->getMessage(), "non trouvé") !== false) {
        $httpCode = 404;
    }

    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
