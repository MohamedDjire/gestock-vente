<?php
/**
 * Fichier CORS central - À inclure au tout début de chaque fichier API
 * Gère les headers CORS et les requêtes OPTIONS (preflight)
 * 
 * IMPORTANT: Ce fichier doit être inclus AVANT tout autre code PHP
 * Pas d'espace, pas d'echo, pas de BOM avant <?php
 */

// Liste des origines autorisées
$allowedOrigins = [
    "http://localhost:5173",
    "http://localhost:3000",
    "http://localhost:8080",
    "https://aliadjame.com"
];

// Récupérer l'origine de la requête
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Définir l'origine autorisée
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    // En développement, autoriser toutes les origines (à retirer en production)
    header("Access-Control-Allow-Origin: *");
}

// Définir les autres headers CORS
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");

// Gérer les requêtes OPTIONS (preflight) IMMÉDIATEMENT
// Le navigateur envoie d'abord une requête OPTIONS pour vérifier les permissions CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Content-Length: 0');
    http_response_code(200);
    exit(0);
}
