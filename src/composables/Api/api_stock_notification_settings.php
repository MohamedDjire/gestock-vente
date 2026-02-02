<?php
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM stock_notification_settings ORDER BY id DESC LIMIT 1";
    $res = $bdd->query($sql)->fetch(PDO::FETCH_ASSOC);
    echo json_encode($res ?: []);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $bdd->exec("DELETE FROM stock_notification_settings");
    $stmt = $bdd->prepare("INSERT INTO stock_notification_settings (email_admin, telephone_admin, email_active, sms_active, notifier_vente, notifier_paiement, notifier_stock_faible, notifier_objectif_vente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['email_admin'] ?? '',
        $data['telephone_admin'] ?? '',
        !empty($data['email_active']) ? 1 : 0,
        !empty($data['sms_active']) ? 1 : 0,
        !empty($data['notifier_vente']) ? 1 : 0,
        !empty($data['notifier_paiement']) ? 1 : 0,
        !empty($data['notifier_stock_faible']) ? 1 : 0,
        !empty($data['notifier_objectif_vente']) ? 1 : 0
    ]);
    echo json_encode(["success" => true]);
    exit;
}
