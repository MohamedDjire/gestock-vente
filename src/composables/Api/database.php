<?php

class Database {
    private $pdo;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $host = '213.255.195.35';
        $dbname = 'aliad2663340';
        $user = 'aliad2663340';
        $password = 'Stock2025@';

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données', 'error' => $e->getMessage()]);
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
