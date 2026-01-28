-- Table des paramètres de notifications (email & sms)
CREATE TABLE stock_notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_admin VARCHAR(255),
    telephone_admin VARCHAR(30),
    email_active TINYINT(1) DEFAULT 0,
    sms_active TINYINT(1) DEFAULT 0,
    notifier_vente TINYINT(1) DEFAULT 1,
    notifier_paiement TINYINT(1) DEFAULT 0,
    notifier_stock_faible TINYINT(1) DEFAULT 0,
    notifier_objectif_vente TINYINT(1) DEFAULT 0,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modifie_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table de log des notifications envoyées
CREATE TABLE stock_notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('email','sms') NOT NULL,
    type_evenement VARCHAR(50) NOT NULL,
    message TEXT,
    statut ENUM('envoye','echec') NOT NULL,
    envoye_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
