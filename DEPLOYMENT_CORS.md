# 🔧 Guide de Déploiement - Correction CORS

## ⚠️ Problème Actuel

Le frontend en local (`http://localhost:5173`) ne peut pas communiquer avec l'API sur `https://aliadjame.com` à cause de **CORS**.

## ✅ Solution : Déployer `cors.php` sur le Serveur

### Étape 1 : Vérifier que `cors.php` est bien déployé

Le fichier `src/composables/Api/cors.php` doit être déployé sur le serveur à :
```
https://aliadjame.com/api-stock/cors.php
```

### Étape 2 : Vérifier que tous les fichiers API incluent `cors.php`

**IMPORTANT** : Tous les fichiers API doivent inclure `cors.php` **tout en haut**, avant tout autre code :

```php
<?php
// CORS - OBLIGATOIRE EN PREMIER
require_once __DIR__ . '/cors.php';

// Ensuite le reste du code...
```

### Étape 3 : Tester les headers CORS

Teste directement dans ton navigateur ou avec curl :

```bash
curl -I https://aliadjame.com/api-stock/login.php
```

Tu dois voir dans les headers :
```
Access-Control-Allow-Origin: http://localhost:5173
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token
```

### Étape 4 : Vérifier les fichiers à déployer

Assure-toi que ces fichiers sont bien sur le serveur :

- ✅ `api-stock/cors.php`
- ✅ `api-stock/login.php` (qui inclut cors.php)
- ✅ `api-stock/index.php` (qui inclut cors.php)
- ✅ `api-stock/api_forfait.php` (qui inclut cors.php)
- ✅ Tous les autres fichiers API

## 🔍 Vérification Rapide

Ouvre la console du navigateur (F12) et teste :

```javascript
fetch('https://aliadjame.com/api-stock/login.php', {
  method: 'OPTIONS',
  headers: {
    'Origin': 'http://localhost:5173'
  }
})
.then(r => {
  console.log('Headers CORS:', r.headers.get('access-control-allow-origin'))
})
```

Si tu vois `null` ou une erreur CORS → le problème est côté serveur.

## 📝 Fichiers Modifiés

- ✅ `vite.config.js` - Proxy amélioré
- ✅ `src/composables/Api/cors.php` - Détection d'origine améliorée
- ✅ `src/views/Login.vue` - Nettoyage localStorage avant connexion

## 🚀 Après Déploiement

1. **Redémarrer le serveur Vite** : `npm run dev`
2. **Vider le cache du navigateur** : Ctrl+Shift+R
3. **Tester la connexion**

## ⚡ Solution Alternative Temporaire (si le proxy ne fonctionne toujours pas)

Si le proxy Vite ne fonctionne toujours pas après redémarrage, tu peux temporairement utiliser directement l'URL complète en développement :

Dans `src/composables/Api/apiService.js`, change temporairement :

```javascript
const API_BASE_URL = import.meta.env.DEV 
  ? 'https://aliadjame.com/api-stock'  // Direct (nécessite CORS côté serveur)
  : '/api-stock'
```

**⚠️ Mais cela nécessite que le serveur renvoie bien les headers CORS !**
