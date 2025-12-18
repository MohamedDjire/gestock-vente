<?php
/**
 * Classe Database - Gestion de la connexion à la base de données
 * Utilise config_database.php pour les paramètres de connexion
 */

// database.php est dans config/, donc config_database.php est dans le répertoire parent
require_once __DIR__ . '/../config_database.php';

class Database {
    private $pdo;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            // Utiliser la fonction utilitaire de config_database.php
            $this->pdo = createDatabaseConnection();
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erreur de connexion à la base de données', 
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function getPdo() {
        return $this->pdo;
    }
}

// Usage example
// $database = new Database();
// $pdo = $database->getPdo();

?>
