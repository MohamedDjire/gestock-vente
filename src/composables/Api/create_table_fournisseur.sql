-- SQL pour créer la table fournisseurs
CREATE TABLE fournisseurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  telephone VARCHAR(30),
  adresse VARCHAR(255),
  statut VARCHAR(20) DEFAULT 'actif',
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
