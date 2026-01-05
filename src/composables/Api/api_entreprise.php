<?php
// --- CORS headers ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

// Gérer les requêtes CORS preflight AVANT toute logique serveur
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Includes & Connexion BDD ---
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/functions_entreprise.php';
require_once __DIR__ . '/functions/middleware_auth.php';
//require_once __DIR__ . '/../../../../vendor/autoload.php'; // si JWT ou autres dépendances

// --- Initialisation variables ---
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$data = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    // Connexion BDD
    $bdd = createDatabaseConnection();
    $bdd->beginTransaction();
    $resultat = null;

    // Route spéciale pour générer un token de test (GET?action=token&user_id=...)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'token' && isset($_GET['user_id'])) {
        $userId = (int)$_GET['user_id'];
        $stmt = $bdd->prepare("SELECT u.id_utilisateur, u.prenom, u.email, u.role, u.id_entreprise FROM stock_utilisateur u WHERE u.id_utilisateur = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new Exception("Utilisateur non trouvé");
        }
        $userData = [
            'user_id' => (int)$user['id_utilisateur'],
            'user_first_name' => $user['prenom'] ?? '',
            'user_email' => $user['email'],
            'user_role' => strtolower($user['role']),
            'user_enterprise_id' => (int)$user['id_entreprise']
        ];
        $token = generateJWT($userData, JWT_SECRET);
        echo json_encode(['success' => true, 'token' => $token]);
        exit;
    }

    // Endpoint public : GET?action=single&id=...
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'single' && $id !== null) {
        $enterprise = getEnterpriseById($bdd, $id);
        $resultat = $enterprise;
    } else {
        // Authentification requise pour toutes les autres actions
        $currentUser = authenticateAndAuthorize($bdd);
        $currentUser['role'] = strtolower($currentUser['role']);

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if ($action === 'all') {
                    $resultat = getAllEnterprises($bdd, $currentUser);
                } elseif ($action === 'search' && isset($_GET['query'])) {
                    $resultat = searchEnterprises($bdd, $_GET['query'], $currentUser);
                } else {
                    throw new Exception("Action non valide ou paramètres manquants");
                }
                break;
            case 'POST':
                if ($action === 'create') {
                    if (!isset($data['nom_entreprise']) || empty($data['nom_entreprise'])) {
                        throw new Exception("Le nom de l'entreprise est requis");
                    }
                    if ($currentUser['role'] !== 'admin') {
                        throw new Exception("Seuls les administrateurs peuvent créer des entreprises", 403);
                    }
                    $resultat = createEnterprise($bdd, $data);
                } else {
                    throw new Exception("Action non valide pour la méthode POST");
                }
                break;
            case 'PUT':
                if ($action === 'update' && $id !== null) {
                    $modifiableFields = [
                        'nom_entreprise', 'slug', 'sigle', 'telephone', 'email', 'adresse', 'ville', 'pays', 'code_postal', 'logo', 'registre_commerce', 'ncc', 'devise', 'site_web', 'fax', 'capital_social', 'forme_juridique', 'numero_tva', 'date_abonnement', 'date_expiration_abonnement', 'statut'
                    ];
                    $hasField = false;
                    foreach ($modifiableFields as $field) {
                        if (isset($data[$field])) {
                            $hasField = true;
                            break;
                        }
                    }
                    if (!$hasField) {
                        throw new Exception("Aucune donnée à mettre à jour");
                    }
                    $resultat = updateEnterprise($bdd, $id, $data);
                } elseif ($action === 'status' && $id !== null && isset($data['statut'])) {
                    if ($currentUser['role'] !== 'admin') {
                        throw new Exception("Seuls les administrateurs peuvent modifier le statut d'une entreprise", 403);
                    }
                    $resultat = updateEnterpriseStatus($bdd, $id, $data['statut']);
                } else {
                    throw new Exception("Action non valide ou paramètres manquants pour la méthode PUT");
                }
                break;
            case 'DELETE':
                if ($action === 'delete' && $id !== null) {
                    if ($currentUser['role'] !== 'admin') {
                        throw new Exception("Seuls les administrateurs peuvent supprimer des entreprises", 403);
                    }
                    $resultat = deleteEnterprise($bdd, $id);
                } elseif ($action === 'bulk' && isset($data['ids']) && is_array($data['ids'])) {
                    if ($currentUser['role'] !== 'admin') {
                        throw new Exception("Seuls les administrateurs peuvent supprimer des entreprises", 403);
                    }
                    $resultat = bulkDeleteEnterprises($bdd, $data['ids']);
                } else {
                    throw new Exception("Action non valide ou paramètres manquants pour la méthode DELETE");
                }
                break;
            case 'OPTIONS':
                $resultat = ['success' => true, 'message' => 'CORS preflight request successful'];
                break;
            default:
                throw new Exception("Méthode HTTP non supportée");
        }
    }

    $bdd->commit();
    echo json_encode([
        'success' => true,
        'data' => $resultat
    ]);

} catch (Exception $e) {
    if (isset($bdd) && $bdd->inTransaction()) {
        $bdd->rollBack();
    }
    $httpCode = 400;
    if (strpos($e->getMessage(), "non autorisé") !== false) {
        $httpCode = 403;
    } elseif (strpos($e->getMessage(), "Authentification requise") !== false || strpos($e->getMessage(), "Token invalide") !== false) {
        $httpCode = 401;
    } elseif (strpos($e->getMessage(), "non trouvé") !== false) {
        $httpCode = 404;
    }
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

