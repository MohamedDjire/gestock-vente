<?php
/**
 * API Stock Utilisateur - Gestion des utilisateurs (stock_utilisateur)
 * Endpoint: /api-stock/
 */

// Activer la gestion des erreurs et définir les headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

// Répondre immédiatement aux requêtes OPTIONS (préflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
    $errorInfo = [
        'success' => false, 
        'message' => 'Erreur de connexion à la base de données',
        'error' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'database' => defined('DB_NAME') ? DB_NAME : 'Non défini',
        'user' => defined('DB_USER') ? DB_USER : 'Non défini',
        'suggestion' => 'Vérifiez les paramètres de connexion dans votre panneau d\'hébergement ou contactez le support.'
    ];
    
    echo json_encode($errorInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

// Inclure middleware_auth pour utiliser generateJWT et authenticateAndAuthorize
require_once __DIR__ . '/functions/middleware_auth.php';

// Inclure check_forfait_limits pour vérifier les limites des forfaits
$checkLimitsFile = __DIR__ . '/check_forfait_limits.php';
if (file_exists($checkLimitsFile)) {
    require_once $checkLimitsFile;
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
    $users = $stmt->fetchAll();
    foreach ($users as &$user) {
        // Lire les accès réels depuis les tables de liaison
        $stmtE = $bdd->prepare("SELECT id_entrepot FROM stock_utilisateur_entrepot WHERE id_utilisateur = :id");
        $stmtE->execute(['id' => $user['id_utilisateur']]);
        $user['permissions_entrepots'] = $stmtE->fetchAll(PDO::FETCH_COLUMN);
        $stmtPV = $bdd->prepare("SELECT id_point_vente FROM stock_utilisateur_point_vente WHERE id_utilisateur = :id");
        $stmtPV->execute(['id' => $user['id_utilisateur']]);
        $user['permissions_points_vente'] = $stmtPV->fetchAll(PDO::FETCH_COLUMN);
    }
    return $users;
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
    
    // Charger les permissions depuis les tables de liaison (comme dans login.php)
    $permissions_entrepots = [];
    $permissions_points_vente = [];
    
    try {
        // Essayer de charger depuis les tables de liaison
        $stmtE = $bdd->prepare("SELECT id_entrepot FROM stock_utilisateur_entrepot WHERE id_utilisateur = :id");
        $stmtE->execute(['id' => $user['id_utilisateur']]);
        $permissions_entrepots = $stmtE->fetchAll(PDO::FETCH_COLUMN);
        $permissions_entrepots = array_map('intval', $permissions_entrepots);
    } catch (PDOException $e) {
        // Si la table n'existe pas ou erreur, utiliser un tableau vide
        error_log("⚠️ [Index] Erreur lors du chargement des permissions entrepôts: " . $e->getMessage());
        $permissions_entrepots = [];
    } catch (Exception $e) {
        error_log("⚠️ [Index] Erreur lors du chargement des permissions entrepôts: " . $e->getMessage());
        $permissions_entrepots = [];
    }
    
    try {
        // Essayer de charger depuis les tables de liaison
        $stmtPV = $bdd->prepare("SELECT id_point_vente FROM stock_utilisateur_point_vente WHERE id_utilisateur = :id");
        $stmtPV->execute(['id' => $user['id_utilisateur']]);
        $permissions_points_vente = $stmtPV->fetchAll(PDO::FETCH_COLUMN);
        $permissions_points_vente = array_map('intval', $permissions_points_vente);
    } catch (PDOException $e) {
        // Si la table n'existe pas ou erreur, utiliser un tableau vide
        error_log("⚠️ [Index] Erreur lors du chargement des permissions points de vente: " . $e->getMessage());
        $permissions_points_vente = [];
    } catch (Exception $e) {
        error_log("⚠️ [Index] Erreur lors du chargement des permissions points de vente: " . $e->getMessage());
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
    
    // Utiliser les permissions des tables de liaison (priorité)
    $user['permissions_entrepots'] = $permissions_entrepots;
    $user['permissions_points_vente'] = $permissions_points_vente;

    // Récupérer l'accès comptabilité (champ booléen)
    $stmtAcces = $bdd->prepare("SELECT acces_comptabilite FROM stock_utilisateur WHERE id_utilisateur = :id");
    $stmtAcces->execute(['id' => $userId]);
    $user['acces_comptabilite'] = (bool)($stmtAcces->fetchColumn());

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
    
    validatePasswordPolicy($data['mot_de_passe']);
    $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
    
    $stmt = $bdd->prepare("
        INSERT INTO stock_utilisateur (
            id_entreprise, nom, prenom, username, email, telephone,
            mot_de_passe, role, photo, statut, date_naissance, genre, fonction,
            permissions_entrepots, permissions_points_vente
        ) VALUES (
            :id_entreprise, :nom, :prenom, :username, :email, :telephone,
            :mot_de_passe, :role, :photo, :statut, :date_naissance, :genre, :fonction,
            :permissions_entrepots, :permissions_points_vente
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
        'fonction' => $data['fonction'] ?? null,
        'permissions_entrepots' => isset($data['permissions_entrepots']) ? json_encode($data['permissions_entrepots']) : null,
        'permissions_points_vente' => isset($data['permissions_points_vente']) ? json_encode($data['permissions_points_vente']) : null
    ]);
    
    $userId = $bdd->lastInsertId();

    // Permissions entrepôts : un entrepôt ne peut être attribué qu'à un seul utilisateur
    if (!empty($data['permissions_entrepots']) && is_array($data['permissions_entrepots'])) {
        $stmtDel = $bdd->prepare("DELETE FROM stock_utilisateur_entrepot WHERE id_entrepot = :id_entrepot");
        $stmtIns = $bdd->prepare("INSERT INTO stock_utilisateur_entrepot (id_utilisateur, id_entrepot) VALUES (:id_utilisateur, :id_entrepot)");
        foreach ($data['permissions_entrepots'] as $id_entrepot) {
            $stmtDel->execute(['id_entrepot' => $id_entrepot]);
            $stmtIns->execute(['id_utilisateur' => $userId, 'id_entrepot' => $id_entrepot]);
        }
    }
    // Permissions points de vente : un point de vente ne peut être attribué qu'à un seul utilisateur
    if (!empty($data['permissions_points_vente']) && is_array($data['permissions_points_vente'])) {
        $stmtDel = $bdd->prepare("DELETE FROM stock_utilisateur_point_vente WHERE id_point_vente = :id_point_vente");
        $stmtIns = $bdd->prepare("INSERT INTO stock_utilisateur_point_vente (id_utilisateur, id_point_vente) VALUES (:id_utilisateur, :id_point_vente)");
        foreach ($data['permissions_points_vente'] as $id_pv) {
            $stmtDel->execute(['id_point_vente' => $id_pv]);
            $stmtIns->execute(['id_utilisateur' => $userId, 'id_point_vente' => $id_pv]);
        }
    }
    // Accès comptabilité
    if (isset($data['acces_comptabilite'])) {
        $stmt = $bdd->prepare("UPDATE stock_utilisateur SET acces_comptabilite = :acces WHERE id_utilisateur = :id");
        $stmt->execute(['acces' => $data['acces_comptabilite'] ? 1 : 0, 'id' => $userId]);
    }

    return getUserById($bdd, $userId);
}

/**
 * Mettre à jour un utilisateur
 */
function updateUser($bdd, $userId, $data) {
        // LOG: Afficher le contenu de $data pour debug
        file_put_contents(__DIR__ . '/updateUser_debug.log',
            date('Y-m-d H:i:s') . "\n" .
            'userId: ' . $userId . "\n" .
            'data: ' . print_r($data, true) . "\n\n",
            FILE_APPEND
        );
        // Ajout des permissions dans l'UPDATE
        if (isset($data['permissions_entrepots'])) {
            $fields[] = "permissions_entrepots = :permissions_entrepots";
            $params['permissions_entrepots'] = json_encode($data['permissions_entrepots']);
        }
        if (isset($data['permissions_points_vente'])) {
            $fields[] = "permissions_points_vente = :permissions_points_vente";
            $params['permissions_points_vente'] = json_encode($data['permissions_points_vente']);
        }
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
    
    // Gérer les permissions d'accès
    // 1. Entrepôts
    file_put_contents(__DIR__ . '/updateUser_debug.log',
        date('Y-m-d H:i:s') . " - permissions_entrepots: " . json_encode($data['permissions_entrepots'] ?? null) . "\n",
        FILE_APPEND
    );
    if (isset($data['permissions_entrepots'])) {
        // Un entrepôt ne peut être attribué qu'à un seul utilisateur : d'abord libérer des autres
        if (is_array($data['permissions_entrepots']) && count($data['permissions_entrepots']) > 0) {
            $stmtDelE = $bdd->prepare("DELETE FROM stock_utilisateur_entrepot WHERE id_entrepot = :id_entrepot");
            foreach ($data['permissions_entrepots'] as $id_entrepot) {
                $stmtDelE->execute(['id_entrepot' => $id_entrepot]);
            }
        }
        $bdd->prepare("DELETE FROM stock_utilisateur_entrepot WHERE id_utilisateur = :id")->execute(['id' => $userId]);
        if (is_array($data['permissions_entrepots']) && count($data['permissions_entrepots']) > 0) {
            $stmt = $bdd->prepare("INSERT INTO stock_utilisateur_entrepot (id_utilisateur, id_entrepot) VALUES (:id_utilisateur, :id_entrepot)");
            foreach ($data['permissions_entrepots'] as $id_entrepot) {
                $stmt->execute(['id_utilisateur' => $userId, 'id_entrepot' => $id_entrepot]);
            }
        }
    }
    // 2. Points de vente : un point de vente ne peut être attribué qu'à un seul utilisateur
    file_put_contents(__DIR__ . '/updateUser_debug.log',
        date('Y-m-d H:i:s') . " - permissions_points_vente: " . json_encode($data['permissions_points_vente'] ?? null) . "\n",
        FILE_APPEND
    );
    if (isset($data['permissions_points_vente'])) {
        if (is_array($data['permissions_points_vente']) && count($data['permissions_points_vente']) > 0) {
            $stmtDelPv = $bdd->prepare("DELETE FROM stock_utilisateur_point_vente WHERE id_point_vente = :id_point_vente");
            foreach ($data['permissions_points_vente'] as $id_pv) {
                $stmtDelPv->execute(['id_point_vente' => $id_pv]);
            }
        }
        $bdd->prepare("DELETE FROM stock_utilisateur_point_vente WHERE id_utilisateur = :id")->execute(['id' => $userId]);
        if (is_array($data['permissions_points_vente']) && count($data['permissions_points_vente']) > 0) {
            $stmt = $bdd->prepare("INSERT INTO stock_utilisateur_point_vente (id_utilisateur, id_point_vente) VALUES (:id_utilisateur, :id_point_vente)");
            foreach ($data['permissions_points_vente'] as $id_pv) {
                $stmt->execute(['id_utilisateur' => $userId, 'id_point_vente' => $id_pv]);
            }
        }
    }
    // 3. Accès comptabilité
    if (isset($data['acces_comptabilite'])) {
        $stmt = $bdd->prepare("UPDATE stock_utilisateur SET acces_comptabilite = :acces WHERE id_utilisateur = :id");
        $stmt->execute(['acces' => $data['acces_comptabilite'] ? 1 : 0, 'id' => $userId]);
    }

    // Préparer un debug info à retourner
    $debug = [
        'data_recu' => $data,
        'permissions_entrepots_apres' => [],
        'permissions_points_vente_apres' => []
    ];
    // Vérifier ce qui reste en base après update
    $stmt = $bdd->prepare("SELECT id_entrepot FROM stock_utilisateur_entrepot WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $userId]);
    $debug['permissions_entrepots_apres'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_utilisateur_point_vente WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $userId]);
    $debug['permissions_points_vente_apres'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $user = getUserById($bdd, $userId);
    $user['debug'] = $debug;
    return $user;
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
    
    validatePasswordPolicy($newPassword);
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    $updateStmt = $bdd->prepare("UPDATE stock_utilisateur SET mot_de_passe = :password, date_modification = NOW() WHERE id_utilisateur = :id");
    $updateStmt->execute(['password' => $hashedPassword, 'id' => $userId]);
    
    return ['message' => 'Mot de passe mis à jour avec succès'];
}

/**
 * Réinitialiser le mot de passe d'un utilisateur (admin)
 * Ne requiert PAS l'ancien mot de passe.
 */
function adminResetUserPassword($bdd, $currentUser, $targetUserId, $newPassword, $oldPassword = null) {
    error_log("🔐 [Admin Reset Password] Début - Admin ID: " . ($currentUser['id'] ?? $currentUser['user_id'] ?? 'N/A') . ", Target User ID: {$targetUserId}");
    
    if ($currentUser['user_role'] !== 'admin' && $currentUser['user_role'] !== 'superadmin') {
        error_log("❌ [Admin Reset Password] Accès refusé - Rôle: " . ($currentUser['user_role'] ?? 'N/A'));
        throw new Exception("Accès non autorisé - admin requis", 403);
    }
    
    $targetUser = getUserById($bdd, $targetUserId);
    if (!$targetUser) {
        error_log("❌ [Admin Reset Password] Utilisateur introuvable - ID: {$targetUserId}");
        throw new Exception("Utilisateur introuvable", 404);
    }
    
    error_log("🔐 [Admin Reset Password] Utilisateur cible trouvé - Email: " . ($targetUser['email'] ?? 'N/A') . ", Username: " . ($targetUser['username'] ?? 'N/A'));
    
    // Un admin ne peut reset que dans sa propre entreprise (superadmin peut tout)
    if ($currentUser['user_role'] !== 'superadmin' && $targetUser['id_entreprise'] != $currentUser['enterprise_id']) {
        error_log("❌ [Admin Reset Password] Accès refusé - Entreprise différente");
        throw new Exception("Accès non autorisé à cet utilisateur", 403);
    }

    // Vérifier si c'est l'utilisateur lui-même qui change son mot de passe
    $isChangingOwnPassword = ($currentUser['id'] ?? $currentUser['user_id'] ?? null) == $targetUserId;
    
    // Si un ancien mot de passe est fourni, le vérifier (obligatoire si c'est l'utilisateur lui-même)
    if ($isChangingOwnPassword) {
        // Si c'est l'utilisateur lui-même, l'ancien mot de passe est OBLIGATOIRE
        if ($oldPassword === null || $oldPassword === '') {
            error_log("❌ [Admin Reset Password] Ancien mot de passe requis pour modifier son propre mot de passe - User ID: {$targetUserId}");
            throw new Exception("L'ancien mot de passe est requis pour modifier votre propre mot de passe", 400);
        }
        
        // Vérifier l'ancien mot de passe
        $stmt = $bdd->prepare("SELECT mot_de_passe FROM stock_utilisateur WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $targetUserId]);
        $currentPasswordData = $stmt->fetch();
        
        if (!$currentPasswordData || !password_verify($oldPassword, $currentPasswordData['mot_de_passe'])) {
            error_log("❌ [Admin Reset Password] Ancien mot de passe incorrect pour utilisateur ID: {$targetUserId}");
            throw new Exception("L'ancien mot de passe est incorrect", 400);
        }
        error_log("✅ [Admin Reset Password] Ancien mot de passe vérifié avec succès (utilisateur modifie son propre mot de passe)");
    } else {
        // Si c'est un admin qui change le mot de passe d'un autre utilisateur, l'ancien mot de passe n'est PAS requis
        if ($oldPassword !== null && $oldPassword !== '') {
            // Si fourni, on peut le vérifier pour sécurité supplémentaire, mais ce n'est pas obligatoire
            $stmt = $bdd->prepare("SELECT mot_de_passe FROM stock_utilisateur WHERE id_utilisateur = :id");
            $stmt->execute(['id' => $targetUserId]);
            $currentPasswordData = $stmt->fetch();
            
            if ($currentPasswordData && password_verify($oldPassword, $currentPasswordData['mot_de_passe'])) {
                error_log("✅ [Admin Reset Password] Ancien mot de passe vérifié (optionnel pour admin)");
            } else {
                error_log("⚠️ [Admin Reset Password] Ancien mot de passe fourni mais incorrect (ignoré car admin)");
                // On ne bloque pas, car un admin peut changer n'importe quel mot de passe
            }
        }
        error_log("✅ [Admin Reset Password] Admin modifie le mot de passe d'un autre utilisateur (ancien mot de passe non requis)");
    }

    validatePasswordPolicy($newPassword);
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    error_log("🔐 [Admin Reset Password] Hash généré pour utilisateur ID: {$targetUserId}, Email: " . ($targetUser['email'] ?? 'N/A'));
    
    // Vérifier combien de lignes seront affectées AVANT la mise à jour
    $checkBeforeStmt = $bdd->prepare("SELECT COUNT(*) as count FROM stock_utilisateur WHERE id_utilisateur = :id");
    $checkBeforeStmt->execute(['id' => $targetUserId]);
    $beforeCount = $checkBeforeStmt->fetch()['count'];
    error_log("🔐 [Admin Reset Password] Nombre d'utilisateurs avec cet ID avant UPDATE: {$beforeCount}");
    
    // Utiliser une transaction pour garantir l'intégrité
    $bdd->beginTransaction();
    
    try {
        $updateStmt = $bdd->prepare("UPDATE stock_utilisateur SET mot_de_passe = :password, date_modification = NOW() WHERE id_utilisateur = :id");
        $updateStmt->execute(['password' => $hashedPassword, 'id' => $targetUserId]);
        
        $rowsAffected = $updateStmt->rowCount();
        error_log("🔐 [Admin Reset Password] Lignes affectées par UPDATE: {$rowsAffected}");
        
        if ($rowsAffected === 0) {
            $bdd->rollBack();
            error_log("❌ [Admin Reset Password] Aucune ligne affectée - ID utilisateur invalide: {$targetUserId}");
            throw new Exception("Erreur lors de la mise à jour du mot de passe - utilisateur non trouvé");
        }
        
        // Vérifier que la mise à jour a bien fonctionné
        $verifyStmt = $bdd->prepare("SELECT id_utilisateur, email, username, mot_de_passe FROM stock_utilisateur WHERE id_utilisateur = :id");
        $verifyStmt->execute(['id' => $targetUserId]);
        $updated = $verifyStmt->fetch();
        
        if (!$updated) {
            $bdd->rollBack();
            error_log("❌ [Admin Reset Password] Utilisateur non trouvé après UPDATE - ID: {$targetUserId}");
            throw new Exception("Erreur lors de la mise à jour du mot de passe - utilisateur non trouvé");
        }
        
        error_log("🔐 [Admin Reset Password] Utilisateur trouvé après UPDATE - ID: {$updated['id_utilisateur']}, Email: {$updated['email']}");
        
        // Vérifier que le nouveau mot de passe fonctionne
        if (!password_verify($newPassword, $updated['mot_de_passe'])) {
            $bdd->rollBack();
            error_log("❌ [Admin Reset Password] Échec de vérification pour utilisateur ID: {$targetUserId}, Email: {$updated['email']}");
            error_log("❌ [Admin Reset Password] Hash stocké: " . substr($updated['mot_de_passe'], 0, 20) . "...");
            throw new Exception("Erreur lors de la mise à jour du mot de passe - vérification échouée");
        }
        
        // Commit de la transaction
        $bdd->commit();
        
        error_log("✅ [Admin Reset Password] Mot de passe mis à jour avec succès pour utilisateur ID: {$targetUserId}, Email: {$updated['email']}");
        error_log("✅ [Admin Reset Password] Vérification réussie - le nouveau mot de passe fonctionne");
        
    } catch (Exception $e) {
        $bdd->rollBack();
        error_log("❌ [Admin Reset Password] Erreur dans la transaction: " . $e->getMessage());
        throw $e;
    }

    return ['message' => 'Mot de passe réinitialisé avec succès'];
}

/**
 * Supprimer définitivement un utilisateur de la base de données
 * ATTENTION: Cette action est irréversible
 */
function deleteUser($bdd, $userId) {
    // Vérifier que l'utilisateur existe
    $user = getUserById($bdd, $userId);

    // Supprimer les accès liés AVANT de supprimer l'utilisateur
    $bdd->prepare("DELETE FROM stock_utilisateur_entrepot WHERE id_utilisateur = :id")->execute(['id' => $userId]);
    $bdd->prepare("DELETE FROM stock_utilisateur_point_vente WHERE id_utilisateur = :id")->execute(['id' => $userId]);

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
        $token = generateAuthToken($user);

        // Journaliser la connexion
        $journalData = [
            'user' => $user['email'] ?? ($user['nom'] ?? 'inconnu'),
            'action' => 'connexion',
            'details' => 'Connexion réussie le ' . date('d/m/Y à H:i:s')
        ];
        // Appel interne à l'API journal
        $journalUrl = __DIR__ . '/api_journal.php';
        if (file_exists($journalUrl)) {
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => json_encode($journalData)
                ]
            ];
            $context  = stream_context_create($opts);
            @file_get_contents($journalUrl, false, $context);
        }

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
                    
                    // Vérifier les limites du forfait avant de créer l'utilisateur
                    $enterpriseIdForCheck = $data['id_entreprise'] ?? $currentUser['enterprise_id'];
                    
                    // Si l'utilisateur essaie de créer un admin, vérifier si le forfait le permet
                    $userRole = strtolower($data['role'] ?? 'Agent');
                    if (($userRole === 'admin' || $userRole === 'superadmin') && function_exists('checkCanNommerAdmin')) {
                        if (!checkCanNommerAdmin($bdd, $enterpriseIdForCheck)) {
                            throw new Exception("Votre forfait ne permet pas de nommer un autre administrateur. Veuillez passer au forfait Enterprise.", 403);
                        }
                    }
                    
                    // Vérifier la limite d'utilisateurs (sauf pour les admins)
                    if ($userRole !== 'admin' && $userRole !== 'superadmin' && function_exists('checkUserLimit')) {
                        $limitCheck = checkUserLimit($bdd, $enterpriseIdForCheck);
                        if (!$limitCheck['allowed']) {
                            throw new Exception($limitCheck['message'], 403);
                        }
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
                } elseif ($action === 'admin_reset_password' && $id !== null) {
                    if (!isset($data['new_password'])) {
                        throw new Exception("Nouveau mot de passe requis");
                    }
                    $oldPassword = $data['old_password'] ?? null;
                    $resultat = adminResetUserPassword($bdd, $currentUser, $id, $data['new_password'], $oldPassword);
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
