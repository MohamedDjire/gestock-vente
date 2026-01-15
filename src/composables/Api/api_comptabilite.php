<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/database.php';

if (!function_exists('getDb')) {
    function getDb() {
        return createDatabaseConnection();
    }
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, x-auth-token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = $_GET['action'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$id_entreprise = $_GET['id_entreprise'] ?? null;

try {
    $bdd = getDb();
    if ($action === 'all') {
        // Liste des mouvements
        $sql = "SELECT * FROM stock_comptabilite WHERE id_entreprise = ? ORDER BY date DESC";
        $stmt = $bdd->prepare($sql);
        $stmt->execute([$id_entreprise]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    } elseif ($action === 'one' && $id) {
        $sql = "SELECT * FROM stock_comptabilite WHERE id_compta = ?";
        $stmt = $bdd->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $row]);
    } elseif ($action === 'create') {
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO stock_comptabilite (date, type, montant, user, details, id_entreprise, categorie, moyen_paiement, statut, piece_jointe, reference, commentaire, utilisateur_validateur, date_validation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $bdd->prepare($sql);
        $stmt->execute([
            $data['date'],
            $data['type'],
            $data['montant'],
            $data['user'],
            $data['details'],
            $data['id_entreprise'],
            $data['categorie'] ?? null,
            $data['moyen_paiement'] ?? null,
            $data['statut'] ?? 'en attente',
            $data['piece_jointe'] ?? null,
            $data['reference'] ?? null,
            $data['commentaire'] ?? null,
            $data['utilisateur_validateur'] ?? null,
            $data['date_validation'] ?? null
        ]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'update' && $id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "UPDATE stock_comptabilite SET date=?, type=?, montant=?, user=?, details=?, categorie=?, moyen_paiement=?, statut=?, piece_jointe=?, reference=?, commentaire=?, utilisateur_validateur=?, date_validation=?, updated_at=NOW() WHERE id_compta=?";
        $stmt = $bdd->prepare($sql);
        $stmt->execute([
            $data['date'],
            $data['type'],
            $data['montant'],
            $data['user'],
            $data['details'],
            $data['categorie'] ?? null,
            $data['moyen_paiement'] ?? null,
            $data['statut'] ?? 'en attente',
            $data['piece_jointe'] ?? null,
            $data['reference'] ?? null,
            $data['commentaire'] ?? null,
            $data['utilisateur_validateur'] ?? null,
            $data['date_validation'] ?? null,
            $id
        ]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete' && $id) {
        $sql = "DELETE FROM stock_comptabilite WHERE id_compta = ?";
        $stmt = $bdd->prepare($sql);
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non valide ou paramètres manquants']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
