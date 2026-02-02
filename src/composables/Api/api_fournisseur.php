<?php
/**
 * API Fournisseurs - Gestion des fournisseurs par entreprise
 * Chaque entreprise ne voit et ne gère que ses propres fournisseurs.
 */
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

try {
    require_once __DIR__ . '/config/database.php';
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
$middlewareFile = __DIR__ . '/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    $middlewareFile = __DIR__ . '/functions/middleware_auth.php';
}
if (!file_exists($middlewareFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Middleware d\'authentification introuvable'], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $middlewareFile;

if (!function_exists('authenticateAndAuthorize')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Authentification non disponible'], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUser = null;
$enterpriseId = null;
try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = (int)($currentUser['enterprise_id'] ?? $currentUser['user_enterprise_id'] ?? 0);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($enterpriseId <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Entreprise non identifiée'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Vérifier si la table a la colonne id_entreprise (migration optionnelle)
$hasEnterpriseColumn = false;
try {
    $check = $bdd->query("SHOW COLUMNS FROM stock_fournisseurs LIKE 'id_entreprise'");
    $hasEnterpriseColumn = $check && $check->rowCount() > 0;
} catch (PDOException $e) {
    // Table peut s'appeler fournisseurs
    try {
        $check = $bdd->query("SHOW TABLES LIKE 'stock_fournisseurs'");
        if ($check && $check->rowCount() > 0) {
            $check2 = $bdd->query("SHOW COLUMNS FROM stock_fournisseurs LIKE 'id_entreprise'");
            $hasEnterpriseColumn = $check2 && $check2->rowCount() > 0;
        }
    } catch (PDOException $e2) {
        // ignore
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$tableName = 'stock_fournisseurs';

if ($method === 'GET') {
    if (!$hasEnterpriseColumn) {
        // Pas encore migré : retourner liste vide pour cette entreprise (isolation)
        echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $sql = "SELECT * FROM {$tableName} WHERE id_entreprise = :id_entreprise ORDER BY id DESC";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_entreprise' => $enterpriseId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (!$hasEnterpriseColumn) {
        http_response_code(501);
        echo json_encode([
            'success' => false,
            'message' => 'Migration requise : exécutez add_fournisseur_id_entreprise.sql sur la base pour activer les fournisseurs par entreprise.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $sql = "INSERT INTO {$tableName} (nom, email, telephone, adresse, statut, id_entreprise) VALUES (:nom, :email, :telephone, :adresse, :statut, :id_entreprise)";
    $stmt = $bdd->prepare($sql);
    $stmt->bindValue(':nom', $data['nom'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':email', $data['email'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':telephone', $data['telephone'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':adresse', $data['adresse'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':statut', $data['statut'] ?? 'actif', PDO::PARAM_STR);
    $stmt->bindValue(':id_entreprise', $enterpriseId, PDO::PARAM_INT);
    $stmt->execute();
    // Journal avec id_entreprise si la table journal le supporte
    try {
        $journalCheck = $bdd->query("SHOW COLUMNS FROM stock_journal LIKE 'id_entreprise'");
        if ($journalCheck && $journalCheck->rowCount() > 0) {
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), ?, ?, ?, ?)');
            $journalStmt->execute([
                $currentUser['username'] ?? $currentUser['email'] ?? 'Utilisateur',
                'Création fournisseur',
                'Fournisseur : ' . ($data['nom'] ?? ''),
                $enterpriseId
            ]);
        } else {
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details) VALUES (NOW(), ?, ?, ?)');
            $journalStmt->execute([
                $currentUser['username'] ?? 'Utilisateur',
                'Création fournisseur',
                'Fournisseur : ' . ($data['nom'] ?? '')
            ]);
        }
    } catch (PDOException $e) {
        error_log("Journal fournisseur: " . $e->getMessage());
    }
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($_GET['id'] ?? $data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID manquant'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$hasEnterpriseColumn) {
        http_response_code(501);
        echo json_encode(['success' => false, 'message' => 'Migration requise (add_fournisseur_id_entreprise.sql)'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $bdd->prepare("SELECT id FROM {$tableName} WHERE id = :id AND id_entreprise = :id_entreprise");
    $stmt->execute(['id' => $id, 'id_entreprise' => $enterpriseId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Fournisseur introuvable ou accès non autorisé'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $sql = "UPDATE {$tableName} SET nom=:nom, email=:email, telephone=:telephone, adresse=:adresse, statut=:statut WHERE id=:id AND id_entreprise=:id_entreprise";
    $stmt = $bdd->prepare($sql);
    $stmt->bindValue(':nom', $data['nom'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':email', $data['email'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':telephone', $data['telephone'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':adresse', $data['adresse'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':statut', $data['statut'] ?? 'actif', PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':id_entreprise', $enterpriseId, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID manquant'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$hasEnterpriseColumn) {
        http_response_code(501);
        echo json_encode(['success' => false, 'message' => 'Migration requise (add_fournisseur_id_entreprise.sql)'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmtNom = $bdd->prepare("SELECT nom FROM {$tableName} WHERE id = :id AND id_entreprise = :id_entreprise");
    $stmtNom->execute(['id' => $id, 'id_entreprise' => $enterpriseId]);
    $fournisseur = $stmtNom->fetch(PDO::FETCH_ASSOC);
    if (!$fournisseur) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Fournisseur introuvable ou accès non autorisé'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $bdd->prepare("DELETE FROM {$tableName} WHERE id = :id AND id_entreprise = :id_entreprise");
    $stmt->execute(['id' => $id, 'id_entreprise' => $enterpriseId]);
    try {
        $journalCheck = $bdd->query("SHOW COLUMNS FROM stock_journal LIKE 'id_entreprise'");
        if ($journalCheck && $journalCheck->rowCount() > 0) {
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details, id_entreprise) VALUES (NOW(), ?, ?, ?, ?)');
            $journalStmt->execute([
                $currentUser['username'] ?? 'Utilisateur',
                'Suppression fournisseur',
                'Fournisseur : ' . ($fournisseur['nom'] ?? ''),
                $enterpriseId
            ]);
        } else {
            $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details) VALUES (NOW(), ?, ?, ?)');
            $journalStmt->execute([
                $currentUser['username'] ?? 'Utilisateur',
                'Suppression fournisseur',
                'Fournisseur : ' . ($fournisseur['nom'] ?? '')
            ]);
        }
    } catch (PDOException $e) {
        error_log("Journal fournisseur: " . $e->getMessage());
    }
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Méthode non supportée'], JSON_UNESCAPED_UNICODE);
