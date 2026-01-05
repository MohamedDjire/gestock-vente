# Réinitialisation du mot de passe

## Problème
Le mot de passe dans la base de données est hashé et vous ne connaissez pas le mot de passe en clair.

## Solution temporaire

Un script PHP a été créé pour réinitialiser le mot de passe : `src/composables/Api/reset_password.php`

### Méthode 1 : Via le navigateur (Postman ou console)

1. Ouvrez Postman ou utilisez la console du navigateur
2. Faites une requête POST vers : `https://aliadjame.com/api-stock/reset_password.php`
3. Avec le body JSON :
```json
{
  "email": "admin@test.com",
  "new_password": "votre_nouveau_mot_de_passe"
}
```

### Méthode 2 : Via curl (terminal)

```bash
curl -X POST https://aliadjame.com/api-stock/reset_password.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","new_password":"votre_nouveau_mot_de_passe"}'
```

### Méthode 3 : Créer un script de test

Créez un fichier `test_reset.php` à la racine et exécutez-le :

```php
<?php
$data = [
    'email' => 'admin@test.com',
    'new_password' => 'admin123' // Votre nouveau mot de passe
];

$ch = curl_init('https://aliadjame.com/api-stock/reset_password.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
```

## Après la réinitialisation

1. **Supprimez le fichier** `reset_password.php` pour des raisons de sécurité
2. Connectez-vous avec :
   - Email : `admin@test.com`
   - Mot de passe : le nouveau mot de passe que vous avez défini

## Recommandation

Pour le compte admin, utilisez un mot de passe fort, par exemple : `Admin@2025` ou `ProStock2025!`








