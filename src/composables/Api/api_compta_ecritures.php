
<?php
file_put_contents(__DIR__.'/debug_php.log', "[".date('c')."] API_COMPTA_ECRITURES.PHP EXECUTED\n", FILE_APPEND);
$dbPath = __DIR__ . '/config/database.php';
if (!file_exists($dbPath)) $dbPath = dirname(__DIR__) . '/api/config/database.php';
require_once $dbPath;
if (!function_exists('createDatabaseConnection')) {
    echo json_encode([
        'success'=>false,
        'message'=>'database.php non trouvé ou fonction absente',
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
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success'=>false,
        'message'=>'Erreur PDO: ' . $e->getMessage(),
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
// API pour la gestion des écritures comptables (compta_ecritures)
require_once 'config.php';
require_once __DIR__ . '/functions/middleware_auth.php';



try {
    $currentUser = authenticateAndAuthorize($pdo);
    $id_entreprise = $currentUser['enterprise_id'];
} catch (Exception $e) {
    response(false, null, 'Authentification requise ou token invalide : ' . $e->getMessage());
}
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? ($json['action'] ?? null);
if (!$action) {
    response(false, null, "Paramètre 'action' manquant ou vide", [
        'get' => $_GET,
        'post' => $_POST,
        'json' => $json,
        'raw' => $raw
    ]);
}
$raw = file_get_contents('php://input');
$json = $raw ? json_decode($raw, true) : null;
file_put_contents(__DIR__.'/debug_ecriture.log', "[".date('c')."] RAW: ".$raw."\n", FILE_APPEND);
file_put_contents(__DIR__.'/debug_ecriture.log', "[".date('c')."] JSON: ".print_r($json, true)."\n", FILE_APPEND);


function response($success, $data = null, $message = '', $debug = null) {
    global $json, $raw;
    $out = [
        'success' => $success,
        'data' => $data,
        'message' => $message
    ];
    // Ajout automatique des infos de debug si id_entreprise manquant
    if ($message === 'ID entreprise manquant' || $message === 'ID entreprise manquant (debug)') {
        $out['json_recu'] = $json;
        $out['raw'] = $raw;
        $out['get'] = $_GET;
        $out['post'] = $_POST;
    }
    if ($debug !== null) {
        $out = array_merge($out, $debug);
    }
    echo json_encode($out);
    exit;
}



switch ($action) {
    case 'all':
        // Récupérer toutes les écritures
        $sql = "SELECT * FROM stock_compta_ecritures WHERE id_entreprise = ? ORDER BY date_ecriture DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_entreprise]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        response(true, $result, 'Liste des écritures');
        break;
    case 'add':
    case 'create':
        // Ajouter une écriture
        $data = $json ?? $_POST;
        // On ignore tout id_entreprise venant du client pour la sécurité
        if (isset($data['id_entreprise'])) unset($data['id_entreprise']);
        $sql = "INSERT INTO stock_compta_ecritures (date_ecriture, type_ecriture, montant, user, categorie, moyen_paiement, statut, reference, piece_jointe, commentaire, details, id_entreprise) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $data['date_ecriture'] ?? date('Y-m-d'),
            $data['type_ecriture'] ?? '',
            $data['montant'] ?? 0,
            $data['user'] ?? '',
            $data['categorie'] ?? '',
            $data['moyen_paiement'] ?? '',
            $data['statut'] ?? 'en attente',
            $data['reference'] ?? '',
            $data['piece_jointe'] ?? '',
            $data['commentaire'] ?? '',
            $data['details'] ?? '',
            $id_entreprise
        ]);
        response($ok, null, $ok ? 'Ajouté' : 'Erreur lors de l\'ajout');
        break;
    case 'update':
        // Modifier une écriture
        $id = $_GET['id'] ?? $_POST['id'] ?? ($json['id'] ?? null);
        if (!$id) response(false, null, 'ID manquant');
        $data = $json ?? $_POST;
        $sql = "UPDATE stock_compta_ecritures SET date_ecriture=?, type_ecriture=?, montant=?, user=?, categorie=?, moyen_paiement=?, statut=?, reference=?, piece_jointe=?, commentaire=?, details=? WHERE id_compta=? AND id_entreprise=?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $data['date_ecriture'] ?? date('Y-m-d'),
            $data['type_ecriture'] ?? '',
            $data['montant'] ?? 0,
            $data['user'] ?? '',
            $data['categorie'] ?? '',
            $data['moyen_paiement'] ?? '',
            $data['statut'] ?? 'en attente',
            $data['reference'] ?? '',
            $data['piece_jointe'] ?? '',
            $data['commentaire'] ?? '',
            $data['details'] ?? '',
            $id,
            $id_entreprise
        ]);
        response($ok, null, $ok ? 'Modifié' : 'Erreur lors de la modification');
        break;
    case 'delete':
        // Supprimer une écriture
        $id = $_GET['id'] ?? $_POST['id'] ?? ($json['id'] ?? null);
        if (!$id) response(false, null, 'ID manquant');
        $sql = "DELETE FROM stock_compta_ecritures WHERE id_compta = ? AND id_entreprise = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([$id, $id_entreprise]);
        response($ok, null, $ok ? 'Supprimé' : 'Erreur lors de la suppression');
        break;
    default:
        response(false, null, 'Action inconnue');
}
