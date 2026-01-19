<?php
/**
 * API Point de Vente - Gestion des points de vente
 * Endpoint: /api-stock/api_point_vente.php
 */

// Activer la gestion des erreurs et définir les headers CORS AVANT TOUT
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');

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
$dbFile = __DIR__ . '/config/database.php';
if (!file_exists($dbFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier config/database.php introuvable sur le serveur',
        'error' => 'Le fichier doit être déployé dans: /api-stock/config/database.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $dbFile;

if (!function_exists('createDatabaseConnection')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fonction createDatabaseConnection() introuvable',
        'error' => 'Vérifiez que le fichier config/database.php contient cette fonction'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
// MIDDLEWARE AUTHENTIFICATION
// =====================================================
$middlewareFile = __DIR__ . '/functions/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    $middlewareFile = __DIR__ . '/middleware_auth.php';
}

if (!file_exists($middlewareFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier middleware_auth.php introuvable',
        'error' => 'Le fichier doit être déployé'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $middlewareFile;

// Inclure check_forfait_limits pour vérifier les limites des forfaits
$checkLimitsFile = __DIR__ . '/check_forfait_limits.php';
if (file_exists($checkLimitsFile)) {
    require_once $checkLimitsFile;
}

if (!function_exists('authenticateAndAuthorize')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fonction authenticateAndAuthorize() introuvable',
        'error' => 'Vérifiez que le fichier middleware_auth.php contient cette fonction'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Authentifier l'utilisateur
try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = $currentUser['enterprise_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================
// GESTION DES REQUÊTES
// =====================================================
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'all') {
                // Filtrage selon les droits d'accès de l'utilisateur
                $isAdmin = isset($currentUser['user_role']) && in_array(strtolower($currentUser['user_role']), ['admin', 'superadmin']);
                $params = [];
                $sql = "
                    SELECT 
                        pv.*,
                        e.nom_entrepot,
                        COUNT(DISTINCT v.id_vente) AS nombre_ventes,
                        COUNT(DISTINCT CASE WHEN v.type_vente = 'vente' AND DATE(v.date_vente) = CURDATE() THEN v.id_vente END) AS ventes_journalieres,
                        COUNT(DISTINCT CASE WHEN v.type_vente = 'retour' THEN v.id_vente END) AS nombre_retours,
                        COUNT(DISTINCT CASE WHEN v.type_vente = 'commande' AND v.statut = 'en_attente' THEN v.id_vente END) AS commandes_en_attente,
                        COUNT(DISTINCT CASE WHEN v.type_vente = 'livraison' AND v.statut = 'en_attente' THEN v.id_vente END) AS a_livrer,
                        COUNT(DISTINCT CASE WHEN v.type_vente = 'expedition' AND v.statut = 'en_attente' THEN v.id_vente END) AS a_expedier,
                        COALESCE(SUM(CASE WHEN v.type_vente = 'vente' AND DATE(v.date_vente) = CURDATE() THEN v.montant_total ELSE 0 END), 0) AS chiffre_affaires_journalier
                    FROM stock_point_vente pv
                    LEFT JOIN stock_entrepot e ON pv.id_entrepot = e.id_entrepot
                    LEFT JOIN stock_vente v ON v.id_point_vente = pv.id_point_vente AND v.id_entreprise = pv.id_entreprise
                    WHERE pv.id_entreprise = ?
                ";
                $params[] = $enterpriseId;
                if (!$isAdmin && isset($currentUser['permissions_points_vente']) && is_array($currentUser['permissions_points_vente']) && count($currentUser['permissions_points_vente']) > 0) {
                    $idsAutorises = array_map('intval', $currentUser['permissions_points_vente']);
                    $in = implode(',', array_fill(0, count($idsAutorises), '?'));
                    $sql .= " AND pv.id_point_vente IN ($in) ";
                    foreach ($idsAutorises as $id) {
                        $params[] = $id;
                    }
                }
                $sql .= " GROUP BY pv.id_point_vente ORDER BY pv.date_creation DESC ";
                $stmt = $bdd->prepare($sql);
                $stmt->execute($params);
                $pointsVente = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'success' => true,
                    'data' => $pointsVente
                ], JSON_UNESCAPED_UNICODE);

            } elseif ($action === 'stats' && isset($_GET['id_point_vente'])) {
                // Récupérer les statistiques détaillées d'un point de vente
                $idPointVente = (int)$_GET['id_point_vente'];
                
                // Vérifier que le point de vente appartient à l'entreprise
                $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE id_point_vente = :id AND id_entreprise = :id_entreprise");
                $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
                if (!$stmt->fetch()) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Point de vente non trouvé'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // Statistiques détaillées
                $stats = [];
                
                // Ventes journalières (7 derniers jours)
                $stmt = $bdd->prepare("
                    SELECT 
                        DATE(date_vente) AS date,
                        COUNT(*) AS nombre_ventes,
                        SUM(montant_total) AS chiffre_affaires
                    FROM stock_vente
                    WHERE id_point_vente = :id 
                    AND id_entreprise = :id_entreprise
                    AND type_vente = 'vente'
                    AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(date_vente)
                    ORDER BY date DESC
                ");
                $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
                $stats['ventes_journalieres'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Retours
                $stmt = $bdd->prepare("
                    SELECT COUNT(*) AS total
                    FROM stock_vente
                    WHERE id_point_vente = :id 
                    AND id_entreprise = :id_entreprise
                    AND type_vente = 'retour'
                ");
                $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
                $stats['retours'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                
                // Articles à livrer
                $stmt = $bdd->prepare("
                    SELECT COUNT(*) AS total
                    FROM stock_vente
                    WHERE id_point_vente = :id 
                    AND id_entreprise = :id_entreprise
                    AND type_vente = 'livraison'
                    AND statut = 'en_attente'
                ");
                $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
                $stats['a_livrer'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                
                // Articles à expédier
                $stmt = $bdd->prepare("
                    SELECT COUNT(*) AS total
                    FROM stock_vente
                    WHERE id_point_vente = :id 
                    AND id_entreprise = :id_entreprise
                    AND type_vente = 'expedition'
                    AND statut = 'en_attente'
                ");
                $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
                $stats['a_expedier'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                
                // Commandes en attente
                $stmt = $bdd->prepare("
                    SELECT COUNT(*) AS total
                    FROM stock_vente
                    WHERE id_point_vente = :id 
                    AND id_entreprise = :id_entreprise
                    AND type_vente = 'commande'
                    AND statut = 'en_attente'
                ");
                $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
                $stats['commandes_en_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                
                echo json_encode([
                    'success' => true,
                    'data' => $stats
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif (isset($_GET['id_point_vente'])) {
                // Récupérer un point de vente spécifique
                $idPointVente = (int)$_GET['id_point_vente'];
                $stmt = $bdd->prepare("
                    SELECT 
                        pv.*,
                        e.nom_entrepot
                    FROM stock_point_vente pv
                    LEFT JOIN stock_entrepot e ON pv.id_entrepot = e.id_entrepot
                    WHERE pv.id_point_vente = :id AND pv.id_entreprise = :id_entreprise
                ");
                $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
                $pointVente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$pointVente) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Point de vente non trouvé'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $pointVente
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // Créer un nouveau point de vente
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['nom_point_vente'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Le nom du point de vente est requis'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier si le nom existe déjà pour cette entreprise
            $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE nom_point_vente = :nom AND id_entreprise = :id_entreprise");
            $stmt->execute(['nom' => $data['nom_point_vente'], 'id_entreprise' => $enterpriseId]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Un point de vente avec ce nom existe déjà'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier les limites du forfait avant de créer le point de vente
            if (function_exists('checkPointVenteLimit')) {
                $limitCheck = checkPointVenteLimit($bdd, $enterpriseId);
                if (!$limitCheck['allowed']) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'message' => $limitCheck['message'],
                        'limit_info' => [
                            'current' => $limitCheck['current'],
                            'max' => $limitCheck['max']
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            
            $stmt = $bdd->prepare("
                INSERT INTO stock_point_vente (
                    nom_point_vente, id_entrepot, adresse, ville, pays, 
                    telephone, email, responsable, id_entreprise, actif
                ) VALUES (
                    :nom_point_vente, :id_entrepot, :adresse, :ville, :pays,
                    :telephone, :email, :responsable, :id_entreprise, :actif
                )
            ");
            
            $stmt->execute([
                'nom_point_vente' => $data['nom_point_vente'],
                'id_entrepot' => !empty($data['id_entrepot']) ? (int)$data['id_entrepot'] : null,
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'email' => $data['email'] ?? null,
                'responsable' => $data['responsable'] ?? null,
                'id_entreprise' => $enterpriseId,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1
            ]);
            
            $idPointVente = $bdd->lastInsertId();
            // Journaliser la création du point de vente avec le nom
            $userName = $currentUser['nom'] ?? ($currentUser['email'] ?? 'Utilisateur');
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), ?, ?, ?, ?)');
            $journalStmt->execute([
                $userName,
                'Création point de vente',
                'Point de vente : ' . ($data['nom_point_vente'] ?? ''),
                $enterpriseId
            ]);
            echo json_encode([
                'success' => true,
                'message' => 'Point de vente créé avec succès',
                'data' => ['id_point_vente' => $idPointVente]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'PUT':
            // Mettre à jour un point de vente
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id_point_vente'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID point de vente requis'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $idPointVente = (int)$data['id_point_vente'];
            
            // Vérifier que le point de vente appartient à l'entreprise
            $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE id_point_vente = :id AND id_entreprise = :id_entreprise");
            $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Point de vente non trouvé'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier si le nouveau nom existe déjà (si changé)
            if (!empty($data['nom_point_vente'])) {
                $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE nom_point_vente = :nom AND id_entreprise = :id_entreprise AND id_point_vente != :id");
                $stmt->execute(['nom' => $data['nom_point_vente'], 'id_entreprise' => $enterpriseId, 'id' => $idPointVente]);
                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Un point de vente avec ce nom existe déjà'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            
            $stmt = $bdd->prepare("
                UPDATE stock_point_vente SET
                    nom_point_vente = COALESCE(:nom_point_vente, nom_point_vente),
                    id_entrepot = :id_entrepot,
                    adresse = :adresse,
                    ville = :ville,
                    pays = :pays,
                    telephone = :telephone,
                    email = :email,
                    responsable = :responsable,
                    actif = :actif
                WHERE id_point_vente = :id AND id_entreprise = :id_entreprise
            ");
            
            $stmt->execute([
                'nom_point_vente' => $data['nom_point_vente'] ?? null,
                'id_entrepot' => !empty($data['id_entrepot']) ? (int)$data['id_entrepot'] : null,
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'email' => $data['email'] ?? null,
                'responsable' => $data['responsable'] ?? null,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1,
                'id' => $idPointVente,
                'id_entreprise' => $enterpriseId
            ]);
            
            // Journaliser la modification du point de vente avec le nom
            $userName = $currentUser['nom'] ?? ($currentUser['email'] ?? 'Utilisateur');
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), ?, ?, ?, ?)');
            $journalStmt->execute([
                $userName,
                'Modification point de vente',
                'Point de vente : ' . ($data['nom_point_vente'] ?? ''),
                $enterpriseId
            ]);
            echo json_encode([
                'success' => true,
                'message' => 'Point de vente mis à jour avec succès'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'DELETE':
            // Supprimer un point de vente
            $idPointVente = (int)($_GET['id_point_vente'] ?? 0);
            
            if (!$idPointVente) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID point de vente requis'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier que le point de vente appartient à l'entreprise
            $stmt = $bdd->prepare("SELECT nom_point_vente FROM stock_point_vente WHERE id_point_vente = :id AND id_entreprise = :id_entreprise");
            $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
            $pointVente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pointVente) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Point de vente non trouvé'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier s'il y a des ventes liées à ce point de vente
            $stmt = $bdd->prepare("SELECT COUNT(*) as count FROM stock_vente WHERE id_point_vente = :id AND id_entreprise = :id_entreprise");
            $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Impossible de supprimer le point de vente : il contient des ventes enregistrées.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $bdd->prepare("DELETE FROM stock_point_vente WHERE id_point_vente = :id AND id_entreprise = :id_entreprise");
            $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
            
            // Journaliser la suppression du point de vente avec le nom
            $userName = $currentUser['nom'] ?? ($currentUser['email'] ?? 'Utilisateur');
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), ?, ?, ?, ?)');
            $journalStmt->execute([
                $userName,
                'Suppression point de vente',
                'Point de vente : ' . ($pointVente['nom_point_vente'] ?? ''),
                $enterpriseId
            ]);
            echo json_encode([
                'success' => true,
                'message' => 'Point de vente supprimé avec succès'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Méthode non autorisée'
            ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}







