<?php
/**
 * API Vente - Gestion des ventes
 * Endpoint: /api-stock/api_vente.php
 */

// Activer la gestion des erreurs et définir les headers CORS AVANT TOUT
// Désactiver l'affichage des erreurs pour éviter qu'elles polluent la réponse JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Headers CORS - DOIT être défini avant toute sortie
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Répondre immédiatement aux requêtes OPTIONS (préflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// =====================================================
// CONFIGURATION BASE DE DONNÉES
// =====================================================
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

// =====================================================
// MIDDLEWARE AUTHENTIFICATION
// =====================================================
$middlewareFile = __DIR__ . '/functions/middleware_auth.php';
if (!file_exists($middlewareFile)) {
    $middlewareFile = __DIR__ . '/middleware_auth.php';
}

if (!file_exists($middlewareFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fichier middleware_auth.php introuvable',
        'error' => 'Le fichier doit être déployé'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $middlewareFile;

if (!function_exists('authenticateAndAuthorize')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fonction authenticateAndAuthorize() introuvable',
        'error' => 'Vérifiez que le fichier middleware_auth.php contient cette fonction'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Authentifier l'utilisateur
try {
    $currentUser = authenticateAndAuthorize($bdd);
    $enterpriseId = $currentUser['enterprise_id'];
    $userId = $currentUser['id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

/**
 * Créer une vente
 */
function createVente($bdd, $data, $enterpriseId, $userId, $currentUser = null) {
    // Validation
    if (empty($data['produits']) || !is_array($data['produits']) || count($data['produits']) === 0) {
        throw new Exception("Au moins un produit est requis pour la vente");
    }
    
    if (empty($data['id_point_vente'])) {
        throw new Exception("Le point de vente est requis");
    }
    
    $bdd->beginTransaction();
    
    try {
        $idPointVente = (int)$data['id_point_vente'];
        $idClient = isset($data['id_client']) ? (int)$data['id_client'] : null;
        $notes = $data['notes'] ?? null;
        $montantTotal = 0;
        $ventesCreees = [];
        
        // Vérifier que le point de vente appartient à l'entreprise
        $stmt = $bdd->prepare("SELECT id_point_vente FROM stock_point_vente WHERE id_point_vente = :id AND id_entreprise = :id_entreprise");
        $stmt->execute(['id' => $idPointVente, 'id_entreprise' => $enterpriseId]);
        if (!$stmt->fetch()) {
            throw new Exception("Point de vente non trouvé", 404);
        }
        
        // Traiter chaque produit
        foreach ($data['produits'] as $produit) {
            $idProduit = (int)$produit['id_produit'];
            $quantite = (int)$produit['quantite'];
            $prixUnitaire = (float)$produit['prix_unitaire'];
            
            if ($quantite <= 0) {
                throw new Exception("La quantité doit être supérieure à 0");
            }
            
            // Vérifier que le produit existe et appartient à l'entreprise
            $stmt = $bdd->prepare("
                SELECT id_produit, nom, prix_vente, quantite_stock 
                FROM stock_produit 
                WHERE id_produit = :id AND id_entreprise = :id_entreprise AND actif = 1
            ");
            $stmt->execute(['id' => $idProduit, 'id_entreprise' => $enterpriseId]);
            $produitData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$produitData) {
                throw new Exception("Produit non trouvé ou inactif: ID {$idProduit}");
            }
            
            // Vérifier le stock disponible
            if ($produitData['quantite_stock'] < $quantite) {
                throw new Exception("Stock insuffisant pour le produit '{$produitData['nom']}'. Stock disponible: {$produitData['quantite_stock']}");
            }
            
            // Utiliser le prix fourni ou le prix de vente du produit
            if ($prixUnitaire <= 0) {
                $prixUnitaire = (float)$produitData['prix_vente'];
            }
            
            $montantTotalProduit = $prixUnitaire * $quantite;
            $montantTotal += $montantTotalProduit;
            
            // Créer la vente
            $stmt = $bdd->prepare("
                INSERT INTO stock_vente (
                    id_point_vente, id_produit, quantite, prix_unitaire, montant_total,
                    type_vente, statut, id_client, id_user, id_entreprise, notes
                ) VALUES (
                    :id_point_vente, :id_produit, :quantite, :prix_unitaire, :montant_total,
                    'vente', 'en_cours', :id_client, :id_user, :id_entreprise, :notes
                )
            ");
            
            $stmt->execute([
                'id_point_vente' => $idPointVente,
                'id_produit' => $idProduit,
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire,
                'montant_total' => $montantTotalProduit,
                'id_client' => $idClient,
                'id_user' => $userId,
                'id_entreprise' => $enterpriseId,
                'notes' => $notes
            ]);
            
            $idVente = $bdd->lastInsertId();
            
            // Créer une sortie de stock
            // NOTE: Le trigger 'trg_after_sortie_stock' déduira automatiquement le stock
            // Ne pas déduire manuellement pour éviter la double déduction
            $sortieStmt = $bdd->prepare("
                INSERT INTO stock_sortie (
                    id_produit, quantite, motif, id_user, id_entreprise, type_sortie
                ) VALUES (
                    :id_produit, :quantite, 'Vente', :id_user, :id_entreprise, 'vente'
                )
            ");
            $sortieStmt->execute([
                'id_produit' => $idProduit,
                'quantite' => $quantite,
                'id_user' => $userId,
                'id_entreprise' => $enterpriseId
            ]);
            
            // Récupérer les détails de la vente créée
            $getStmt = $bdd->prepare("
                SELECT v.*, p.nom AS produit_nom, p.code_produit
                FROM stock_vente v
                INNER JOIN stock_produit p ON v.id_produit = p.id_produit
                WHERE v.id_vente = :id
            ");
            $getStmt->execute(['id' => $idVente]);
            $ventesCreees[] = $getStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Appliquer la remise globale si fournie
        $remise = isset($data['remise']) ? (float)$data['remise'] : 0;
        $montantTotalFinal = $montantTotal - $remise;

        // Enregistrer une écriture comptable automatique
        try {
            $sqlEcriture = "INSERT INTO stock_compta_ecritures (date_ecriture, type_ecriture, montant, user, categorie, moyen_paiement, statut, reference, piece_jointe, commentaire, details, id_entreprise) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtEcriture = $bdd->prepare($sqlEcriture);
            $stmtEcriture->execute([
                date('Y-m-d'),
                'vente',
                $montantTotalFinal,
                $userName,
                'Vente',
                $data['moyen_paiement'] ?? '',
                'validée',
                '',
                '',
                '',
                $details,
                $enterpriseId
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors de l'insertion de l'écriture comptable vente : " . $e->getMessage());
        }

        // Enregistrer dans le journal
        try {
            // Récupérer le nom complet de l'utilisateur
            $userName = 'Utilisateur';
            if ($currentUser) {
                $nom = trim(($currentUser['nom'] ?? '') . ' ' . ($currentUser['prenom'] ?? ''));
                $userName = !empty($nom) ? $nom : ($currentUser['email'] ?? 'Utilisateur');
            } else {
                // Récupérer le nom de l'utilisateur depuis la base
                $stmtUser = $bdd->prepare("SELECT nom, prenom, email FROM stock_utilisateur WHERE id_utilisateur = :id");
                $stmtUser->execute(['id' => $userId]);
                $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                if ($userData) {
                    $nom = trim(($userData['nom'] ?? '') . ' ' . ($userData['prenom'] ?? ''));
                    $userName = !empty($nom) ? $nom : ($userData['email'] ?? 'Utilisateur');
                }
            }
            
            // Récupérer le nom du point de vente
            $pointVenteName = 'Point de vente inconnu';
            $stmtPv = $bdd->prepare("SELECT nom_point_vente FROM stock_point_vente WHERE id_point_vente = :id");
            $stmtPv->execute(['id' => $idPointVente]);
            $pvData = $stmtPv->fetch(PDO::FETCH_ASSOC);
            if ($pvData && !empty($pvData['nom_point_vente'])) {
                $pointVenteName = $pvData['nom_point_vente'];
            }
            
            // Créer une liste détaillée des produits vendus
            $produitsList = [];
            foreach ($ventesCreees as $vente) {
                $produitsList[] = sprintf(
                    "%s (x%d)",
                    $vente['produit_nom'] ?? 'Produit',
                    $vente['quantite'] ?? 0
                );
            }
            
            // Format cohérent avec les autres actions du journal
            $details = sprintf(
                "Point de vente: %s | Produits: %s | Total: %s FCFA%s",
                $pointVenteName,
                implode(', ', array_slice($produitsList, 0, 5)) . (count($produitsList) > 5 ? sprintf(' et %d autre(s)', count($produitsList) - 5) : ''),
                number_format($montantTotalFinal, 0, ',', ' '),
                $remise > 0 ? sprintf(" | Remise: %s FCFA", number_format($remise, 0, ',', ' ')) : ""
            );
            
            // Vérifier si la table stock_journal existe (essayer plusieurs noms possibles)
            $tableNames = ['stock_journal', 'journal'];
            $tableExists = false;
            $tableName = null;
            
            foreach ($tableNames as $table) {
                $checkTable = $bdd->query("SHOW TABLES LIKE '{$table}'");
                if ($checkTable->rowCount() > 0) {
                    $tableExists = true;
                    $tableName = $table;
                    break;
                }
            }
            
            if ($tableExists) {
                // Vérifier si la colonne id_entreprise existe
                $checkColumn = $bdd->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'id_entreprise'");
                $hasEnterpriseColumn = $checkColumn->rowCount() > 0;
                
                if ($hasEnterpriseColumn) {
                    // Insérer avec id_entreprise
                    $journalStmt = $bdd->prepare("
                        INSERT INTO `{$tableName}` (date, user, action, details, id_entreprise)
                        VALUES (NOW(), :user, 'Vente', :details, :id_entreprise)
                    ");
                    $result = $journalStmt->execute([
                        'user' => $userName,
                        'details' => $details,
                        'id_entreprise' => $enterpriseId
                    ]);
                    if (!$result) {
                        error_log("Erreur SQL lors de l'insertion dans le journal: " . implode(', ', $journalStmt->errorInfo()));
                    }
                } else {
                    // Fallback : insérer sans id_entreprise (pour compatibilité)
                    $journalStmt = $bdd->prepare("
                        INSERT INTO `{$tableName}` (date, user, action, details)
                        VALUES (NOW(), :user, 'Vente', :details)
                    ");
                    $result = $journalStmt->execute([
                        'user' => $userName,
                        'details' => $details
                    ]);
                    if (!$result) {
                        error_log("Erreur SQL lors de l'insertion dans le journal: " . implode(', ', $journalStmt->errorInfo()));
                    }
                }
            } else {
                error_log("Table stock_journal ou journal n'existe pas");
            }
        } catch (Exception $e) {
            // Ne pas faire échouer la vente si l'enregistrement du journal échoue
            error_log("Erreur lors de l'enregistrement dans le journal: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
        
        // Enregistrer le reçu automatiquement
        try {
            // S'assurer que pointVenteName est défini (utiliser celui du journal si disponible)
            if (empty($pointVenteName)) {
                $stmtPv2 = $bdd->prepare("SELECT nom_point_vente FROM stock_point_vente WHERE id_point_vente = :id");
                $stmtPv2->execute(['id' => $idPointVente]);
                $pvData2 = $stmtPv2->fetch(PDO::FETCH_ASSOC);
                $pointVenteName = $pvData2 && !empty($pvData2['nom_point_vente']) ? $pvData2['nom_point_vente'] : 'Point de vente inconnu';
            }
            
            // Préparer les données du reçu
            $receiptProducts = [];
            foreach ($ventesCreees as $vente) {
                $receiptProducts[] = [
                    'nom' => $vente['produit_nom'] ?? 'Produit',
                    'code' => $vente['code_produit'] ?? '',
                    'quantite' => $vente['quantite'] ?? 0,
                    'prix_unitaire' => $vente['prix_unitaire'] ?? 0,
                    'sous_total' => $vente['montant_total'] ?? 0
                ];
            }
            
            $receiptData = [
                'id_vente' => $ventesCreees[0]['id_vente'] ?? null,
                'date' => date('Y-m-d H:i:s'),
                'point_vente' => $pointVenteName,
                'produits' => $receiptProducts,
                'nombre_articles' => count($ventesCreees),
                'sous_total' => $montantTotal,
                'remise' => $remise,
                'total' => $montantTotalFinal
            ];
            
            // Vérifier si la table stock_receipt existe
            $checkReceiptTable = $bdd->query("SHOW TABLES LIKE 'stock_receipt'");
            if ($checkReceiptTable->rowCount() > 0) {
                $receiptStmt = $bdd->prepare("
                    INSERT INTO stock_receipt (
                        id_vente, id_point_vente, id_entreprise, id_user,
                        date_vente, point_vente_nom, nombre_articles,
                        sous_total, remise, total, produits_json, receipt_data
                    ) VALUES (
                        :id_vente, :id_point_vente, :id_entreprise, :id_user,
                        NOW(), :point_vente_nom, :nombre_articles,
                        :sous_total, :remise, :total, :produits_json, :receipt_data
                    )
                ");
                $receiptStmt->execute([
                    'id_vente' => $ventesCreees[0]['id_vente'] ?? null,
                    'id_point_vente' => $idPointVente,
                    'id_entreprise' => $enterpriseId,
                    'id_user' => $userId,
                    'point_vente_nom' => $pointVenteName,
                    'nombre_articles' => count($ventesCreees),
                    'sous_total' => $montantTotal,
                    'remise' => $remise,
                    'total' => $montantTotalFinal,
                    'produits_json' => json_encode($receiptProducts, JSON_UNESCAPED_UNICODE),
                    'receipt_data' => json_encode($receiptData, JSON_UNESCAPED_UNICODE)
                ]);
            }
        } catch (Exception $e) {
            // Ne pas faire échouer la vente si l'enregistrement du reçu échoue
            error_log("Erreur lors de l'enregistrement du reçu: " . $e->getMessage());
        }
        
        $bdd->commit();
        
        return [
            'id_vente' => $ventesCreees[0]['id_vente'] ?? null,
            'ventes' => $ventesCreees,
            'montant_total' => $montantTotalFinal,
            'montant_sous_total' => $montantTotal,
            'remise' => $remise,
            'nombre_produits' => count($ventesCreees)
        ];
        
    } catch (Exception $e) {
        $bdd->rollBack();
        throw $e;
    }
}

/**
 * Récupérer les ventes
 */
function getVentes($bdd, $enterpriseId, $filters = []) {
    $where = ["v.id_entreprise = :id_entreprise"];
    $params = ['id_entreprise' => $enterpriseId];
    
    if (!empty($filters['date_debut'])) {
        $where[] = "DATE(v.date_vente) >= :date_debut";
        $params['date_debut'] = $filters['date_debut'];
    }
    
    if (!empty($filters['date_fin'])) {
        $where[] = "DATE(v.date_vente) <= :date_fin";
        $params['date_fin'] = $filters['date_fin'];
    }
    
    if (!empty($filters['id_point_vente'])) {
        $where[] = "v.id_point_vente = :id_point_vente";
        $params['id_point_vente'] = (int)$filters['id_point_vente'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Vérifier si la table stock_clients existe (avec un "s")
    $clientTableExists = false;
    try {
        $checkTable = $bdd->query("SHOW TABLES LIKE 'stock_clients'");
        $clientTableExists = $checkTable->rowCount() > 0;
    } catch (PDOException $e) {
        // Table n'existe pas, on continue sans
        $clientTableExists = false;
    }
    
    $sql = "
        SELECT 
            v.*,
            COALESCE(p.nom, 'Produit supprimé') AS produit_nom,
            COALESCE(p.code_produit, '') AS code_produit,
            COALESCE(pv.nom_point_vente, 'Point de vente inconnu') AS nom_point_vente,
            " . ($clientTableExists ? "c.nom AS client_nom, c.prenom AS client_prenom" : "NULL AS client_nom, NULL AS client_prenom") . ",
            COALESCE(u.nom, 'Utilisateur') AS user_nom,
            COALESCE(u.prenom, '') AS user_prenom
        FROM stock_vente v
        LEFT JOIN stock_produit p ON v.id_produit = p.id_produit
        LEFT JOIN stock_point_vente pv ON v.id_point_vente = pv.id_point_vente
        LEFT JOIN stock_utilisateur u ON v.id_user = u.id_utilisateur
        " . ($clientTableExists ? "LEFT JOIN stock_clients c ON v.id_client = c.id" : "") . "
        WHERE {$whereClause}
        ORDER BY v.date_vente DESC
        LIMIT 1000
    ";
    
    try {
        $stmt = $bdd->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur SQL dans getVentes: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));
        throw new Exception("Erreur lors de la récupération des ventes: " . $e->getMessage());
    }
}

// =====================================================
// GESTION DES REQUÊTES
// =====================================================

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            if ($action === 'all' || $action === '') {
                $filters = [
                    'date_debut' => $_GET['date_debut'] ?? null,
                    'date_fin' => $_GET['date_fin'] ?? null,
                    'id_point_vente' => $_GET['id_point_vente'] ?? null
                ];
                $resultat = getVentes($bdd, $enterpriseId, $filters);
            } else {
                throw new Exception("Action non valide");
            }
            break;
            
        case 'POST':
            if ($action === 'create' || $action === '') {
                $resultat = createVente($bdd, $data, $enterpriseId, $userId, $currentUser);
            } else {
                throw new Exception("Action non valide");
            }
            break;
            
        default:
            throw new Exception("Méthode HTTP non supportée");
    }
    
    echo json_encode([
        'success' => true,
        'data' => $resultat
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // S'assurer que les en-têtes CORS sont toujours envoyés même en cas d'erreur
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token, Accept');
    header('Content-Type: application/json; charset=utf-8');
    
    error_log("Erreur PDO dans api_vente.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    // S'assurer que les en-têtes CORS sont toujours envoyés même en cas d'erreur
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token, Accept');
    header('Content-Type: application/json; charset=utf-8');
    
    error_log("Erreur dans api_vente.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

