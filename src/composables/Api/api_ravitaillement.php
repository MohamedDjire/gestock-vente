<?php
/**
 * API Ravitaillement - Liaison Point de vente ↔ Entrepôts
 * Un point de vente peut se ravitailler dans plusieurs entrepôts.
 * Un entrepôt peut servir plusieurs points de vente.
 * Endpoint: /api-stock/api_ravitaillement.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 0);
error_reporting(E_ALL);

$dbFile = __DIR__ . '/config/database.php';
if (!file_exists($dbFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration base de données introuvable'], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $dbFile;

try {
    $bdd = createDatabaseConnection();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur connexion BDD', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Créer la table si elle n'existe pas
$bdd->exec("
    CREATE TABLE IF NOT EXISTS stock_point_vente_entrepot (
        id_point_vente INT(11) NOT NULL,
        id_entrepot INT(11) NOT NULL,
        PRIMARY KEY (id_point_vente, id_entrepot),
        KEY idx_pve_entrepot (id_entrepot)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$middlewareFile = __DIR__ . '/functions/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    $middlewareFile = __DIR__ . '/middleware_auth.php';
}
if (!file_exists($middlewareFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Middleware auth introuvable'], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $middlewareFile;

if (!function_exists('authenticateAndAuthorize')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fonction authenticateAndAuthorize introuvable'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = (int)($currentUser['enterprise_id'] ?? $currentUser['id_entreprise'] ?? 0);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$role = strtolower($currentUser['user_role'] ?? $currentUser['role'] ?? '');
$isAdmin = in_array($role, ['admin', 'superadmin']);

$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $idPv = isset($_GET['id_point_vente']) ? (int)$_GET['id_point_vente'] : null;
        $idE = isset($_GET['id_entrepot']) ? (int)$_GET['id_entrepot'] : null;
        $listAll = isset($_GET['list']) && $_GET['list'] === '1';

        if ($listAll) {
            if (!$isAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Réservé aux administrateurs'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $bdd->prepare("
                SELECT pv.id_point_vente, pv.nom_point_vente,
                       GROUP_CONCAT(pve.id_entrepot) AS id_entrepots_concat
                FROM stock_point_vente pv
                LEFT JOIN stock_point_vente_entrepot pve ON pve.id_point_vente = pv.id_point_vente
                WHERE pv.id_entreprise = ?
                GROUP BY pv.id_point_vente, pv.nom_point_vente
                ORDER BY pv.nom_point_vente
            ");
            $stmt->execute([$enterpriseId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = array_map(function ($r) {
                $ids = $r['id_entrepots_concat'] ? array_map('intval', explode(',', $r['id_entrepots_concat'])) : [];
                return ['id_point_vente' => (int)$r['id_point_vente'], 'nom_point_vente' => $r['nom_point_vente'], 'id_entrepots' => $ids];
            }, $rows);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($idPv) {
            $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE id_point_vente = ? AND id_entreprise = ?");
            $stmt->execute([$idPv, $enterpriseId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Point de vente introuvable'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            // Non-admin : n'autoriser que si ce point de vente est dans leurs permissions
            if (!$isAdmin) {
                $perm = $currentUser['permissions_points_vente'] ?? [];
                $ids = is_array($perm) ? array_map('intval', $perm) : [];
                if (!in_array($idPv, $ids, true)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Accès non autorisé à ce point de vente'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            $stmt = $bdd->prepare("
                SELECT pve.id_entrepot, e.nom_entrepot
                FROM stock_point_vente_entrepot pve
                INNER JOIN stock_entrepot e ON e.id_entrepot = pve.id_entrepot AND e.id_entreprise = ?
                WHERE pve.id_point_vente = ?
            ");
            $stmt->execute([$enterpriseId, $idPv]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $id_entrepots = array_map('intval', array_column($rows, 'id_entrepot'));
            echo json_encode(['success' => true, 'id_entrepots' => $id_entrepots, 'entrepots' => $rows], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($idE) {
            if (!$isAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Réservé aux administrateurs'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $bdd->prepare("SELECT id_entrepot FROM stock_entrepot WHERE id_entrepot = ? AND id_entreprise = ?");
            $stmt->execute([$idE, $enterpriseId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Entrepôt introuvable'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $bdd->prepare("
                SELECT pve.id_point_vente, pv.nom_point_vente
                FROM stock_point_vente_entrepot pve
                INNER JOIN stock_point_vente pv ON pv.id_point_vente = pve.id_point_vente AND pv.id_entreprise = ?
                WHERE pve.id_entrepot = ?
            ");
            $stmt->execute([$enterpriseId, $idE]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $id_points_vente = array_map('intval', array_column($rows, 'id_point_vente'));
            echo json_encode(['success' => true, 'id_points_vente' => $id_points_vente, 'points_vente' => $rows], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fournir id_point_vente ou id_entrepot'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Réservé aux administrateurs'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $idPv = isset($input['id_point_vente']) ? (int)$input['id_point_vente'] : null;
        $idEntrepots = isset($input['id_entrepots']) && is_array($input['id_entrepots']) ? array_map('intval', $input['id_entrepots']) : null;
        $idE = isset($input['id_entrepot']) ? (int)$input['id_entrepot'] : null;
        $idPointsVente = isset($input['id_points_vente']) && is_array($input['id_points_vente']) ? array_map('intval', $input['id_points_vente']) : null;

        if ($idPv !== null && $idEntrepots !== null) {
            $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE id_point_vente = ? AND id_entreprise = ?");
            $stmt->execute([$idPv, $enterpriseId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Point de vente introuvable'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $bdd->prepare("DELETE FROM stock_point_vente_entrepot WHERE id_point_vente = ?")->execute([$idPv]);
            $stmtIns = $bdd->prepare("INSERT INTO stock_point_vente_entrepot (id_point_vente, id_entrepot) VALUES (?, ?)");
            foreach ($idEntrepots as $eid) {
                if ($eid <= 0) continue;
                $chk = $bdd->prepare("SELECT id_entrepot FROM stock_entrepot WHERE id_entrepot = ? AND id_entreprise = ?");
                $chk->execute([$eid, $enterpriseId]);
                if ($chk->fetch()) {
                    $stmtIns->execute([$idPv, $eid]);
                }
            }
            echo json_encode(['success' => true, 'message' => 'Ravitaillement mis à jour (par point de vente)'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($idE !== null && $idPointsVente !== null) {
            $stmt = $bdd->prepare("SELECT id_entrepot FROM stock_entrepot WHERE id_entrepot = ? AND id_entreprise = ?");
            $stmt->execute([$idE, $enterpriseId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Entrepôt introuvable'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $bdd->prepare("DELETE FROM stock_point_vente_entrepot WHERE id_entrepot = ?")->execute([$idE]);
            $stmtIns = $bdd->prepare("INSERT INTO stock_point_vente_entrepot (id_point_vente, id_entrepot) VALUES (?, ?)");
            foreach ($idPointsVente as $pvid) {
                if ($pvid <= 0) continue;
                $chk = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE id_point_vente = ? AND id_entreprise = ?");
                $chk->execute([$pvid, $enterpriseId]);
                if ($chk->fetch()) {
                    $stmtIns->execute([$pvid, $idE]);
                }
            }
            echo json_encode(['success' => true, 'message' => 'Ravitaillement mis à jour (par entrepôt)'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Body: { id_point_vente, id_entrepots: [] } ou { id_entrepot, id_points_vente: [] }'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée'], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
