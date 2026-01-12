# Mise à jour des Forfaits

Ce document explique comment mettre à jour les forfaits avec les nouveaux prix et limites.

## Nouveaux Forfaits

1. **Starter - 2500 FCFA**
   - 1 utilisateur + admin
   - 1 entrepôt
   - 1 point de vente

2. **Standard - 4000 FCFA**
   - 2-3 utilisateurs + admin
   - 2-3 entrepôts
   - 2-3 points de vente

3. **Business - 7000 FCFA**
   - Jusqu'à 6 utilisateurs + admin
   - 5-6 entrepôts
   - 5-6 points de vente
   - Fonctionnalités avancées

4. **Enterprise - 10000 FCFA**
   - Jusqu'à 10-15 utilisateurs + admin
   - Peut nommer un autre admin
   - 10-15 entrepôts
   - 10-15 points de vente
   - Toutes les fonctionnalités avancées

## Installation

### Étape 1 : Exécuter le script SQL

**Option A : Si les colonnes n'existent pas encore**

Exécutez d'abord `add_forfait_columns.sql` puis `update_forfaits.sql` :

```bash
mysql -u votre_utilisateur -p votre_base_de_donnees < src/composables/Api/add_forfait_columns.sql
mysql -u votre_utilisateur -p votre_base_de_donnees < src/composables/Api/update_forfaits.sql
```

**Option B : Script unique (recommandé)**

Le script `update_forfaits.sql` inclut maintenant la vérification automatique des colonnes, vous pouvez l'exécuter directement :

```bash
mysql -u votre_utilisateur -p votre_base_de_donnees < src/composables/Api/update_forfaits.sql
```

Ou via phpMyAdmin / votre outil de gestion de base de données préféré.

### Étape 2 : Vérifier les modifications

Le script va :
1. Ajouter les colonnes de limites à la table `stock_forfait`
2. Désactiver les anciens forfaits
3. Créer les 4 nouveaux forfaits avec leurs caractéristiques

### Étape 3 : Vérifier dans l'interface

Connectez-vous en tant qu'admin et allez dans "Gestion du Compte" pour voir les nouveaux forfaits.

## Fonctionnalités

### Vérification automatique des limites

Le système vérifie automatiquement les limites lors de :
- Création d'un utilisateur
- Création d'un entrepôt
- Création d'un point de vente
- Nomination d'un administrateur (forfait Enterprise uniquement)

### Messages d'erreur

Si une limite est atteinte, l'utilisateur recevra un message clair indiquant :
- La limite actuelle
- La limite maximale
- Un message explicatif

## Notes importantes

- Les anciens forfaits sont désactivés mais pas supprimés (pour préserver l'historique)
- Les entreprises avec des abonnements actifs conservent leur forfait jusqu'à expiration
- Les limites sont vérifiées en temps réel lors de chaque création

