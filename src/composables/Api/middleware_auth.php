

<?php
require_once 'config.php';
/**
 * Middleware d'authentification et d'autorisation JWT (sans dépendance externe)
 */


function generateJWT($payload, $secret) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $base64UrlPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
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
    $header = base64_decode(strtr($parts[0], '-_', '+/'));
    $payload = base64_decode(strtr($parts[1], '-_', '+/'));
    $signatureProvided = $parts[2];
    $validSignature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$parts[0].$parts[1]", $secret, true)), '+/', '-_'), '=');
    if (!hash_equals($validSignature, $signatureProvided)) return false;
    $data = json_decode($payload, true);
    return $data ?: false;
}
