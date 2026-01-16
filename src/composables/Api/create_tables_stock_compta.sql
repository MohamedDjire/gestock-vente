-- Table principale des écritures comptables
CREATE TABLE IF NOT EXISTS stock_compta_ecritures (
    id_compta INT AUTO_INCREMENT PRIMARY KEY,
    date_ecriture DATE NOT NULL,
    type_ecriture VARCHAR(50) NOT NULL, -- Entrée, Sortie, Virement, etc.
    montant DECIMAL(15,2) NOT NULL,
    user VARCHAR(100),
    categorie VARCHAR(100),
    moyen_paiement VARCHAR(50),
    statut VARCHAR(50) DEFAULT 'en attente',
    reference VARCHAR(100),
    piece_jointe VARCHAR(255),
    commentaire TEXT,
    details TEXT,
    id_entreprise INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des factures clients
CREATE TABLE IF NOT EXISTS stock_compta_factures_clients (
    id_facture INT AUTO_INCREMENT PRIMARY KEY,
    numero_facture VARCHAR(100) NOT NULL,
    date_facture DATE NOT NULL,
    montant DECIMAL(15,2) NOT NULL,
    client VARCHAR(100),
    statut VARCHAR(50) DEFAULT 'en attente',
    id_entreprise INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des factures fournisseurs
CREATE TABLE IF NOT EXISTS stock_compta_factures_fournisseurs (
    id_facture INT AUTO_INCREMENT PRIMARY KEY,
    numero_facture VARCHAR(100) NOT NULL,
    date_facture DATE NOT NULL,
    montant DECIMAL(15,2) NOT NULL,
    fournisseur VARCHAR(100),
    statut VARCHAR(50) DEFAULT 'en attente',
    id_entreprise INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de trésorerie
CREATE TABLE IF NOT EXISTS stock_compta_tresorerie (
    id_tresorerie INT AUTO_INCREMENT PRIMARY KEY,
    date_operation DATE NOT NULL,
    type_operation VARCHAR(50) NOT NULL,
    montant DECIMAL(15,2) NOT NULL,
    solde DECIMAL(15,2),
    commentaire TEXT,
    id_entreprise INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des rapports comptables
CREATE TABLE IF NOT EXISTS stock_compta_rapports (
    id_rapport INT AUTO_INCREMENT PRIMARY KEY,
    type_rapport VARCHAR(100) NOT NULL,
    periode VARCHAR(50),
    donnees TEXT,
    id_entreprise INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table d'audit
CREATE TABLE IF NOT EXISTS stock_compta_audit (
    id_audit INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    user VARCHAR(100),
    details TEXT,
    id_entreprise INT NOT NULL,
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
