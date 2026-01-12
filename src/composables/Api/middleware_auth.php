

<?php
// Charger config.php (JWT_SECRET et JWT_EXPIRATION)
// Sur le serveur: config.php est à la racine de l'API
// En local: config.php est dans le même dossier
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/config.php';
}
require_once $configPath;
/**
 * Middleware d'authentification et d'autorisation JWT (sans dépendance externe)
 */


function generateJWT($userData, $secret) {
    // Créer l'en-tête
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    // Créer la charge utile (payload)
    $payload = [
        'sub' => $userData['user_id'],                    // ID de l'utilisateur
        'name' => $userData['user_first_name'],          // Nom de l'utilisateur
        'email' => $userData['user_email'],              // Email de l'utilisateur
        'role' => $userData['user_role'],                // Rôle de l'utilisateur
        'enterprise_id' => $userData['user_enterprise_id'], // ID de l'entreprise
        'iat' => time(),                                 // Émis à (timestamp)
        'exp' => time() + JWT_EXPIRATION                 // Expiration (timestamp)
    ];
    
    // Encoder l'en-tête et la charge utile en Base64Url
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
    // Créer la signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    // Créer le token
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    
    return $jwt;
}

function authenticateAndAuthorize($bdd, $enterpriseId = null) {
    // Récupérer le header Authorization de manière compatible avec tous les serveurs
    $authHeader = '';
    
    // Méthode 1: getallheaders() (fonctionne sur Apache)
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                     (isset($headers['authorization']) ? $headers['authorization'] : '');
        // Vérifier aussi X-Auth-Token dans getallheaders (priorité si Authorization est vide)
        if (empty($authHeader) && isset($headers['X-Auth-Token'])) {
            $authHeader = 'Bearer ' . $headers['X-Auth-Token'];
        }
    }
    
    // Méthode 2: $_SERVER (fonctionne sur tous les serveurs)
    if (empty($authHeader)) {
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : 
                     (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '');
    }
    
    // Méthode 2b: Header personnalisé X-Auth-Token depuis $_SERVER (fallback si Authorization est filtré)
    if (empty($authHeader) && isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $authHeader = 'Bearer ' . $_SERVER['HTTP_X_AUTH_TOKEN'];
    }
    
    // Méthode 3: apache_request_headers() si disponible
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                     (isset($headers['authorization']) ? $headers['authorization'] : '');
        // Vérifier aussi X-Auth-Token dans apache_request_headers
        if (empty($authHeader) && isset($headers['X-Auth-Token'])) {
            $authHeader = 'Bearer ' . $headers['X-Auth-Token'];
        }
    }
    
    // Vérifier le format du token (insensible à la casse, gère les espaces multiples)
    if (empty($authHeader)) {
        // Log pour débogage (désactiver en production)
        error_log("AUTH DEBUG: Aucun header Authorization trouvé. Headers disponibles: " . json_encode(array_keys($_SERVER)));
        throw new Exception("Authentification requise", 401);
    }
    
    // Extraire le token (Bearer peut être en minuscules ou majuscules)
    if (!preg_match('/Bearer\s+(\S+)/i', trim($authHeader), $matches)) {
        error_log("AUTH DEBUG: Format du header invalide. Header reçu: " . substr($authHeader, 0, 50));
        throw new Exception("Format d'authentification invalide. Format attendu: Bearer <token>", 401);
    }
    
    $token = $matches[1];
    
    // Vérifier que le token n'est pas vide
    if (empty($token)) {
        throw new Exception("Token vide", 401);
    }
    
    // Vérifier le JWT
    $payload = verifyJWT($token, JWT_SECRET);
    if (!$payload) {
        error_log("AUTH DEBUG: Token invalide ou signature incorrecte");
        throw new Exception("Token invalide ou expiré", 401);
    }
    
    // Vérifier l'expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        error_log("AUTH DEBUG: Token expiré. Exp: " . $payload['exp'] . ", Now: " . time());
        throw new Exception("Token expiré", 401);
    }

    // Récupérer l'utilisateur et l'entreprise associée
    $query = "SELECT 
        u.id_utilisateur,
        u.id_entreprise,
        u.nom,
        u.prenom,
        u.username,
        u.email,
        u.role,
        u.statut,
        e.nom_entreprise,
        e.sigle,
        e.statut AS entreprise_statut
    FROM stock_utilisateur u
    INNER JOIN stock_entreprise e ON u.id_entreprise = e.id_entreprise
    WHERE u.id_utilisateur = :id AND u.statut = 'actif'";
    $stmt = $bdd->prepare($query);
    $stmt->bindParam(':id', $payload['sub'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception("Utilisateur non trouvé ou inactif", 401);
    }
    if ($user['entreprise_statut'] !== 'actif') {
        throw new Exception("L'entreprise associée à ce compte est inactive", 403);
    }
    // Vérification des droits d'accès à l'entreprise
    if ($enterpriseId !== null && $user['id_entreprise'] != $enterpriseId) {
        $role = strtolower($user['role']);
        if ($role !== 'admin' && $role !== 'superadmin') {
            throw new Exception("Accès non autorisé à cette entreprise", 403);
        }
    }
    // Charger les permissions d'accès (entrepôts et points de vente)
    $stmtE = $bdd->prepare("SELECT id_entrepot FROM stock_utilisateur_entrepot WHERE id_utilisateur = :id");
    $stmtE->execute(['id' => $user['id_utilisateur']]);
    $permissions_entrepots = $stmtE->fetchAll(PDO::FETCH_COLUMN);
    $stmtPV = $bdd->prepare("SELECT id_point_vente FROM stock_utilisateur_point_vente WHERE id_utilisateur = :id");
    $stmtPV->execute(['id' => $user['id_utilisateur']]);
    $permissions_points_vente = $stmtPV->fetchAll(PDO::FETCH_COLUMN);

    // Retourne un tableau structuré cohérent avec la page user
    return [
        'id' => $user['id_utilisateur'],
        'user_id' => $user['id_utilisateur'],
        'enterprise_id' => $user['id_entreprise'],
        'user_enterprise_id' => $user['id_entreprise'],
        'user_role' => strtolower($user['role']),
        'role' => $user['role'],
        'username' => $user['username'],
        'email' => $user['email'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'statut' => $user['statut'],
        'entreprise_nom' => $user['nom_entreprise'],
        'entreprise_sigle' => $user['sigle'],
        'permissions_entrepots' => $permissions_entrepots,
        'permissions_points_vente' => $permissions_points_vente
    ];
}

function verifyJWT($token, $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    
    // Reconstruire le header et payload pour vérifier la signature
    // (on utilise les parties originales du token)
    $base64UrlHeader = $parts[0];
    $base64UrlPayload = $parts[1];
    $signatureProvided = $parts[2];
    
    // Vérifier la signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if (!hash_equals($base64UrlSignature, $signatureProvided)) return false;
    
    // Décoder le payload (ajouter le padding si nécessaire pour base64_decode)
    $payload = str_replace(['-', '_'], ['+', '/'], $base64UrlPayload);
    // Ajouter le padding manquant
    $padding = strlen($payload) % 4;
    if ($padding) {
        $payload .= str_repeat('=', 4 - $padding);
    }
    $decodedPayload = base64_decode($payload);
    $data = json_decode($decodedPayload, true);
    
    return $data ?: false;
}

/**
 * Vérifie si l'entreprise a un forfait actif
 * Cette fonction doit être appelée pour toutes les actions sauf la connexion
 * Retourne true si actif, false si pas d'abonnement, et lance une exception si expiré
 */
function checkForfaitActif($bdd, $enterpriseId) {
    try {
        // Vérifier d'abord si la table existe
        $stmt = $bdd->query("SHOW TABLES LIKE 'stock_abonnement'");
        $tableExists = $stmt->fetch();
        
        // Si la table n'existe pas encore, on autorise l'accès (migration en cours)
        if (!$tableExists) {
            return true;
        }
        
        // Vérifier s'il y a un abonnement actif
        $stmt = $bdd->prepare("
            SELECT id_abonnement, date_fin, statut 
            FROM stock_abonnement 
            WHERE id_entreprise = :id_entreprise 
            AND statut = 'actif'
            ORDER BY date_fin DESC 
            LIMIT 1
        ");
        $stmt->execute(['id_entreprise' => $enterpriseId]);
        $abonnement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si aucun abonnement, on autorise l'accès (première utilisation)
        if (!$abonnement) {
            return true;
        }
        
        // Vérifier si l'abonnement est expiré
        $dateFin = new DateTime($abonnement['date_fin']);
        $now = new DateTime();
        
        if ($dateFin < $now) {
            // Mettre à jour le statut
            $updateStmt = $bdd->prepare("
                UPDATE stock_abonnement 
                SET statut = 'expire' 
                WHERE id_abonnement = :id_abonnement
            ");
            $updateStmt->execute(['id_abonnement' => $abonnement['id_abonnement']]);
            
            throw new Exception("Votre forfait a expiré. Veuillez renouveler votre abonnement pour continuer.", 403);
        }
        
        return true;
    } catch (Exception $e) {
        // Si c'est une exception de forfait expiré, la relancer
        if (strpos($e->getMessage(), 'forfait') !== false || strpos($e->getMessage(), 'abonnement') !== false) {
            throw $e;
        }
        // Sinon, en cas d'erreur SQL (table n'existe pas, etc.), autoriser l'accès
        return true;
    }
}
