<?php
require_once __DIR__ . '/cors.php';
/**
 * API Entrepôt - Gestion des entrepôts
 * Endpoint: /api-stock/api_entrepot.php
 */
header('Content-Type: application/json');
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
    $enterpriseId = (int)($currentUser['enterprise_id'] ?? $currentUser['id_entreprise'] ?? 0);
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
                // Vérifier d'abord si la table existe
                try {
                    $testStmt = $bdd->query("SHOW TABLES LIKE 'stock_entrepot'");
                    $tableExists = $testStmt->fetch();
                    if (!$tableExists) {
                        echo json_encode([
                            'success' => true,
                            'data' => []
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => true,
                        'data' => []
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }


                try {
                    $isAdmin = isset($currentUser['user_role']) && in_array(strtolower($currentUser['user_role']), ['admin', 'superadmin']);
                    $params = [];
                    // Même règle de correspondance que action=produits : exact LOWER+TRIM OU l'un contient l'autre + actif uniquement
                    $matchJoin = " p.id_entreprise = e.id_entreprise AND (p.actif IS NULL OR p.actif = 1) AND (
                        LOWER(TRIM(COALESCE(p.entrepot,''))) = LOWER(TRIM(e.nom_entrepot))
                        OR ( TRIM(COALESCE(p.entrepot,'')) != ''
                             AND ( LOWER(TRIM(e.nom_entrepot)) LIKE CONCAT('%', LOWER(TRIM(p.entrepot)), '%')
                                  OR LOWER(TRIM(p.entrepot)) LIKE CONCAT('%', LOWER(TRIM(e.nom_entrepot)), '%') ) )
                    ) ";
                    $sql = "
                        SELECT 
                            e.*,
                            COUNT(DISTINCT p.id_produit) AS nombre_produits,
                            COALESCE(SUM(p.quantite_stock), 0) AS stock_total,
                            COALESCE(SUM(p.quantite_stock * p.prix_achat), 0) AS valeur_stock_achat,
                            COALESCE(SUM(p.quantite_stock * p.prix_vente), 0) AS valeur_stock_vente
                        FROM stock_entrepot e
                        LEFT JOIN stock_produit p ON " . $matchJoin . "
                        WHERE e.id_entreprise = ?
                    ";
                    $params[] = $enterpriseId;
                    if (!$isAdmin && isset($currentUser['permissions_entrepots']) && is_array($currentUser['permissions_entrepots']) && count($currentUser['permissions_entrepots']) > 0) {
                        $idsAutorises = array_map('intval', $currentUser['permissions_entrepots']);
                        $in = implode(',', array_fill(0, count($idsAutorises), '?'));
                        $sql .= " AND e.id_entrepot IN ($in) ";
                        foreach ($idsAutorises as $id) {
                            $params[] = $id;
                        }
                    }
                    $sql .= " GROUP BY e.id_entrepot ORDER BY e.date_creation DESC ";
                    $stmt = $bdd->prepare($sql);
                    $stmt->execute($params);
                    $entrepots = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Erreur SQL api_entrepot: " . $e->getMessage());
                    $entrepots = [];
                }

                echo json_encode([
                    'success' => true,
                    'data' => $entrepots
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif ($action === 'rapport' && isset($_GET['id_entrepot'])) {
                // Récupérer le rapport hebdomadaire d'un entrepôt
                $idEntrepot = (int)$_GET['id_entrepot'];
                
                // Vérifier que l'entrepôt appartient à l'entreprise
                $stmt = $bdd->prepare("SELECT nom_entrepot FROM stock_entrepot WHERE id_entrepot = :id AND id_entreprise = :id_entreprise");
                $stmt->execute(['id' => $idEntrepot, 'id_entreprise' => $enterpriseId]);
                $entrepot = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$entrepot) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Entrepôt non trouvé'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                $nomEntrepot = trim($entrepot['nom_entrepot']);
                // Paramètres positionnels ? pour éviter HY093 (paramètres nommés dupliqués)
                $matchEntrepot = " ( LOWER(TRIM(COALESCE(p.entrepot,''))) = LOWER(TRIM(?)) OR ( TRIM(COALESCE(p.entrepot,'')) != '' AND ( LOWER(TRIM(?)) LIKE CONCAT('%', LOWER(TRIM(p.entrepot)), '%') OR LOWER(TRIM(p.entrepot)) LIKE CONCAT('%', LOWER(TRIM(?)), '%') ) ) ) ";
                // Calculer la date de début de la semaine (7 derniers jours)
                $dateDebut = date('Y-m-d H:i:s', strtotime('-7 days'));
                $paramsRapport = [$enterpriseId, $nomEntrepot, $nomEntrepot, $nomEntrepot, $dateDebut];
                
                // Récupérer les entrées de la semaine
                $stmt = $bdd->prepare("
                    SELECT 
                        e.id_entree AS id,
                        'entree' AS type,
                        e.date_entree AS date,
                        e.quantite,
                        p.nom AS produit_nom,
                        p.code_produit,
                        e.notes,
                        NULL AS type_sortie,
                        NULL AS motif,
                        NULL AS entrepot_destination
                    FROM stock_entree e
                    INNER JOIN stock_produit p ON e.id_produit = p.id_produit
                    WHERE p.id_entreprise = ? AND " . $matchEntrepot . "
                    AND e.date_entree >= ?
                    ORDER BY e.date_entree DESC
                ");
                $stmt->execute($paramsRapport);
                $entrees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Récupérer les sorties de la semaine
                $stmt = $bdd->prepare("
                    SELECT 
                        s.id_sortie AS id,
                        'sortie' AS type,
                        s.date_sortie AS date,
                        s.quantite,
                        p.nom AS produit_nom,
                        p.code_produit,
                        NULL AS notes,
                        s.type_sortie,
                        s.motif,
                        s.entrepot_destination
                    FROM stock_sortie s
                    INNER JOIN stock_produit p ON s.id_produit = p.id_produit
                    WHERE p.id_entreprise = ? AND " . $matchEntrepot . "
                    AND s.date_sortie >= ?
                    ORDER BY s.date_sortie DESC
                ");
                $stmt->execute($paramsRapport);
                $sorties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Combiner et trier tous les mouvements
                $mouvements = array_merge($entrees, $sorties);
                usort($mouvements, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                
                // Calculer les totaux
                $totalEntrees = count($entrees);
                $totalSorties = count($sorties);
                $totalMouvements = count($mouvements);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'totalEntrees' => $totalEntrees,
                        'totalSorties' => $totalSorties,
                        'totalMouvements' => $totalMouvements,
                        'mouvements' => $mouvements
                    ]
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif ($action === 'produits' && isset($_GET['id_entrepot'])) {
                // Récupérer les produits d'un entrepôt spécifique
                $idEntrepot = (int)$_GET['id_entrepot'];
                $isAdmin = isset($currentUser['user_role']) && in_array(strtolower($currentUser['user_role']), ['admin', 'superadmin']);
                
                // Vérifier que l'entrepôt appartient à l'entreprise
                $stmt = $bdd->prepare("SELECT nom_entrepot FROM stock_entrepot WHERE id_entrepot = :id AND id_entreprise = :id_entreprise");
                $stmt->execute(['id' => $idEntrepot, 'id_entreprise' => $enterpriseId]);
                $entrepot = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$entrepot) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Entrepôt non trouvé'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (!$isAdmin && isset($currentUser['permissions_entrepots']) && is_array($currentUser['permissions_entrepots']) && count($currentUser['permissions_entrepots']) > 0) {
                    $ids = array_map('intval', $currentUser['permissions_entrepots']);
                    if (!in_array($idEntrepot, $ids, true)) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Accès non autorisé à cet entrepôt'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
                // Vérifier que la colonne entrepot existe (add_entrepot_column.sql)
                $hasEntrepotCol = true;
                try {
                    $chk = $bdd->query("SHOW COLUMNS FROM stock_produit LIKE 'entrepot'");
                    $hasEntrepotCol = $chk && $chk->rowCount() > 0;
                } catch (Throwable $t) { /* ignore */ }
                if (!$hasEntrepotCol) {
                    echo json_encode(['success' => true, 'data' => [], 'entrepot' => $entrepot], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $nom = trim((string)($entrepot['nom_entrepot'] ?? ''));
                // Match flexible : exact OU nom/entrepot l’un contient l’autre (ex. "meuble" / "Entrepot meuble")
                $stmt = $bdd->prepare("
                    SELECT 
                        p.*,
                        (p.prix_vente - p.prix_achat) AS marge_beneficiaire,
                        (p.quantite_stock * p.prix_achat) AS valeur_stock_achat,
                        (p.quantite_stock * p.prix_vente) AS valeur_stock_vente
                    FROM stock_produit p
                    WHERE p.id_entreprise = ?
                    AND (p.actif IS NULL OR p.actif = 1)
                    AND (
                        LOWER(TRIM(COALESCE(p.entrepot,''))) = LOWER(TRIM(?))
                        OR ( TRIM(COALESCE(p.entrepot,'')) != ''
                             AND ( LOWER(TRIM(?)) LIKE CONCAT('%', LOWER(TRIM(p.entrepot)), '%')
                                  OR LOWER(TRIM(p.entrepot)) LIKE CONCAT('%', LOWER(TRIM(?)), '%') ) )
                    )
                    ORDER BY p.nom ASC
                ");
                $stmt->execute([$enterpriseId, $nom, $nom, $nom]);
                $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("✅ [API Entrepot] Produits trouvés: " . count($produits) . " pour entrepôt '$nom'");
                if (count($produits) > 0) {
                    $exemples = array_slice($produits, 0, 3);
                    foreach ($exemples as $ex) {
                        error_log("  - Produit: {$ex['nom']} (id={$ex['id_produit']}), stock={$ex['quantite_stock']}, entrepot={$ex['entrepot']}");
                    }
                } else {
                    // Vérifier tous les produits pour debug
                    $checkAll = $bdd->prepare("SELECT COUNT(*) as total, GROUP_CONCAT(DISTINCT entrepot SEPARATOR ', ') as entrepots FROM stock_produit WHERE id_entreprise = ? AND (actif IS NULL OR actif = 1)");
                    $checkAll->execute([$enterpriseId]);
                    $allData = $checkAll->fetch(PDO::FETCH_ASSOC);
                    error_log("⚠️ [API Entrepot] Aucun produit trouvé pour '$nom'. Total produits actifs: " . ($allData['total'] ?? 0) . ", Entrepôts dans produits: " . ($allData['entrepots'] ?? ''));
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $produits,
                    'entrepot' => $entrepot
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif ($action === 'sorties' && isset($_GET['id_entrepot'])) {
                // Historique des sorties d’un entrepôt (sans limite 7 jours)
                $idEntrepot = (int)$_GET['id_entrepot'];
                $jours = isset($_GET['jours']) ? (int)$_GET['jours'] : 90;
                $isAdmin = isset($currentUser['user_role']) && in_array(strtolower($currentUser['user_role']), ['admin', 'superadmin']);
                
                $stmt = $bdd->prepare("SELECT nom_entrepot FROM stock_entrepot WHERE id_entrepot = :id AND id_entreprise = :id_entreprise");
                $stmt->execute(['id' => $idEntrepot, 'id_entreprise' => $enterpriseId]);
                $entrepot = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$entrepot) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Entrepôt non trouvé'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (!$isAdmin && isset($currentUser['permissions_entrepots']) && is_array($currentUser['permissions_entrepots']) && count($currentUser['permissions_entrepots']) > 0) {
                    $ids = array_map('intval', $currentUser['permissions_entrepots']);
                    if (!in_array($idEntrepot, $ids, true)) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Accès non autorisé'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
                // Vérifier colonne entrepot (stock_produit) et entrepot_destination (stock_sortie)
                $hasEntrepotCol = true;
                $hasEntrepotDest = true;
                try {
                    $c1 = $bdd->query("SHOW COLUMNS FROM stock_produit LIKE 'entrepot'");
                    $hasEntrepotCol = $c1 && $c1->rowCount() > 0;
                    $c2 = $bdd->query("SHOW COLUMNS FROM stock_sortie LIKE 'entrepot_destination'");
                    $hasEntrepotDest = $c2 && $c2->rowCount() > 0;
                } catch (Throwable $t) { /* ignore */ }
                if (!$hasEntrepotCol) {
                    echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $colDest = $hasEntrepotDest ? 's.entrepot_destination' : 'NULL AS entrepot_destination';
                $nom = trim((string)($entrepot['nom_entrepot'] ?? ''));
                $dateFiltre = $jours > 0 ? " AND s.date_sortie >= DATE_SUB(NOW(), INTERVAL " . (int)$jours . " DAY) " : "";
                $stmt = $bdd->prepare("
                    SELECT s.id_sortie AS id, 'sortie' AS type, s.date_sortie AS date, s.quantite,
                           p.nom AS produit_nom, p.code_produit, s.type_sortie, s.motif, " . $colDest . ",
                           (s.quantite * COALESCE(s.prix_unitaire, p.prix_vente, 0)) AS montant
                    FROM stock_sortie s
                    INNER JOIN stock_produit p ON s.id_produit = p.id_produit
                    WHERE p.id_entreprise = ?
                    AND ( LOWER(TRIM(COALESCE(p.entrepot,''))) = LOWER(TRIM(?))
                          OR ( TRIM(COALESCE(p.entrepot,'')) != '' AND ( LOWER(TRIM(?)) LIKE CONCAT('%', LOWER(TRIM(p.entrepot)), '%') OR LOWER(TRIM(p.entrepot)) LIKE CONCAT('%', LOWER(TRIM(?)), '%') ) ) )
                    " . $dateFiltre . "
                    ORDER BY s.date_sortie DESC
                ");
                $stmt->execute([$enterpriseId, $nom, $nom, $nom]);
                $sorties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $sorties], JSON_UNESCAPED_UNICODE);
                exit;
                
            } elseif (isset($_GET['id_entrepot'])) {
                // Récupérer un entrepôt spécifique (même règle de match que action=produits)
                $idEntrepot = (int)$_GET['id_entrepot'];
                $matchSingle = " p.id_entreprise = e.id_entreprise AND (p.actif IS NULL OR p.actif = 1) AND (
                    LOWER(TRIM(COALESCE(p.entrepot,''))) = LOWER(TRIM(e.nom_entrepot))
                    OR ( TRIM(COALESCE(p.entrepot,'')) != ''
                         AND ( LOWER(TRIM(e.nom_entrepot)) LIKE CONCAT('%', LOWER(TRIM(p.entrepot)), '%')
                              OR LOWER(TRIM(p.entrepot)) LIKE CONCAT('%', LOWER(TRIM(e.nom_entrepot)), '%') ) )
                ) ";
                $stmt = $bdd->prepare("
                    SELECT 
                        e.*,
                        COUNT(DISTINCT p.id_produit) AS nombre_produits,
                        COALESCE(SUM(p.quantite_stock), 0) AS stock_total,
                        COALESCE(SUM(p.quantite_stock * p.prix_achat), 0) AS valeur_stock_achat,
                        COALESCE(SUM(p.quantite_stock * p.prix_vente), 0) AS valeur_stock_vente
                    FROM stock_entrepot e
                    LEFT JOIN stock_produit p ON " . $matchSingle . "
                    WHERE e.id_entrepot = :id AND e.id_entreprise = :id_entreprise
                    GROUP BY e.id_entrepot
                ");
                $stmt->execute(['id' => $idEntrepot, 'id_entreprise' => $enterpriseId]);
                $entrepot = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$entrepot) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Entrepôt non trouvé'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $entrepot
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // Créer un nouvel entrepôt
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['nom_entrepot'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Le nom de l\'entrepôt est requis'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier si le nom existe déjà pour cette entreprise
            $stmt = $bdd->prepare("SELECT id_entrepot FROM stock_entrepot WHERE nom_entrepot = :nom AND id_entreprise = :id_entreprise");
            $stmt->execute(['nom' => $data['nom_entrepot'], 'id_entreprise' => $enterpriseId]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Un entrepôt avec ce nom existe déjà'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier les limites du forfait avant de créer l'entrepôt
            if (function_exists('checkEntrepotLimit')) {
                $limitCheck = checkEntrepotLimit($bdd, $enterpriseId);
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
                INSERT INTO stock_entrepot (
                    nom_entrepot, adresse, ville, pays, telephone, email,
                    responsable, capacite_max, id_entreprise, actif
                ) VALUES (
                    :nom_entrepot, :adresse, :ville, :pays, :telephone, :email,
                    :responsable, :capacite_max, :id_entreprise, :actif
                )
            ");
            
            $stmt->execute([
                'nom_entrepot' => $data['nom_entrepot'],
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'email' => $data['email'] ?? null,
                'responsable' => $data['responsable'] ?? null,
                'capacite_max' => !empty($data['capacite_max']) ? (int)$data['capacite_max'] : null,
                'id_entreprise' => $enterpriseId,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1
            ]);
            
            $idEntrepot = $bdd->lastInsertId();
            // Journaliser la création de l'entrepôt avec le nom
            $userName = $currentUser['nom'] ?? ($currentUser['email'] ?? 'Utilisateur');
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), ?, ?, ?, ?)');
            $journalStmt->execute([
                $userName,
                'Création entrepôt',
                'Entrepôt : ' . ($data['nom_entrepot'] ?? ''),
                $enterpriseId
            ]);
            echo json_encode([
                'success' => true,
                'message' => 'Entrepôt créé avec succès',
                'data' => ['id_entrepot' => $idEntrepot]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'PUT':
            // Mettre à jour un entrepôt
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id_entrepot'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID entrepôt requis'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $idEntrepot = (int)$data['id_entrepot'];
            
            // Vérifier que l'entrepôt appartient à l'entreprise
            $stmt = $bdd->prepare("SELECT id_entrepot FROM stock_entrepot WHERE id_entrepot = :id AND id_entreprise = :id_entreprise");
            $stmt->execute(['id' => $idEntrepot, 'id_entreprise' => $enterpriseId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Entrepôt non trouvé'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier si le nouveau nom existe déjà (si changé)
            if (!empty($data['nom_entrepot'])) {
                $stmt = $bdd->prepare("SELECT id_entrepot FROM stock_entrepot WHERE nom_entrepot = :nom AND id_entreprise = :id_entreprise AND id_entrepot != :id");
                $stmt->execute(['nom' => $data['nom_entrepot'], 'id_entreprise' => $enterpriseId, 'id' => $idEntrepot]);
                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Un entrepôt avec ce nom existe déjà'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            
            $stmt = $bdd->prepare("
                UPDATE stock_entrepot SET
                    nom_entrepot = COALESCE(:nom_entrepot, nom_entrepot),
                    adresse = :adresse,
                    ville = :ville,
                    pays = :pays,
                    telephone = :telephone,
                    email = :email,
                    responsable = :responsable,
                    capacite_max = :capacite_max,
                    actif = :actif
                WHERE id_entrepot = :id AND id_entreprise = :id_entreprise
            ");
            
            $stmt->execute([
                'nom_entrepot' => $data['nom_entrepot'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'email' => $data['email'] ?? null,
                'responsable' => $data['responsable'] ?? null,
                'capacite_max' => !empty($data['capacite_max']) ? (int)$data['capacite_max'] : null,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1,
                'id' => $idEntrepot,
                'id_entreprise' => $enterpriseId
            ]);
            
            // Journaliser la modification de l'entrepôt avec le nom
            $userName = $currentUser['nom'] ?? ($currentUser['email'] ?? 'Utilisateur');
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), ?, ?, ?, ?)');
            $journalStmt->execute([
                $userName,
                'Modification entrepôt',
                'Entrepôt : ' . ($data['nom_entrepot'] ?? ''),
                $enterpriseId
            ]);
            echo json_encode([
                'success' => true,
                'message' => 'Entrepôt mis à jour avec succès'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'DELETE':
            // Supprimer un entrepôt
            $idEntrepot = (int)($_GET['id_entrepot'] ?? 0);
            
            if (!$idEntrepot) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID entrepôt requis'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier que l'entrepôt appartient à l'entreprise
            $stmt = $bdd->prepare("SELECT nom_entrepot FROM stock_entrepot WHERE id_entrepot = :id AND id_entreprise = :id_entreprise");
            $stmt->execute(['id' => $idEntrepot, 'id_entreprise' => $enterpriseId]);
            $entrepot = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$entrepot) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Entrepôt non trouvé'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Vérifier s'il y a des produits dans cet entrepôt
            $stmt = $bdd->prepare("SELECT COUNT(*) as count FROM stock_produit WHERE LOWER(TRIM(entrepot)) = LOWER(TRIM(:nom)) AND id_entreprise = :id_entreprise");
            $stmt->execute(['nom' => trim($entrepot['nom_entrepot']), 'id_entreprise' => $enterpriseId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Impossible de supprimer l\'entrepôt : il contient des produits. Transférez d\'abord les produits vers un autre entrepôt.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $bdd->prepare("DELETE FROM stock_entrepot WHERE id_entrepot = :id AND id_entreprise = :id_entreprise");
            $stmt->execute(['id' => $idEntrepot, 'id_entreprise' => $enterpriseId]);
            
            // Journaliser la suppression de l'entrepôt avec le nom
            $userName = $currentUser['nom'] ?? ($currentUser['email'] ?? 'Utilisateur');
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), ?, ?, ?, ?)');
            $journalStmt->execute([
                $userName,
                'Suppression entrepôt',
                'Entrepôt : ' . ($entrepot['nom_entrepot'] ?? ''),
                $enterpriseId
            ]);
            echo json_encode([
                'success' => true,
                'message' => 'Entrepôt supprimé avec succès'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Méthode non autorisée'
            ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'error' => $e->getMessage(),
        'code' => $code
    ], JSON_UNESCAPED_UNICODE);
}








