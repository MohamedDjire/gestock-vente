<?php
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');
// API de gestion des écritures comptables - isolée par entreprise
// Endpoint: /api_compta_ecritures.php
require_once 'config.php';
$dbPath = __DIR__ . '/config/database.php';
if (!file_exists($dbPath)) $dbPath = dirname(__DIR__) . '/api/config/database.php';
require_once $dbPath;
if (!function_exists('createDatabaseConnection')) {
    echo json_encode([
        'success'=>false,
        'message'=>'database.php non trouvé ou fonction absente',
        'dbPath'=>$dbPath,
        'file' => __FILE__,
        'line' => __LINE__,
        'included_files'=>get_included_files()
    ]);
    exit;
}
try {
    $bdd = createDatabaseConnection();
    if (!$bdd || !($bdd instanceof PDO)) {
        echo json_encode([
            'success' => false,
            'message' => 'Connexion PDO échouée ou objet non valide',
            'dbPath' => $dbPath,
            'pdo_type' => gettype($bdd),
            'pdo_dump' => print_r($bdd, true),
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

// =====================================================
// AUTHENTIFICATION - chaque entreprise ne voit que ses écritures
// =====================================================
$middlewareFile = __DIR__ . '/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    $middlewareFile = __DIR__ . '/functions/middleware_auth.php';
}
if (!file_exists($middlewareFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Middleware d\'authentification introuvable']);
    exit;
}
require_once $middlewareFile;
if (!function_exists('authenticateAndAuthorize')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Authentification non disponible']);
    exit;
}
try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = (int)($currentUser['enterprise_id'] ?? $currentUser['user_enterprise_id'] ?? 0);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé', 'error' => $e->getMessage()]);
    exit;
}
if ($enterpriseId <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Entreprise non identifiée']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Champs de la table stock_compta_ecritures
$fields = [
  'date_ecriture', 'type_ecriture', 'montant', 'debit', 'credit', 'user', 'categorie', 'moyen_paiement', 'statut',
  'reference', 'piece_jointe', 'commentaire', 'details', 'id_entreprise', 'id_utilisateur', 'id_client', 'id_fournisseur', 'id_point_vente', 'nom_client'
];

switch ($method) {
  case 'GET':
    // Toujours filtrer par l'entreprise de l'utilisateur connecté (ignorer id_entreprise du client pour la sécurité)
    $sql = "SELECT * FROM stock_compta_ecritures WHERE id_entreprise = :id_entreprise ORDER BY date_ecriture DESC";
    $stmt = $bdd->prepare($sql);
    $stmt->bindParam(':id_entreprise', $enterpriseId, PDO::PARAM_INT);
    $stmt->execute();
    $ecritures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($ecritures);
    break;

  case 'POST':
    $data = json_decode(file_get_contents('php://input'), true);
    // Suppression via POST/action=delete - vérifier que l'écriture appartient à l'entreprise
    if (isset($data['action']) && $data['action'] === 'delete' && !empty($data['id_compta'])) {
      $id = (int)$data['id_compta'];
      $stmt = $bdd->prepare("DELETE FROM stock_compta_ecritures WHERE id_compta = :id_compta AND id_entreprise = :id_entreprise");
      $stmt->execute(['id_compta' => $id, 'id_entreprise' => $enterpriseId]);
      echo json_encode(['success' => true, 'deleted_id' => $id]);
      break;
    }
    // Forcer id_entreprise à l'entreprise de l'utilisateur connecté
    $data['id_entreprise'] = $enterpriseId;
    // Mapping automatique des champs frontend vers backend
    if (isset($data['date']) && empty($data['date_ecriture'])) {
      $data['date_ecriture'] = $data['date'];
    }
    if (isset($data['type']) && empty($data['type_ecriture'])) {
      $data['type_ecriture'] = $data['type'];
    }
    if (isset($data['url_piece_jointe']) && empty($data['piece_jointe'])) {
      $data['piece_jointe'] = $data['url_piece_jointe'];
    }
    // Valeurs par défaut pour les champs obligatoires non envoyés
    if (empty($data['statut'])) $data['statut'] = 'en attente';
    if (empty($data['debit'])) $data['debit'] = 0;
    if (empty($data['credit'])) $data['credit'] = 0;
    // Valeurs par défaut personnalisées pour référence, commentaire, moyen_paiement
    if (empty($data['reference'])) {
      $data['reference'] = 'VENTE-' . date('Ymd-His') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
    }
    if (empty($data['commentaire'])) {
      $data['commentaire'] = "Vente enregistrée automatiquement";
    }
    if (empty($data['moyen_paiement'])) {
      $data['moyen_paiement'] = 'espèces';
    }
    // Champs optionnels à NULL si non envoyés
    $optionals = ['user','categorie','commentaire','details','id_utilisateur','id_client','id_fournisseur','id_point_vente','nom_client'];
    foreach ($optionals as $opt) {
      if (!isset($data[$opt])) $data[$opt] = null;
    }
    $params = [];
    foreach ($fields as $f) {
      $val = isset($data[$f]) ? $data[$f] : null;
      if (is_array($val)) {
        $val = json_encode($val);
      }
      $params[$f] = $val;
    }
    $sql = "INSERT INTO stock_compta_ecritures (" . implode(", ", array_keys($params)) . ") VALUES (" . implode(", ", array_map(function($f){return ":$f";}, array_keys($params))) . ")";
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'id' => $bdd->lastInsertId()]);
    break;

  case 'PUT':
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id_compta'])) {
      echo json_encode(['error' => 'id_compta manquant']);
      exit;
    }
    $data['id_entreprise'] = $enterpriseId;
    $params = [];
    foreach ($fields as $f) {
      $params[$f] = isset($data[$f]) ? $data[$f] : null;
    }
    $params['id_compta'] = $data['id_compta'];
    $sql = "UPDATE stock_compta_ecritures SET ";
    $sql .= implode(", ", array_map(function($f){return "$f=:$f";}, array_keys($params)));
    $sql .= " WHERE id_compta=:id_compta AND id_entreprise=:id_entreprise";
    $params['id_entreprise'] = $enterpriseId;
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true]);
    break;

  case 'DELETE':
    $id = $_GET['id_compta'] ?? null;
    if (!$id) {
      $data = json_decode(file_get_contents('php://input'), true);
      $id = $data['id_compta'] ?? null;
    }
    if (!$id) {
      echo json_encode(['error' => 'id_compta manquant']);
      exit;
    }
    $sql = "DELETE FROM stock_compta_ecritures WHERE id_compta = :id_compta AND id_entreprise = :id_entreprise";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_compta' => (int)$id, 'id_entreprise' => $enterpriseId]);
    echo json_encode(['success' => true]);
    break;

  default:
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}
