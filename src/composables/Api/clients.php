<?php
// Affichage des erreurs PHP pour le debug (à retirer en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoriser CORS pour le développement local (toujours AVANT tout output)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Content-Type: application/json');
    http_response_code(200);
    exit;
}
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions/middleware_auth.php';
$pdo = createDatabaseConnection();



// GET : liste ou détail
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT c.*, u.nom AS nom_utilisateur, e.nom_entreprise 
                               FROM stock_clients c
                               JOIN stock_utilisateur u ON c.id_utilisateur = u.id_utilisateur
                               JOIN stock_entreprise e ON c.id_entreprise = e.id_entreprise
                               WHERE c.id = ?');
        $stmt->execute([$_GET['id']]);
        echo json_encode($stmt->fetch());
    } else {
        $stmt = $pdo->query('SELECT c.*, u.nom AS nom_utilisateur, e.nom_entreprise 
                             FROM stock_clients c
                             JOIN stock_utilisateur u ON c.id_utilisateur = u.id_utilisateur
                             JOIN stock_entreprise e ON c.id_entreprise = e.id_entreprise
                             ORDER BY c.date_creation DESC');
        echo json_encode($stmt->fetchAll());
    }
    exit;
}

// POST : ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO stock_clients (nom, prenom, id_entreprise, id_utilisateur, email, telephone, adresse, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['nom'],
        $data['prenom'],
        $data['id_entreprise'],
        $data['id_utilisateur'],
        $data['email'],
        $data['telephone'],
        $data['adresse'],
        $data['statut'] ?? 'actif'
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// PUT : modification
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $data);
    $stmt = $pdo->prepare('UPDATE stock_clients SET nom=?, prenom=?, id_entreprise=?, id_utilisateur=?, email=?, telephone=?, adresse=?, statut=? WHERE id=?');
    $stmt->execute([
        $data['nom'],
        $data['prenom'],
        $data['id_entreprise'],
        $data['id_utilisateur'],
        $data['email'],
        $data['telephone'],
        $data['adresse'],
        $data['statut'],
        $data['id']
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// DELETE : suppression
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $data);
    $stmt = $pdo->prepare('DELETE FROM stock_clients WHERE id=?');
    $stmt->execute([$data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
?>