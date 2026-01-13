-- Script SQL pour mettre à jour la table stock_journal
-- Ajoute la colonne id_entreprise si elle n'existe pas
-- À exécuter dans votre base de données

-- Vérifier et créer la table stock_journal si elle n'existe pas
CREATE TABLE IF NOT EXISTS `stock_journal` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user` VARCHAR(100) NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `id_entreprise` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`),
  KEY `idx_entreprise` (`id_entreprise`),
  KEY `idx_action` (`action`),
  CONSTRAINT `fk_journal_entreprise` FOREIGN KEY (`id_entreprise`) 
    REFERENCES `stock_entreprise` (`id_entreprise`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter la colonne id_entreprise si elle n'existe pas déjà
-- Pour MySQL 5.7+, on utilise une procédure pour éviter l'erreur si la colonne existe
SET @dbname = DATABASE();
SET @tablename = 'stock_journal';
SET @columnname = 'id_entreprise';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT(11) DEFAULT NULL, ADD KEY idx_entreprise (', @columnname, '), ADD CONSTRAINT fk_journal_entreprise FOREIGN KEY (', @columnname, ') REFERENCES stock_entreprise(id_entreprise) ON DELETE CASCADE ON UPDATE CASCADE')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Mettre à jour les enregistrements existants sans id_entreprise (optionnel)
-- UPDATE stock_journal SET id_entreprise = 1 WHERE id_entreprise IS NULL;
-- (Décommentez et ajustez selon vos besoins)

