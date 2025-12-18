<?php
/**
 * Configuration et gestion de la base de données
 * 
 * Ce fichier contient :
 * - Les constantes de configuration (DB_HOST, DB_NAME, etc.)
 * - La fonction createDatabaseConnection()
 * - La classe Database (optionnelle, utilise createDatabaseConnection)
 * 
 * IMPORTANT: Ne commitez jamais ce fichier avec des informations de production en clair.
 * Utilisez des variables d'environnement ou un fichier .env pour la production.
 * 
 * NOTE: Sur le serveur, ce fichier doit être déployé dans /htdocs/api-stock/config/database.php
 */

// =====================================================
// CONFIGURATION DE LA BASE DE DONNÉES
// =====================================================

if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'aliad2663340');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'aliad2663340');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'Stock2025@');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Méthodes de connexion à essayer (pour compatibilité avec différents environnements)
if (!defined('DB_CONNECTION_METHODS')) {
    define('DB_CONNECTION_METHODS', [
        ['host' => '127.0.0.1', 'socket' => null],
        ['host' => 'localhost', 'socket' => null],
        ['host' => 'localhost', 'socket' => '/var/run/mysqld/mysqld.sock'],
        ['host' => 'localhost', 'socket' => '/tmp/mysql.sock'],
        ['host' => 'mysql4202.lwspanel.com', 'socket' => null],
        ['host' => '213.255.195.35', 'socket' => null], // Host alternatif
    ]);
}

// =====================================================
// FONCTION DE CONNEXION
// =====================================================

/**
 * Fonction utilitaire pour créer une connexion PDO
 * Essaie plusieurs méthodes de connexion jusqu'à ce qu'une fonctionne
 * 
 * @return PDO Retourne l'instance PDO
 * @throws PDOException Si toutes les méthodes de connexion échouent
 */
function createDatabaseConnection() {
    $db_name = DB_NAME;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
    $db_charset = DB_CHARSET;
    $connectionMethods = DB_CONNECTION_METHODS;
    
    $bdd = null;
    $lastError = null;
    
    foreach ($connectionMethods as $method) {
        try {
            if ($method['socket']) {
                $dsn = "mysql:unix_socket={$method['socket']};dbname={$db_name};charset={$db_charset}";
            } else {
                $dsn = "mysql:host={$method['host']};dbname={$db_name};charset={$db_charset}";
            }
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $bdd = new PDO($dsn, $db_user, $db_pass, $options);
            break; // Connexion réussie, sortir de la boucle
        } catch (PDOException $e) {
            $lastError = $e;
            continue; // Essayer la méthode suivante
        }
    }
    
    if ($bdd === null) {
        throw new PDOException(
            'Erreur de connexion à la base de données: ' . 
            ($lastError ? $lastError->getMessage() : 'Aucune méthode de connexion n\'a fonctionné')
        );
    }
    
    return $bdd;
}

// =====================================================
// CLASSE DATABASE (OPTIONNELLE)
// =====================================================

/**
 * Classe Database - Wrapper autour de createDatabaseConnection()
 * Utilisée principalement par api_entreprise.php
 */
class Database {
    private $pdo;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
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

?>
