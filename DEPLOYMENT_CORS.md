# CORS – Solution définitive

## 1. Frontend : URL absolue (déjà en place)

Dans `src/composables/Api/apiService.js` :

- **Correct** : `baseURL: 'https://aliadjame.com/api-stock'`
- **À éviter** : `baseURL: '/api-stock'` (provoque des redirections et des erreurs CORS)

Surcharge possible via `VITE_API_BASE_URL` (ex. en prod si l’API est sur le même domaine).

---

## 2. Serveur : CORS en tout premier dans chaque API

Sur **aliadjame.com**, chaque fichier API (dont `api_forfait.php`) doit envoyer les en-têtes CORS **tout en haut**, avant tout autre code.

**Rien avant** : pas d’espace, pas de BOM, pas de `require` ni `echo` avant ces en-têtes.

Exemple à mettre en **tout début** du fichier (ou via `require_once __DIR__ . '/cors.php';` en première ligne après `<?php`) :

```php
<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost:8080',
    'https://aliadjame.com'
];

if (in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

**Interdit** : utiliser ensemble `Access-Control-Allow-Origin: *` et `Access-Control-Allow-Credentials: true` (le navigateur bloque).

---

## 3. Test de vérification

1. Ouvrir :  
   `https://aliadjame.com/api-stock/api_forfait.php?action=status`
2. Dans DevTools → **Network** → cliquer sur la requête → **Response Headers**.
3. Vérifier la présence de :  
   `Access-Control-Allow-Origin: http://localhost:5173`

Si ce header n’apparaît pas, le problème reste côté serveur (fichier non déployé ou CORS pas en premier).

---

## 4. Fichiers à déployer sur aliadjame.com

- `api-stock/cors.php` (version à jour)
- `api-stock/api_forfait.php` (CORS en tout premier)
- `api-stock/login.php`, `register.php`, `index.php` (incluent `cors.php` en premier)
- Tous les autres fichiers API appelés depuis le front

---

## 5. Règle à retenir

- **API distante** = URL absolue dans le frontend + CORS correct côté serveur.
- Ne pas utiliser d’URL relative pour une API externe (sinon redirections + CORS).
