<?php
/**
 * Fonctions pour la gestion des entreprises
 */

/**
 * Récupère toutes les entreprises
 * @param PDO $bdd Instance PDO de la base de données
 * @param array $currentUser Utilisateur courant
 * @return array Liste des entreprises
 */
function getAllEnterprises($bdd, $currentUser) {
    $role = strtolower($currentUser['role']);
    if ($role !== 'admin') {
        $stmt = $bdd->prepare("SELECT * FROM stock_entreprise WHERE id_entreprise = :id ORDER BY nom_entreprise");
        $stmt->bindParam(':id', $currentUser['enterprise_id'], PDO::PARAM_INT);
    } else {
        $stmt = $bdd->prepare("SELECT * FROM stock_entreprise ORDER BY nom_entreprise");
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère une entreprise par son ID
 */
function getEnterpriseById($bdd, $id) {
    if ($id <= 0) {
        throw new Exception("ID d'entreprise invalide");
    }
    $stmt = $bdd->prepare("SELECT * FROM stock_entreprise WHERE id_entreprise = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $enterprise = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$enterprise) {
        throw new Exception("Entreprise non trouvée", 404);
    }
    return $enterprise;
}

/**
 * Recherche des entreprises par nom, ville ou email
 */
function searchEnterprises($bdd, $query, $currentUser) {
    $role = strtolower($currentUser['role']);
    $searchTerm = "%$query%";
    if ($role !== 'admin') {
        $stmt = $bdd->prepare("
            SELECT * FROM stock_entreprise 
            WHERE id_entreprise = :id 
            AND (nom_entreprise LIKE :term OR ville LIKE :term OR email LIKE :term)
            ORDER BY nom_entreprise
        ");
        $stmt->bindParam(':id', $currentUser['enterprise_id'], PDO::PARAM_INT);
    } else {
        $stmt = $bdd->prepare("
            SELECT * FROM stock_entreprise 
            WHERE nom_entreprise LIKE :term OR ville LIKE :term OR email LIKE :term
            ORDER BY nom_entreprise
        ");
    }
    $stmt->bindParam(':term', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Crée une nouvelle entreprise
 */
function createEnterprise($bdd, $data) {
    $stmt = $bdd->prepare("
        INSERT INTO stock_entreprise (
            nom_entreprise, slug, sigle, telephone, email, adresse, ville, pays, code_postal, logo, registre_commerce, ncc, devise, site_web, fax, capital_social, forme_juridique, numero_tva, date_abonnement, date_expiration_abonnement, statut, date_creation, date_modification
        ) VALUES (
            :nom_entreprise, :slug, :sigle, :telephone, :email, :adresse, :ville, :pays, :code_postal, :logo, :registre_commerce, :ncc, :devise, :site_web, :fax, :capital_social, :forme_juridique, :numero_tva, :date_abonnement, :date_expiration_abonnement, :statut, NOW(), NOW()
        )
    ");
    $stmt->bindValue(':nom_entreprise', $data['nom_entreprise'], PDO::PARAM_STR);
    $stmt->bindValue(':slug', $data['slug'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':sigle', $data['sigle'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':telephone', $data['telephone'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':email', $data['email'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':adresse', $data['adresse'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':ville', $data['ville'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':pays', $data['pays'] ?? 'France', PDO::PARAM_STR);
    $stmt->bindValue(':code_postal', $data['code_postal'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':logo', $data['logo'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':registre_commerce', $data['registre_commerce'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':ncc', $data['ncc'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':devise', $data['devise'] ?? 'EUR', PDO::PARAM_STR);
    $stmt->bindValue(':site_web', $data['site_web'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':fax', $data['fax'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':capital_social', $data['capital_social'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':forme_juridique', $data['forme_juridique'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':numero_tva', $data['numero_tva'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':date_abonnement', $data['date_abonnement'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':date_expiration_abonnement', $data['date_expiration_abonnement'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':statut', $data['statut'] ?? 'actif', PDO::PARAM_STR);
    $stmt->execute();
    $newId = $bdd->lastInsertId();
    return [
        'message' => 'Entreprise créée avec succès',
        'id_entreprise' => $newId,
        'entreprise' => getEnterpriseById($bdd, $newId)
    ];
}

/**
 * Met à jour une entreprise existante
 */
function updateEnterprise($bdd, $id, $data) {
    getEnterpriseById($bdd, $id);
    $stmt = $bdd->prepare("
        UPDATE stock_entreprise SET
            nom_entreprise = :nom_entreprise,
            slug = :slug,
            sigle = :sigle,
            telephone = :telephone,
            email = :email,
            adresse = :adresse,
            ville = :ville,
            pays = :pays,
            code_postal = :code_postal,
            logo = :logo,
            registre_commerce = :registre_commerce,
            ncc = :ncc,
            devise = :devise,
            site_web = :site_web,
            fax = :fax,
            capital_social = :capital_social,
            forme_juridique = :forme_juridique,
            numero_tva = :numero_tva,
            date_abonnement = :date_abonnement,
            date_expiration_abonnement = :date_expiration_abonnement,
            statut = :statut,
            date_modification = NOW()
        WHERE id_entreprise = :id
    ");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':nom_entreprise', $data['nom_entreprise'], PDO::PARAM_STR);
    $stmt->bindValue(':slug', $data['slug'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':sigle', $data['sigle'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':telephone', $data['telephone'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':email', $data['email'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':adresse', $data['adresse'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':ville', $data['ville'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':pays', $data['pays'] ?? 'France', PDO::PARAM_STR);
    $stmt->bindValue(':code_postal', $data['code_postal'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':logo', $data['logo'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':registre_commerce', $data['registre_commerce'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':ncc', $data['ncc'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':devise', $data['devise'] ?? 'EUR', PDO::PARAM_STR);
    $stmt->bindValue(':site_web', $data['site_web'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':fax', $data['fax'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':capital_social', $data['capital_social'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':forme_juridique', $data['forme_juridique'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':numero_tva', $data['numero_tva'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':date_abonnement', $data['date_abonnement'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':date_expiration_abonnement', $data['date_expiration_abonnement'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':statut', $data['statut'] ?? 'actif', PDO::PARAM_STR);
    $stmt->execute();
    return [
        'message' => 'Entreprise mise à jour avec succès',
        'entreprise' => getEnterpriseById($bdd, $id)
    ];
}

/**
 * Met à jour le statut d'une entreprise
 */
function updateEnterpriseStatus($bdd, $id, $statut) {
    getEnterpriseById($bdd, $id);
    $validStatuses = ['actif', 'inactif', 'suspendu'];
    if (!in_array($statut, $validStatuses)) {
        throw new Exception("Statut invalide. Les valeurs acceptées sont: " . implode(', ', $validStatuses));
    }
    $stmt = $bdd->prepare("UPDATE stock_entreprise SET statut = :statut WHERE id_entreprise = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);
    $stmt->execute();
    return [
        'message' => 'Statut de l\'entreprise mis à jour avec succès',
        'entreprise' => getEnterpriseById($bdd, $id)
    ];
}

/**
 * Supprime une entreprise
 */
function deleteEnterprise($bdd, $id) {
    getEnterpriseById($bdd, $id);
    // Vérifier si des utilisateurs sont associés à cette entreprise
    $stmt = $bdd->prepare("SELECT COUNT(*) FROM stock_utilisateur WHERE id_entreprise = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        throw new Exception("Impossible de supprimer cette entreprise car elle est associée à " . $count . " utilisateur(s)");
    }
    $stmt = $bdd->prepare("DELETE FROM stock_entreprise WHERE id_entreprise = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return [
        'message' => 'Entreprise supprimée avec succès',
        'id_entreprise' => $id
    ];
}

/**
 * Supprime plusieurs entreprises
 */
function bulkDeleteEnterprises($bdd, $ids) {
    $deleted = [];
    $failed = [];
    foreach ($ids as $id) {
        try {
            deleteEnterprise($bdd, $id);
            $deleted[] = $id;
        } catch (Exception $e) {
            $failed[] = [
                'id' => $id,
                'reason' => $e->getMessage()
            ];
        }
    }
    return [
        'message' => count($deleted) . ' entreprise(s) supprimée(s), ' . count($failed) . ' échec(s)',
        'deleted' => $deleted,
        'failed' => $failed
    ];
}
?>
