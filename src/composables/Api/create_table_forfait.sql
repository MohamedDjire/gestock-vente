-- =====================================================
-- TABLE FORFAIT
-- =====================================================
-- Script SQL pour créer les tables de gestion des forfaits
-- Chaque entreprise doit souscrire à un forfait pour utiliser l'application

-- Table des types de forfaits disponibles
CREATE TABLE IF NOT EXISTS `stock_forfait` (
  `id_forfait` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_forfait` VARCHAR(100) NOT NULL,
  `prix` DECIMAL(10,2) NOT NULL,
  `duree_jours` INT(11) NOT NULL COMMENT 'Durée du forfait en jours',
  `description` TEXT DEFAULT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_forfait`),
  KEY `idx_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des abonnements des entreprises aux forfaits
CREATE TABLE IF NOT EXISTS `stock_abonnement` (
  `id_abonnement` INT(11) NOT NULL AUTO_INCREMENT,
  `id_entreprise` INT(11) NOT NULL,
  `id_forfait` INT(11) NOT NULL,
  `date_debut` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` DATETIME NOT NULL,
  `prix_paye` DECIMAL(10,2) NOT NULL,
  `statut` ENUM('actif', 'expire', 'annule') NOT NULL DEFAULT 'actif',
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_abonnement`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_forfait` (`id_forfait`),
  KEY `idx_date_fin` (`date_fin`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_abonnement_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_abonnement_forfait` FOREIGN KEY (`id_forfait`) 
    REFERENCES `stock_forfait` (`id_forfait`) 
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les forfaits par défaut
INSERT INTO `stock_forfait` (`nom_forfait`, `prix`, `duree_jours`, `description`, `actif`) VALUES
('Forfait Basique', 3000.00, 30, 'Forfait mensuel de base avec fonctionnalités essentielles', 1),
('Forfait Premium', 5000.00, 30, 'Forfait mensuel premium avec toutes les fonctionnalités', 1)
ON DUPLICATE KEY UPDATE `nom_forfait` = VALUES(`nom_forfait`);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS `idx_abonnement_entreprise_statut` 
  ON `stock_abonnement` (`id_entreprise`, `statut`, `date_fin`);
