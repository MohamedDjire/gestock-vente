<?php
/**
 * Configuration principale de l'API
 * 
 * Ce fichier contient les configurations générales de l'API (JWT, etc.)
 * Pour la configuration de la base de données, voir config_database.php
 */

// Clé secrète pour la signature des JWT (à personnaliser !)
// IMPORTANT: Changez cette clé par une clé unique et complexe en production
// Utilisez une clé d'au moins 32 caractères générée aléatoirement
// Utilisez le script generate_secret.php pour générer une clé sécurisée
if (!defined('JWT_SECRET')) {
    // Clé secrète sécurisée - CHANGEZ-LA EN PRODUCTION
    define('JWT_SECRET', 'ProStock_2025_Secure_Key_' . hash('sha256', 'aliadjame.com_stock_management'));
}

// Durée d'expiration du token JWT (en secondes)
// Par défaut: 7 jours (604800 secondes)
if (!defined('JWT_EXPIRATION')) {
    define('JWT_EXPIRATION', 604800); // 7 jours
}
