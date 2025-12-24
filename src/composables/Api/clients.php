    // DEBUG LOG pour comprendre le POST reçu
    file_put_contents(__DIR__.'/debug_clients.log', date('c')." TYPE: $type\nDATA: ".json_encode($data)."\n", FILE_APPEND);
<?php
// Fallback pour serveurs qui ne supportent pas PUT/DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['_method'])) {
    if ($_GET['_method'] === 'PUT') {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
    } elseif ($_GET['_method'] === 'DELETE') {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
    }
}
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
        $client = $stmt->fetch();
        if ($client && $client['type'] === 'entreprise') {
            $client['nom'] = $client['nom_entreprise'];
            $client['prenom'] = $client['nom_entreprise'];
        }
        echo json_encode($client);
    } else {
        $stmt = $pdo->query('SELECT c.*, u.nom AS nom_utilisateur, e.nom_entreprise 
                             FROM stock_clients c
                             JOIN stock_utilisateur u ON c.id_utilisateur = u.id_utilisateur
                             JOIN stock_entreprise e ON c.id_entreprise = e.id_entreprise
                             ORDER BY c.date_creation DESC');
        $clients = $stmt->fetchAll();
        foreach ($clients as &$client) {
            if ($client['type'] === 'entreprise') {
                $client['nom'] = $client['nom_entreprise'];
                $client['prenom'] = $client['nom_entreprise'];
            }
        }
        echo json_encode($clients);
    }
    exit;
}


// POST : ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Fallback : si JSON vide, tente parse_str (x-www-form-urlencoded)
    if (!$data) {
        parse_str(file_get_contents('php://input'), $data);
    }
    // Détection robuste du type de client
    $type = isset($data['type']) ? strtolower(trim($data['type'])) : '';
    // Si nom_entreprise est renseigné et nom/prenom sont vides, on force le type à entreprise
    if ($type !== 'particulier' && !empty($data['nom_entreprise']) && empty($data['nom']) && empty($data['prenom'])) {
        $type = 'entreprise';
    }
    // Pour une entreprise, on force nom et prenom à nom_entreprise (toujours, même si le front envoie nom/prenom présents ou vides)
    if ($type === 'entreprise') {
        $data['nom'] = isset($data['nom_entreprise']) ? $data['nom_entreprise'] : '';
        $data['prenom'] = isset($data['nom_entreprise']) ? $data['nom_entreprise'] : '';
    }

    if ($type === 'entreprise') {
        // Pour une entreprise, nom_entreprise et id_utilisateur obligatoires
        if (empty($data['nom_entreprise']) || empty($data['id_utilisateur'])) {
            echo json_encode(['error' => "Champ manquant : nom_entreprise ou id_utilisateur", 'data' => $data]);
            exit;
        }
        // Vérifier si l'entreprise existe, sinon la créer
        $stmtE = $pdo->prepare('SELECT id_entreprise FROM stock_entreprise WHERE nom_entreprise = ? LIMIT 1');
        $stmtE->execute([$data['nom_entreprise']]);
        $rowE = $stmtE->fetch();
        if ($rowE && isset($rowE['id_entreprise'])) {
            $id_entreprise = $rowE['id_entreprise'];
        } else {
            $stmtInsertE = $pdo->prepare('INSERT INTO stock_entreprise (nom_entreprise) VALUES (?)');
            $stmtInsertE->execute([$data['nom_entreprise']]);
            $id_entreprise = $pdo->lastInsertId();
        }
        $nomEntreprise = trim($data['nom_entreprise']);
        $stmt = $pdo->prepare('INSERT INTO stock_clients (nom, prenom, id_entreprise, id_utilisateur, email, telephone, adresse, statut, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $nomEntreprise, // nom = nom de l'entreprise
            $nomEntreprise, // prenom = nom de l'entreprise
            $id_entreprise,
            $data['id_utilisateur'],
            isset($data['email']) ? $data['email'] : null,
            isset($data['telephone']) ? $data['telephone'] : null,
            isset($data['adresse']) ? $data['adresse'] : null,
            isset($data['statut']) ? $data['statut'] : 'actif',
            'entreprise'
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    } else if ($type === 'particulier') {
        // Pour un particulier, nom et prénom obligatoires
        $required = ['nom','prenom','id_utilisateur'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo json_encode(['error' => "Champ manquant ou vide : $field", 'data' => $data]);
                exit;
            }
        }
        // Si une entreprise est sélectionnée, on l'associe
        $id_entreprise = isset($data['id_entreprise']) ? $data['id_entreprise'] : null;
        if (!empty($data['nom_entreprise'])) {
            $stmtE = $pdo->prepare('SELECT id_entreprise FROM stock_entreprise WHERE nom_entreprise = ? LIMIT 1');
            $stmtE->execute([$data['nom_entreprise']]);
            $rowE = $stmtE->fetch();
            if ($rowE && isset($rowE['id_entreprise'])) {
                $id_entreprise = $rowE['id_entreprise'];
            } else {
                $stmtInsertE = $pdo->prepare('INSERT INTO stock_entreprise (nom_entreprise) VALUES (?)');
                $stmtInsertE->execute([$data['nom_entreprise']]);
                $id_entreprise = $pdo->lastInsertId();
            }
        }
        $stmt = $pdo->prepare('INSERT INTO stock_clients (nom, prenom, id_entreprise, id_utilisateur, email, telephone, adresse, statut, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['nom'],
            $data['prenom'],
            $id_entreprise,
            $data['id_utilisateur'],
            isset($data['email']) ? $data['email'] : null,
            isset($data['telephone']) ? $data['telephone'] : null,
            isset($data['adresse']) ? $data['adresse'] : null,
            isset($data['statut']) ? $data['statut'] : 'actif',
            'particulier'
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    } else {
        echo json_encode(['error' => "Type de client inconnu ou non géré", 'data' => $data]);
        exit;
    }
}

// PUT : modification
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $raw = file_get_contents('php://input');
    error_log('PUT RAW INPUT: ' . $raw);
    $data = json_decode($raw, true);
    // Fallback : si JSON vide, tente parse_str (x-www-form-urlencoded)
    if (!$data) {
        parse_str($raw, $data);
        error_log('PUT PARSE_STR: ' . print_r($data, true));
    }
    if (!$data || !isset($data['type'])) {
        echo json_encode(['error' => 'Aucune donnée reçue ou format incorrect', 'raw' => $raw, 'data' => $data]);
        exit;
    }
    $type = $data['type'];
    if ($type === 'entreprise') {
        if (empty($data['nom_entreprise']) || empty($data['id_utilisateur']) || empty($data['id'])) {
            echo json_encode(['error' => 'Champs manquants pour entreprise', 'data' => $data]);
            exit;
        }
        // Vérifier/créer l'entreprise
        $stmtE = $pdo->prepare('SELECT id_entreprise FROM stock_entreprise WHERE nom_entreprise = ? LIMIT 1');
        $stmtE->execute([$data['nom_entreprise']]);
        $rowE = $stmtE->fetch();
        if ($rowE && isset($rowE['id_entreprise'])) {
            $id_entreprise = $rowE['id_entreprise'];
        } else {
            $stmtInsertE = $pdo->prepare('INSERT INTO stock_entreprise (nom_entreprise) VALUES (?)');
            $stmtInsertE->execute([$data['nom_entreprise']]);
            $id_entreprise = $pdo->lastInsertId();
        }
        $stmt = $pdo->prepare('UPDATE stock_clients SET nom=?, prenom=?, id_entreprise=?, id_utilisateur=?, email=?, telephone=?, adresse=?, statut=?, type=? WHERE id=?');
        $stmt->execute([
            $data['nom_entreprise'], // nom = nom de l'entreprise
            $data['nom_entreprise'], // prenom = nom de l'entreprise
            $id_entreprise,
            $data['id_utilisateur'],
            isset($data['email']) ? $data['email'] : null,
            isset($data['telephone']) ? $data['telephone'] : null,
            isset($data['adresse']) ? $data['adresse'] : null,
            isset($data['statut']) ? $data['statut'] : 'actif',
            'entreprise',
            $data['id']
        ]);
        echo json_encode([
            'success' => true,
            'rowCount' => $stmt->rowCount(),
            'data' => $data
        ]);
        exit;
    } else {
        // particulier
        $required = ['nom','prenom','id_utilisateur','id'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo json_encode(['error' => "Champ manquant : $field", 'data' => $data]);
                exit;
            }
        }
        // Gestion entreprise associée si renseignée
        $id_entreprise = isset($data['id_entreprise']) ? $data['id_entreprise'] : null;
        if (!empty($data['nom_entreprise'])) {
            $stmtE = $pdo->prepare('SELECT id_entreprise FROM stock_entreprise WHERE nom_entreprise = ? LIMIT 1');
            $stmtE->execute([$data['nom_entreprise']]);
            $rowE = $stmtE->fetch();
            if ($rowE && isset($rowE['id_entreprise'])) {
                $id_entreprise = $rowE['id_entreprise'];
            } else {
                $stmtInsertE = $pdo->prepare('INSERT INTO stock_entreprise (nom_entreprise) VALUES (?)');
                $stmtInsertE->execute([$data['nom_entreprise']]);
                $id_entreprise = $pdo->lastInsertId();
            }
        }
        $stmt = $pdo->prepare('UPDATE stock_clients SET nom=?, prenom=?, id_entreprise=?, id_utilisateur=?, email=?, telephone=?, adresse=?, statut=?, type=? WHERE id=?');
        $stmt->execute([
            $data['nom'],
            $data['prenom'],
            $id_entreprise,
            $data['id_utilisateur'],
            isset($data['email']) ? $data['email'] : null,
            isset($data['telephone']) ? $data['telephone'] : null,
            isset($data['adresse']) ? $data['adresse'] : null,
            isset($data['statut']) ? $data['statut'] : 'actif',
            'particulier',
            $data['id']
        ]);
        echo json_encode([
            'success' => true,
            'rowCount' => $stmt->rowCount(),
            'data' => $data
        ]);
        exit;
    }
}


// DELETE : suppression
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data) {
        parse_str($raw, $data);
    }
    // Accepte aussi l'id via l'URL (ex: /clients.php?id=123)
    $id = null;
    if (isset($data['id']) && !empty($data['id'])) {
        $id = $data['id'];
    } elseif (isset($_GET['id']) && !empty($_GET['id'])) {
        $id = $_GET['id'];
    }
    if (!$id) {
        echo json_encode(['error' => 'ID manquant pour suppression', 'raw' => $raw, 'data' => $data, 'get' => $_GET]);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM stock_clients WHERE id=?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'rowCount' => $stmt->rowCount(), 'id' => $id]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
?>