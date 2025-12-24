<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// src/composables/api/api_journal.php
// API pour journaliser et récupérer les mouvements de l'application
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';


$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Récupérer l'historique complet
    $sql = "SELECT * FROM journal ORDER BY date DESC LIMIT 200";
    $stmt = $bdd->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

if ($method === 'POST') {
    // Ajouter un mouvement
    $data = json_decode(file_get_contents('php://input'), true);
    $sql = "INSERT INTO journal (date, user, action, details) VALUES (NOW(), :user, :action, :details)";
    $stmt = $bdd->prepare($sql);
    $stmt->bindValue(':user', $data['user'] ?? 'inconnu', PDO::PARAM_STR);
    $stmt->bindValue(':action', $data['action'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':details', $data['details'] ?? '', PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Méthode non supportée']);
exit;
