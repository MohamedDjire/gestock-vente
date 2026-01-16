

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
// API pour la gestion des rapports (compta_rapports)
require_once 'config.php';
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
        $sql = "SELECT * FROM stock_compta_rapports WHERE id_entreprise = ? ORDER BY date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_entreprise]);
        response(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) response(false, null, 'ID manquant');
        $sql = "SELECT * FROM stock_compta_rapports WHERE id_rapport = ? AND id_entreprise = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $id_entreprise]);
        response(true, $stmt->fetch(PDO::FETCH_ASSOC));
        break;
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $sql = "INSERT INTO stock_compta_rapports (type_rapport, periode, donnees, id_entreprise) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $data['type_rapport'] ?? '',
            $data['periode'] ?? '',
            $data['donnees'] ?? '',
            $id_entreprise
        ]);
        response($ok, null, $ok ? 'Ajouté' : 'Erreur lors de l\'ajout');
        break;
    case 'delete':
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) response(false, null, 'ID manquant');
        $sql = "DELETE FROM stock_compta_rapports WHERE id_rapport = ? AND id_entreprise = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([$id, $id_entreprise]);
        response($ok, null, $ok ? 'Supprimé' : 'Erreur lors de la suppression');
        break;
    default:
        response(false, null, 'Action inconnue');
}
