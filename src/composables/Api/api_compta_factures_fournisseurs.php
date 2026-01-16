

<?php
$dbPath = __DIR__ . '/config/database.php';
if (!file_exists($dbPath)) $dbPath = dirname(__DIR__) . '/api/config/database.php';
require_once $dbPath;
if (!function_exists('createDatabaseConnection')) {
    echo json_encode([
        'success'=>false,
        'message'=>'database.php non trouvé ou fonction absente',
        'dbPath'=>$dbPath,
        'file'=>__FILE__,
        'line'=>__LINE__,
        'included_files'=>get_included_files()
    ]);
    exit;
}
try {
    $pdo = createDatabaseConnection();
    if (!$pdo || !($pdo instanceof PDO)) {
        echo json_encode([
            'success' => false,
            'message' => 'Connexion PDO échouée ou objet non valide',
            'dbPath' => $dbPath,
            'pdo_type' => gettype($pdo),
            'pdo_dump' => print_r($pdo, true),
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success'=>false,
        'message'=>'Erreur PDO: ' . $e->getMessage(),
        'dbPath'=>$dbPath,
        'file' => __FILE__,
        'line' => __LINE__
    ]);
    exit;
}
// CORS universel
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// API pour la gestion des factures fournisseurs (compta_factures_fournisseurs)
require_once 'config.php';
require_once __DIR__ . '/functions/middleware_auth.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$id_entreprise = $_GET['id_entreprise'] ?? $_POST['id_entreprise'] ?? null;

function response($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

if (!$id_entreprise) {
    response(false, null, 'ID entreprise manquant');
}

switch ($action) {
    case 'all':
        $sql = "SELECT * FROM stock_compta_factures_fournisseurs WHERE id_entreprise = ? ORDER BY date_facture DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_entreprise]);
        response(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) response(false, null, 'ID manquant');
        $sql = "SELECT * FROM stock_compta_factures_fournisseurs WHERE id_facture = ? AND id_entreprise = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $id_entreprise]);
        response(true, $stmt->fetch(PDO::FETCH_ASSOC));
        break;
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $sql = "INSERT INTO stock_compta_factures_fournisseurs (numero_facture, date_facture, montant, fournisseur, statut, id_entreprise) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $data['numero_facture'] ?? '',
            $data['date_facture'] ?? date('Y-m-d'),
            $data['montant'] ?? 0,
            $data['fournisseur'] ?? '',
            $data['statut'] ?? 'en attente',
            $id_entreprise
        ]);
        // Ajout écriture comptable automatique
        if ($ok) {
            try {
                $sqlEcriture = "INSERT INTO stock_compta_ecritures (date_ecriture, type_ecriture, montant, user, categorie, moyen_paiement, statut, reference, piece_jointe, commentaire, details, id_entreprise) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtEcriture = $pdo->prepare($sqlEcriture);
                $stmtEcriture->execute([
                    $data['date_facture'] ?? date('Y-m-d'),
                    'facture_fournisseur',
                    $data['montant'] ?? 0,
                    $data['fournisseur'] ?? '',
                    'Facture fournisseur',
                    '',
                    $data['statut'] ?? 'en attente',
                    $data['numero_facture'] ?? '',
                    '',
                    '',
                    'Facture fournisseur : ' . ($data['fournisseur'] ?? ''),
                    $id_entreprise
                ]);
            } catch (Exception $e) {
                error_log("Erreur écriture comptable facture fournisseur : " . $e->getMessage());
            }
        }
        response($ok, null, $ok ? 'Ajouté' : 'Erreur lors de l\'ajout');
        break;
    case 'update':
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) response(false, null, 'ID manquant');
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $sql = "UPDATE stock_compta_factures_fournisseurs SET numero_facture=?, date_facture=?, montant=?, fournisseur=?, statut=? WHERE id_facture=? AND id_entreprise=?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $data['numero_facture'] ?? '',
            $data['date_facture'] ?? date('Y-m-d'),
            $data['montant'] ?? 0,
            $data['fournisseur'] ?? '',
            $data['statut'] ?? 'en attente',
            $id,
            $id_entreprise
        ]);
        response($ok, null, $ok ? 'Modifié' : 'Erreur lors de la modification');
        break;
    case 'delete':
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) response(false, null, 'ID manquant');
        $sql = "DELETE FROM stock_compta_factures_fournisseurs WHERE id_facture = ? AND id_entreprise = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([$id, $id_entreprise]);
        response($ok, null, $ok ? 'Supprimé' : 'Erreur lors de la suppression');
        break;
    default:
        response(false, null, 'Action inconnue');
}
