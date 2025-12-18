<?php
// Activer la gestion des erreurs et définir les headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes CORS preflight AVANT toute logique serveur
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Inclure les fichiers nécessaires
include 'database.php';
include 'functions_entreprise.php';
include 'middleware_auth.php';
include 'config.php';


// Initialiser la connexion à la base de données
$database = new Database();
$bdd = $database->getPdo();

// Récupération des données de la requête
$rawBody = file_get_contents("php://input");
$data = json_decode($rawBody, true);
file_put_contents(__DIR__ . '/debug_post.txt', "RAW BODY:\n" . $rawBody . "\nPARSED:\n" . print_r($data, true));

// Récupérer les paramètres de requête
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? $_GET['id'] : null;

try {
    $bdd->beginTransaction(); // Démarrer une transaction

    // Route spéciale pour générer un token de test (GET?action=token&user_id=...)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'token' && isset($_GET['user_id'])) {
        $userId = (int)$_GET['user_id'];
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 3600 // 1h de validité
        ];
        $token = generateJWT($payload, JWT_SECRET);
        echo json_encode(['success' => true, 'token' => $token]);
        exit;
    }

    // Pour toutes les autres actions, authentification requise
    $currentUser = authenticateAndAuthorize($bdd);
    $currentUser['role'] = strtolower($currentUser['role']);

    // Traiter la requête en fonction de la méthode HTTP
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Récupération des entreprises
            if ($action === 'all') {
                // Récupérer toutes les entreprises (filtré par rôle d'utilisateur)
                $resultat = getAllEnterprises($bdd, $currentUser);
            } elseif ($action === 'single' && $id !== null) {
                // Récupérer une entreprise spécifique
                $enterprise = getEnterpriseById($bdd, $id);
                
                // Vérifier que l'utilisateur a accès à cette entreprise
                if ($currentUser['role'] !== 'admin' && $enterprise['entreprise_id'] != $currentUser['enterprise_id']) {
                    throw new Exception("Accès non autorisé à cette entreprise", 403);
                }
                
                $resultat = $enterprise;
            } elseif ($action === 'search' && isset($_GET['query'])) {
                // Rechercher des entreprises
                $resultat = searchEnterprises($bdd, $_GET['query'], $currentUser);
            } else {
                // Action non reconnue
                throw new Exception("Action non valide ou paramètres manquants");
            }
            break;

        case 'POST':
            // Création d'une nouvelle entreprise
            if ($action === 'create') {
                // Vérifier que les données nécessaires sont présentes
                if (!isset($data['nom_entreprise']) || empty($data['nom_entreprise'])) {
                    throw new Exception("Le nom de l'entreprise est requis");
                }
                
                // Vérifier que l'utilisateur a les droits pour créer une entreprise
                if ($currentUser['role'] !== 'admin') {
                    throw new Exception("Seuls les administrateurs peuvent créer des entreprises", 403);
                }
                
                $resultat = createEnterprise($bdd, $data);
            } else {
                // Action non reconnue
                throw new Exception("Action non valide pour la méthode POST");
            }
            break;

        case 'PUT':
            // Mise à jour d'une entreprise existante
            if ($action === 'update' && $id !== null) {
                // Permettre la mise à jour partielle : il faut au moins un champ modifiable
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
                // Vérifier que l'utilisateur a les droits pour modifier le statut
                if ($currentUser['role'] !== 'admin') {
                    throw new Exception("Seuls les administrateurs peuvent modifier le statut d'une entreprise", 403);
                }
                
                // Mettre à jour le statut d'une entreprise
                $resultat = updateEnterpriseStatus($bdd, $id, $data['statut']);
            } else {
                // Action non reconnue
                throw new Exception("Action non valide ou paramètres manquants pour la méthode PUT");
            }
            break;

        case 'DELETE':
            // Suppression d'une entreprise
            if ($action === 'delete' && $id !== null) {
                // Vérifier que l'utilisateur a les droits pour supprimer une entreprise
                if ($currentUser['role'] !== 'admin') {
                    throw new Exception("Seuls les administrateurs peuvent supprimer des entreprises", 403);
                }
                
                $resultat = deleteEnterprise($bdd, $id);
            } elseif ($action === 'bulk' && isset($data['ids']) && is_array($data['ids'])) {
                // Suppression en masse d'entreprises
                if ($currentUser['role'] !== 'admin') {
                    throw new Exception("Seuls les administrateurs peuvent supprimer des entreprises", 403);
                }
                
                $resultat = bulkDeleteEnterprises($bdd, $data['ids']);
            } else {
                // Action non reconnue
                throw new Exception("Action non valide ou paramètres manquants pour la méthode DELETE");
            }
            break;

        case 'OPTIONS':
            // Répondre aux requêtes OPTIONS (pour CORS)
            $resultat = ['success' => true, 'message' => 'CORS preflight request successful'];
            break;

        default:
            // Méthode HTTP non supportée
            throw new Exception("Méthode HTTP non supportée");
    }

    // Valider la transaction si tout s'est bien passé
    $bdd->commit();

    // Envoyer la réponse
    echo json_encode([
        'success' => true,
        'data' => $resultat
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($bdd->inTransaction()) {
        $bdd->rollBack();
    }

    // Déterminer le code HTTP approprié
    $httpCode = 400; // Par défaut
    if (strpos($e->getMessage(), "non autorisé") !== false) {
        $httpCode = 403; // Forbidden
    } elseif (strpos($e->getMessage(), "Authentification requise") !== false || strpos($e->getMessage(), "Token invalide") !== false) {
        $httpCode = 401; // Unauthorized
    } elseif (strpos($e->getMessage(), "non trouvé") !== false) {
        $httpCode = 404; // Not Found
    }

    // Envoyer une réponse d'erreur
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
