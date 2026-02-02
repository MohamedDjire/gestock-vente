<?php
require_once __DIR__ . '/cors.php';
/**
 * Script temporaire pour réinitialiser le mot de passe d'un utilisateur
 * Endpoint: /api-stock/reset_password.php
 */
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration base de données
// Sur le serveur: config/database.php
require_once __DIR__ . '/config/database.php';

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

$data = json_decode(file_get_contents("php://input"), true);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée. Utilisez POST.");
    }
    
    // Vérifier les données requises
    if (!isset($data['email']) || !isset($data['new_password'])) {
        throw new Exception("Email et nouveau mot de passe requis");
    }
    
    $email = $data['email'];
    $newPassword = $data['new_password'];
    
    // Vérifier que l'utilisateur existe
    $stmt = $bdd->prepare("SELECT id_utilisateur FROM stock_utilisateur WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Aucun utilisateur trouvé avec cet email");
    }
    
    // Hasher le nouveau mot de passe
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Mettre à jour le mot de passe
    $updateStmt = $bdd->prepare("UPDATE stock_utilisateur SET mot_de_passe = :password WHERE email = :email");
    $updateStmt->execute([
        'password' => $hashedPassword,
        'email' => $email
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mot de passe réinitialisé avec succès',
        'email' => $email,
        'new_password' => $newPassword // À retirer en production
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>








