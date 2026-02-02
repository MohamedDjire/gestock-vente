<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost:8080',
    'https://aliadjame.com'
];
if (in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
/**
 * API Forfait - Gestion des forfaits et abonnements
 * Endpoint: /api-stock/api_forfait.php
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'] ?? '';

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
// Note: Pour l'action 'status', on peut permettre l'accès sans authentification stricte
// car c'est juste une vérification de statut
$currentUser = null;
$enterpriseId = null;

try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = $currentUser['enterprise_id'];
} catch (Exception $e) {
    // Si l'authentification échoue, on peut quand même répondre pour certaines actions
    // mais avec un statut "non autorisé" ou "pas d'abonnement"
    if ($action === 'status' || $action === 'check') {
        // Pour les actions de vérification, retourner un statut "pas d'abonnement"
        echo json_encode([
            'success' => true,
            'data' => [
                'actif' => false,
                'date_fin' => null,
                'forfait' => null,
                'expire' => true,
                'no_subscription' => true
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Pour les autres actions, exiger l'authentification
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

/**
 * Vérifie et met à jour le statut d'un abonnement
 */
function checkAndUpdateAbonnement($bdd, $enterpriseId) {
    $stmt = $bdd->prepare("
        SELECT id_abonnement, id_forfait, date_fin, statut 
        FROM stock_abonnement 
        WHERE id_entreprise = :id_entreprise 
        AND statut = 'actif'
        ORDER BY date_fin DESC 
        LIMIT 1
    ");
    $stmt->execute(['id_entreprise' => $enterpriseId]);
    $abonnement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($abonnement) {
        $dateFin = new DateTime($abonnement['date_fin']);
        $now = new DateTime();
        
        // Si la date d'expiration est passée, mettre à jour le statut
        if ($dateFin < $now && $abonnement['statut'] === 'actif') {
            $updateStmt = $bdd->prepare("
                UPDATE stock_abonnement 
                SET statut = 'expire' 
                WHERE id_abonnement = :id_abonnement
            ");
            $updateStmt->execute(['id_abonnement' => $abonnement['id_abonnement']]);
            return null; // Abonnement expiré
        }
        
        return $abonnement;
    }
    
    return null; // Aucun abonnement actif
}

/**
 * Vérifie si l'entreprise a un abonnement actif
 */
function hasActiveAbonnement($bdd, $enterpriseId) {
    $abonnement = checkAndUpdateAbonnement($bdd, $enterpriseId);
    return $abonnement !== null;
}

// =====================================================
// GESTION DES REQUÊTES
// =====================================================
$method = $_SERVER['REQUEST_METHOD'];


try {
    switch ($method) {
        case 'GET':
            if ($action === 'status') {
                // Utiliser la nouvelle fonction getForfaitStatus pour obtenir l'état détaillé
                if (function_exists('getForfaitStatus')) {
                    $status = getForfaitStatus($bdd, $enterpriseId);
                    
                    // Récupérer les détails de l'abonnement et du forfait
                    $stmt = $bdd->prepare("
                        SELECT a.*, f.nom_forfait, f.prix, f.duree_jours, f.description
                        FROM stock_abonnement a
                        LEFT JOIN stock_forfait f ON a.id_forfait = f.id_forfait
                        WHERE a.id_entreprise = :id_entreprise
                        ORDER BY a.date_fin DESC
                        LIMIT 1
                    ");
                    $stmt->execute(['id_entreprise' => $enterpriseId]);
                    $abonnement = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$abonnement) {
                        echo json_encode([
                            'success' => true,
                            'data' => [
                                'etat' => 'no_subscription',
                                'actif' => false,
                                'date_fin' => null,
                                'forfait' => null,
                                'expire' => true,
                                'no_subscription' => true,
                                'jours_restants' => null,
                                'message' => 'Aucun forfait actif'
                            ]
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    
                    // Calculer la date de fin de période de grâce
                    $dateFin = new DateTime($abonnement['date_fin']);
                    $now = new DateTime();
                    $dateGraceFin = clone $dateFin;
                    $dateGraceFin->modify('+2 days');
                    
                    $responseData = [
                        'id_abonnement' => $abonnement['id_abonnement'],
                        'id_forfait' => $abonnement['id_forfait'],
                        'nom' => $abonnement['nom_forfait'],
                        'prix' => $abonnement['prix'],
                        'duree_jours' => $abonnement['duree_jours'],
                        'description' => $abonnement['description'] ?? '',
                        'date_debut' => isset($abonnement['date_debut']) ? $abonnement['date_debut'] : null,
                        'date_fin' => isset($abonnement['date_fin']) ? $abonnement['date_fin'] : null,
                        'statut' => $abonnement['statut'],
                        'etat' => $status['etat'],
                        'actif' => $status['actif'],
                        'jours_restants' => $status['jours_restants'] ?? 0,
                        'jours_grace_restants' => $status['jours_grace_restants'] ?? null,
                        'message' => $status['message'],
                        'expire' => $status['etat'] === 'bloque' || $status['etat'] === 'grace',
                        'no_subscription' => $status['etat'] === 'no_subscription',
                        'en_grace' => $status['etat'] === 'grace',
                        'bloque' => $status['etat'] === 'bloque',
                        'warning' => $status['etat'] === 'warning'
                    ];
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $responseData
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // Fallback vers l'ancien système si la fonction n'existe pas
                $abonnement = checkAndUpdateAbonnement($bdd, $enterpriseId);
                
                if (!$abonnement) {
                    $stmt = $bdd->prepare("
                        SELECT a.*, f.nom_forfait, f.prix, f.duree_jours
                        FROM stock_abonnement a
                        LEFT JOIN stock_forfait f ON a.id_forfait = f.id_forfait
                        WHERE a.id_entreprise = :id_entreprise
                        ORDER BY a.date_fin DESC
                        LIMIT 1
                    ");
                    $stmt->execute(['id_entreprise' => $enterpriseId]);
                    $lastAbonnement = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$lastAbonnement) {
                        echo json_encode([
                            'success' => true,
                            'data' => [
                                'actif' => false,
                                'date_fin' => null,
                                'forfait' => null,
                                'expire' => true,
                                'no_subscription' => true
                            ]
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'actif' => false,
                            'date_fin' => $lastAbonnement['date_fin'] ?? null,
                            'forfait' => $lastAbonnement ? [
                                'nom' => $lastAbonnement['nom_forfait'],
                                'prix' => $lastAbonnement['prix']
                            ] : null,
                            'expire' => true,
                            'no_subscription' => false
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                $stmt = $bdd->prepare("
                    SELECT nom_forfait, prix, duree_jours, description
                    FROM stock_forfait
                    WHERE id_forfait = :id_forfait
                ");
                $stmt->execute(['id_forfait' => $abonnement['id_forfait']]);
                $forfait = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $dateFin = new DateTime($abonnement['date_fin']);
                $now = new DateTime();
                $joursRestants = $now->diff($dateFin)->days;
                if ($dateFin < $now) {
                    $joursRestants = 0;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id_abonnement' => $abonnement['id_abonnement'],
                        'id_forfait' => $abonnement['id_forfait'],
                        'nom' => $forfait['nom_forfait'],
                        'prix' => $forfait['prix'],
                        'duree_jours' => $forfait['duree_jours'],
                        'description' => $forfait['description'] ?? '',
                        'date_debut' => isset($abonnement['date_debut']) ? $abonnement['date_debut'] : null,
                        'date_fin' => isset($abonnement['date_fin']) ? $abonnement['date_fin'] : null,
                        'statut' => $abonnement['statut'],
                        'actif' => true,
                        'jours_restants' => $joursRestants,
                        'expire' => false
                    ]
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif ($action === 'forfaits') {
                // Récupérer tous les forfaits disponibles
                $stmt = $bdd->prepare("
                    SELECT * FROM stock_forfait 
                    WHERE actif = 1 
                    ORDER BY prix ASC
                ");
                $stmt->execute();
                $forfaits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $forfaits
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif ($action === 'check') {
                // Vérification rapide si l'abonnement est actif (pour les vérifications périodiques)
                $isActive = hasActiveAbonnement($bdd, $enterpriseId);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'actif' => $isActive
                    ]
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // Souscrire à un forfait
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id_forfait'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID forfait requis'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Récupérer les informations du forfait
            $stmt = $bdd->prepare("SELECT * FROM stock_forfait WHERE id_forfait = :id AND actif = 1");
            $stmt->execute(['id' => (int)$data['id_forfait']]);
            $forfait = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$forfait) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Forfait non trouvé'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $isRenouvellement = !empty($data['renouvellement']) && ($data['renouvellement'] === true || $data['renouvellement'] === '1' || $data['renouvellement'] === 1);
            if ($isRenouvellement && $enterpriseId) {
                // Renouvellement : ajouter la durée au temps restant si abonnement actif au même forfait
                $stmtAb = $bdd->prepare("
                    SELECT id_abonnement, date_fin FROM stock_abonnement
                    WHERE id_entreprise = :id_entreprise AND id_forfait = :id_forfait AND statut = 'actif'
                    ORDER BY date_fin DESC LIMIT 1
                ");
                $stmtAb->execute(['id_entreprise' => $enterpriseId, 'id_forfait' => (int)$data['id_forfait']]);
                $ab = $stmtAb->fetch(PDO::FETCH_ASSOC);
                if ($ab) {
                    $dateFinExistante = new DateTime($ab['date_fin']);
                    if ($dateFinExistante > new DateTime()) {
                        $duree = (int)($forfait['duree_jours'] ?? 0);
                        if ($duree > 0) {
                            $stmtUp = $bdd->prepare("UPDATE stock_abonnement SET date_fin = DATE_ADD(date_fin, INTERVAL :duree DAY) WHERE id_abonnement = :id");
                            $stmtUp->execute(['duree' => $duree, 'id' => $ab['id_abonnement']]);
                            $stmtUp = $bdd->prepare("SELECT date_fin FROM stock_abonnement WHERE id_abonnement = :id");
                            $stmtUp->execute(['id' => $ab['id_abonnement']]);
                            $row = $stmtUp->fetch(PDO::FETCH_ASSOC);
                            echo json_encode([
                                'success' => true,
                                'message' => 'Forfait renouvelé. La durée a été ajoutée au temps restant.',
                                'data' => ['id_abonnement' => (int)$ab['id_abonnement'], 'date_fin' => $row['date_fin'] ?? null]
                            ], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    }
                }
            }
            
            // Calculer la date de fin
            $dateDebut = new DateTime();
            $dateFin = clone $dateDebut;
            $dateFin->modify('+' . $forfait['duree_jours'] . ' days');
            
            // Désactiver les anciens abonnements
            $stmt = $bdd->prepare("
                UPDATE stock_abonnement 
                SET statut = 'annule' 
                WHERE id_entreprise = :id_entreprise AND statut = 'actif'
            ");
            $stmt->execute(['id_entreprise' => $enterpriseId]);
            
            // Créer le nouvel abonnement
            $stmt = $bdd->prepare("
                INSERT INTO stock_abonnement (
                    id_entreprise, id_forfait, date_debut, date_fin, prix_paye, statut
                ) VALUES (
                    :id_entreprise, :id_forfait, :date_debut, :date_fin, :prix_paye, 'actif'
                )
            ");
            
            $stmt->execute([
                'id_entreprise' => $enterpriseId,
                'id_forfait' => (int)$data['id_forfait'],
                'date_debut' => $dateDebut->format('Y-m-d H:i:s'),
                'date_fin' => $dateFin->format('Y-m-d H:i:s'),
                'prix_paye' => $forfait['prix']
            ]);
            
            $idAbonnement = $bdd->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Abonnement créé avec succès',
                'data' => [
                    'id_abonnement' => $idAbonnement,
                    'date_fin' => $dateFin->format('Y-m-d H:i:s')
                ]
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
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
