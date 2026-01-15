-- Table des mouvements comptables enrichie
CREATE TABLE stock_comptabilite (
  id_compta INT AUTO_INCREMENT PRIMARY KEY,
  date DATETIME NOT NULL,
  type ENUM('Entrée','Sortie') NOT NULL,
  montant DECIMAL(15,2) NOT NULL,
  user VARCHAR(100) NOT NULL,
  details TEXT,
  id_entreprise INT NOT NULL,
  categorie VARCHAR(50),
  moyen_paiement VARCHAR(50),
  statut ENUM('validé','en attente','rejeté') DEFAULT 'en attente',
  piece_jointe VARCHAR(255),
  reference VARCHAR(100),
  commentaire TEXT,
  utilisateur_validateur VARCHAR(100),
  date_validation DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);
CREATE INDEX idx_compta_date ON stock_comptabilite(date);
CREATE INDEX idx_compta_type ON stock_comptabilite(type);
CREATE INDEX idx_compta_user ON stock_comptabilite(user);
CREATE INDEX idx_compta_entreprise ON stock_comptabilite(id_entreprise);
CREATE INDEX idx_compta_categorie ON stock_comptabilite(categorie);
CREATE INDEX idx_compta_statut ON stock_comptabilite(statut);
