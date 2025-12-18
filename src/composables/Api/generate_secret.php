<?php
/**
 * Script pour générer une clé secrète JWT sécurisée
 * 
 * UTILISATION:
 * 1. Exécutez ce script: php generate_secret.php
 * 2. Copiez la clé générée
 * 3. Remplacez JWT_SECRET dans config.php
 * 4. SUPPRIMEZ ce fichier après utilisation
 */

echo "=== Générateur de Clé Secrète JWT ===\n\n";

// Méthode 1: Utiliser random_bytes (recommandé)
$secret1 = base64_encode(random_bytes(64)); // 64 bytes = 88 caractères en base64
echo "Clé secrète générée (base64):\n";
echo $secret1 . "\n\n";

// Méthode 2: Utiliser openssl_random_pseudo_bytes
if (function_exists('openssl_random_pseudo_bytes')) {
    $secret2 = bin2hex(openssl_random_pseudo_bytes(32)); // 64 caractères hex
    echo "Clé secrète générée (hex):\n";
    echo $secret2 . "\n\n";
}

// Méthode 3: Combinaison aléatoire
$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
$secret3 = '';
for ($i = 0; $i < 64; $i++) {
    $secret3 .= $chars[random_int(0, strlen($chars) - 1)];
}
echo "Clé secrète générée (caractères aléatoires):\n";
echo $secret3 . "\n\n";

echo "=== RECOMMANDATION ===\n";
echo "Utilisez la première clé (base64) - elle est la plus sécurisée.\n";
echo "Longueur: " . strlen($secret1) . " caractères\n";
echo "\n";
echo "Copiez cette clé et remplacez JWT_SECRET dans config.php:\n";
echo "define('JWT_SECRET', '" . $secret1 . "');\n";
echo "\n";
echo "⚠️  IMPORTANT: Supprimez ce fichier après utilisation!\n";
?>
