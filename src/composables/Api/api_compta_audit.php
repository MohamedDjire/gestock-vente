
<?php
// Diagnostic tout début de script
file_put_contents(__DIR__.'/audit_debug.log', "[START] " . date('c') . "\n", FILE_APPEND);

// Inclusion et diagnostic database.php
$dbPath = __DIR__ . '/config/database.php';

if (!file_exists($dbPath)) $dbPath = dirname(__DIR__) . '/api/config/database.php';
require_once $dbPath;
file_put_contents(__DIR__.'/audit_debug.log', "[AFTER require database.php] " . date('c') . "\n", FILE_APPEND);
if (!function_exists('createDatabaseConnection')) {
    echo json_encode([
        'success'=>false,
        'message'=>'database.php non trouvé ou fonction absente',
        'dbPath'=>$dbPath,
        'file'=>__FILE__,
        'line'=>__LINE__,
        'included_files'=>get_included_files()
    ]);
    file_put_contents(__DIR__.'/audit_debug.log', "[ERREUR: PAS DE FONCTION] " . date('c') . "\n", FILE_APPEND);
    exit;
}
file_put_contents(__DIR__.'/audit_debug.log', "[FONCTION OK] " . date('c') . "\n", FILE_APPEND);
// Diagnostic supplémentaire : la fonction existe, on continue
try {
    $pdo = createDatabaseConnection();
    file_put_contents(__DIR__.'/audit_debug.log', "[PDO OBJET] " . (is_object($pdo) ? get_class($pdo) : gettype($pdo)) . "\n", FILE_APPEND);
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
        file_put_contents(__DIR__.'/audit_debug.log', "[ERREUR: PDO NON VALIDE] " . date('c') . "\n", FILE_APPEND);
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
    file_put_contents(__DIR__.'/audit_debug.log', "[ERREUR: EXCEPTION PDO] " . date('c') . "\n", FILE_APPEND);
    exit;
}
file_put_contents(__DIR__.'/audit_debug.log', "[APRES CONNEXION PDO] " . date('c') . "\n", FILE_APPEND);
// CORS universel
// CORS universel
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// API pour la gestion de l'audit (stock_compta_audit)
require_once 'config.php';
require_once __DIR__ . '/functions/middleware_auth.php';
header('Content-Type: application/json');


$action = $_GET['action'] ?? $_POST['action'] ?? null;
$id_entreprise = $_GET['id_entreprise'] ?? $_POST['id_entreprise'] ?? null;

// Diagnostic intermédiaire : vérifier $pdo juste avant usage
if (!isset($pdo) || !$pdo || !($pdo instanceof PDO)) {
    echo json_encode([
        'success' => false,
        'message' => 'Diagnostic $pdo avant usage SQL',
        'pdo_type' => isset($pdo) ? gettype($pdo) : 'non défini',
        'pdo_dump' => isset($pdo) ? print_r($pdo, true) : 'non défini',
        'file' => __FILE__,
        'line' => __LINE__
    ]);
    exit;
}

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
        $sql = "SELECT * FROM stock_compta_audit WHERE id_entreprise = ? ORDER BY date_action DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_entreprise]);
        response(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) response(false, null, 'ID manquant');
        $sql = "SELECT * FROM stock_compta_audit WHERE id_audit = ? AND id_entreprise = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $id_entreprise]);
        response(true, $stmt->fetch(PDO::FETCH_ASSOC));
        break;
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $sql = "INSERT INTO stock_compta_audit (action, user, details, id_entreprise) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $data['action'] ?? '',
            $data['user'] ?? '',
            $data['details'] ?? '',
            $id_entreprise
        ]);
        response($ok, null, $ok ? 'Ajouté' : 'Erreur lors de l\'ajout');
        break;
    default:
        response(false, null, 'Action inconnue');
}
