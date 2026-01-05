# Configuration de l'URL de l'API

## Problème
Si vous obtenez une erreur 500, c'est probablement que l'URL de l'API n'est pas correctement configurée.

## Solution

### 1. Créer un fichier `.env` à la racine du projet

Créez un fichier `.env` (sans extension) à la racine de votre projet avec le contenu suivant :

```env
VITE_API_BASE_URL=http://votre-serveur.com/api-stock
```

**Exemples d'URL selon votre configuration :**

- **Serveur local (XAMPP/WAMP) :**
  ```
  VITE_API_BASE_URL=http://localhost/api-stock
  ```
  ou
  ```
  VITE_API_BASE_URL=http://localhost/gestock-vente/api-stock
  ```

- **Serveur distant :**
  ```
  VITE_API_BASE_URL=https://votre-domaine.com/api-stock
  ```

- **Serveur avec port spécifique :**
  ```
  VITE_API_BASE_URL=http://localhost:8000/api-stock
  ```

### 2. Vérifier que les fichiers PHP sont accessibles

Assurez-vous que vos fichiers PHP dans `src/composables/Api/` sont bien déployés sur votre serveur dans le dossier correspondant à l'URL configurée.

**Structure attendue sur le serveur :**
```
/api-stock/
  ├── login.php
  ├── register.php
  ├── index.php
  └── ...
```

### 3. Redémarrer le serveur de développement

Après avoir créé/modifié le fichier `.env`, redémarrez votre serveur Vite :

```bash
npm run dev
```

### 4. Vérifier dans la console

Ouvrez la console du navigateur (F12) et vous devriez voir :
```
🔗 URL de l'API configurée: http://votre-url/api-stock
```

## Test

Pour tester si votre API est accessible, essayez d'accéder directement à :
- `http://votre-url/api-stock/login.php` dans votre navigateur

Vous devriez voir une réponse JSON (même si c'est une erreur, cela confirme que le fichier est accessible).

## Endpoints utilisés

- **Login :** `POST /login.php`
- **Register :** `POST /register.php`

Ces endpoints sont appelés depuis :
- `src/views/Login.vue` → utilise `useAuth().login()`
- `src/views/SignUp.vue` → utilise `useAuth().signUp()`








