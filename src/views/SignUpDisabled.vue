<template>
  <div class="auth-container">
    <div class="auth-wrapper signup-disabled">
      <div class="auth-content">
        <div class="auth-header">
          <div class="app-logo">
            <div class="logo-square">PS</div>
          </div>
        </div>
        <div class="auth-title-section">
          <h1 class="auth-title">Inscription désactivée</h1>
          <p class="auth-subtitle">
            Les comptes sont créés uniquement par les administrateurs. Si vous devez accéder à l'application, contactez votre administrateur pour qu'il vous ajoute en tant qu'utilisateur.
          </p>
        </div>
        <div class="auth-actions">
          <router-link to="/login" class="auth-button">Retour à la connexion</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Page affichée quand on tente d'accéder à /signup
// Seuls les administrateurs créent les comptes via Gestion des utilisateurs
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: #f3f4f6;
}

.auth-wrapper {
  background: #fff;
  border-radius: 20px;
  padding: 3rem;
  max-width: 480px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.auth-header {
  margin-bottom: 2rem;
}

.logo-square {
  width: 50px;
  height: 50px;
  background: #1a5f4a;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1rem;
}

.auth-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 0.5rem 0;
}

.auth-subtitle {
  font-size: 1rem;
  color: #6b7280;
  line-height: 1.5;
  margin: 0 0 2rem 0;
}

.auth-actions {
  margin-top: 1.5rem;
}

.auth-button {
  display: inline-block;
  width: 100%;
  padding: 0.875rem;
  background: #1a5f4a;
  color: white;
  text-align: center;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  text-decoration: none;
  transition: background-color 0.2s;
}

.auth-button:hover {
  background: #134e3a;
}
</style>
