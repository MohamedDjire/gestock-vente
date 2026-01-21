
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/database.php';
    $bdd = createDatabaseConnection();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $sql = "SELECT * FROM stock_fournisseurs ORDER BY id DESC";
        $stmt = $bdd->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO stock_fournisseurs (nom, email, telephone, adresse, statut) VALUES (:nom, :email, :telephone, :adresse, :statut)";
        $stmt = $bdd->prepare($sql);
        $stmt->bindValue(':nom', $data['nom'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':telephone', $data['telephone'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':adresse', $data['adresse'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':statut', $data['statut'] ?? 'actif', PDO::PARAM_STR);
        $stmt->execute();
        // Journaliser la création du fournisseur avec le nom
        $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details) VALUES (NOW(), ?, ?, ?)');
        $journalStmt->execute([
            $data['nom'] ?? 'Utilisateur',
            'Création fournisseur',
            'Fournisseur : ' . ($data['nom'] ?? '')
        ]);
        // Journaliser la modification du fournisseur avec le nom
        $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details) VALUES (NOW(), ?, ?, ?)');
        $journalStmt->execute([
            $data['nom'] ?? 'Utilisateur',
            'Modification fournisseur',
            'Fournisseur : ' . ($data['nom'] ?? '')
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID manquant']); exit; }
        $sql = "UPDATE  stock_fournisseurs SET nom=:nom, email=:email, telephone=:telephone, adresse=:adresse, statut=:statut WHERE id=:id";
        $stmt = $bdd->prepare($sql);
        $stmt->bindValue(':nom', $data['nom'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':telephone', $data['telephone'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':adresse', $data['adresse'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':statut', $data['statut'] ?? 'actif', PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID manquant']); exit; }
        // Récupérer le nom du fournisseur avant suppression
        $stmtNom = $bdd->prepare('SELECT nom FROM stock_fournisseurs WHERE id=:id');
        $stmtNom->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtNom->execute();
        $fournisseur = $stmtNom->fetch(PDO::FETCH_ASSOC);
        $sql = "DELETE FROM stock_fournisseurs WHERE id=:id";
        $stmt = $bdd->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        // Journaliser la suppression du fournisseur avec le nom
        $journalStmt = $bdd->prepare('INSERT INTO stock_journal (date, user, action, details) VALUES (NOW(), ?, ?, ?)');
        $journalStmt->execute([
            $fournisseur['nom'] ?? 'Utilisateur',
            'Suppression fournisseur',
            'Fournisseur : ' . ($fournisseur['nom'] ?? '')
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Méthode non supportée']);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
}
