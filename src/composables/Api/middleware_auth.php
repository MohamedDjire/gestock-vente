

<?php
require_once __DIR__ . '/../config.php';
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
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        throw new Exception("Authentification requise", 401);
    }
    $token = $matches[1];
    $payload = verifyJWT($token, JWT_SECRET);
    if (!$payload) {
        throw new Exception("Token invalide ou expiré", 401);
    }
    if (isset($payload['exp']) && $payload['exp'] < time()) {
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
        'entreprise_sigle' => $user['sigle']
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
