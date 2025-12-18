<template>
  <div class="auth-container">
    <div class="auth-wrapper">
      <!-- Left Panel - Sign Up Form -->
      <div class="auth-left-panel">
      <div class="auth-content">
        <!-- Logo and App Name -->
        <div class="auth-header">
          <div class="app-logo">
            <div class="logo-square">PS</div>
          </div>
        </div>

        <!-- Title and Subtitle -->
        <div class="auth-title-section">
          <h1 class="auth-title">Commencer</h1>
          <p class="auth-subtitle">Bienvenue sur ProStock - Créons votre compte</p>
        </div>

        <!-- Sign Up Form -->
        <form @submit.prevent="handleSignUp" class="auth-form">
          <div class="form-group">
            <label for="nom">Nom</label>
            <input
              id="nom"
              v-model="formData.nom"
              type="text"
              placeholder="Entrez votre nom"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="prenom">Prénom</label>
            <input
              id="prenom"
              v-model="formData.prenom"
              type="text"
              placeholder="Entrez votre prénom"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="username">Nom d'utilisateur</label>
            <input
              id="username"
              v-model="formData.username"
              type="text"
              placeholder="Choisissez un nom d'utilisateur"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="formData.email"
              type="email"
              placeholder="Entrez votre email"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="telephone">Téléphone (optionnel)</label>
            <input
              id="telephone"
              v-model="formData.telephone"
              type="tel"
              placeholder="Entrez votre numéro de téléphone"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="id_entreprise">ID Entreprise <span class="required">*</span></label>
            <input
              id="id_entreprise"
              v-model.number="formData.id_entreprise"
              type="number"
              placeholder="Entrez l'ID de l'entreprise"
              required
              class="form-input"
            />
            <small class="form-hint">Contactez l'administrateur pour obtenir un ID entreprise</small>
          </div>

          <div class="form-group">
            <label for="password">Mot de passe</label>
            <input
              id="password"
              v-model="formData.password"
              type="password"
              placeholder="Créez un mot de passe"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirmer le mot de passe</label>
            <input
              id="confirmPassword"
              v-model="formData.confirmPassword"
              type="password"
              placeholder="Confirmez votre mot de passe"
              required
              class="form-input"
            />
          </div>

          <div v-if="error" class="error-message">{{ error }}</div>

          <button type="submit" class="auth-button" :disabled="loading">
            {{ loading ? 'Création du compte...' : "S'inscrire" }}
          </button>
        </form>

        <!-- Log In Link -->
        <div class="auth-footer">
          <p>Vous avez déjà un compte ? <router-link to="/login" class="auth-link">Se connecter</router-link></p>
        </div>
      </div>
      </div>

      <!-- Right Panel - Promotional Content -->
    <div class="auth-right-panel">
      <div class="promo-content">
        <!-- Vertical Icon Bar -->
        <div class="icon-bar">
          <div class="icon-square">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </div>
          <div class="icon-square">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="1"></circle>
              <circle cx="12" cy="5" r="1"></circle>
              <circle cx="12" cy="19" r="1"></circle>
            </svg>
          </div>
          <div class="icon-square">
            <div class="mini-logo">PS</div>
          </div>
        </div>

        <!-- Main Promotional Text -->
        <div class="promo-text">
          <h2 class="promo-title">
            <span>Entrez</span>
            <span>dans l'Avenir</span>
            <span>de la Gestion de Stock,</span>
            <span>aujourd'hui</span>
          </h2>
        </div>

        <!-- Info Card -->
        <div class="info-card">
          <div class="card-header">
            <div class="card-logo">PS</div>
            <div class="card-title">Aperçu du Stock</div>
          </div>
          <div class="card-balance">
            <div class="balance-amount">12,347</div>
            <div class="balance-label">Produits Totaux</div>
          </div>
          <div class="card-details">
            <div class="detail-item">
              <span class="detail-label">Articles Actifs</span>
              <span class="detail-value">2,546</span>
            </div>
            <div class="card-number">PROD-****-****-6917</div>
          </div>
          <div class="card-footer">
            <span class="card-badge">PROSTOCK</span>
            <button class="view-all-btn">Voir Tout</button>
          </div>
        </div>
      </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/auth/useAuth.js'

const router = useRouter()
const { signUp, loading, error } = useAuth()

const formData = ref({
  nom: '',
  prenom: '',
  username: '',
  email: '',
  telephone: '',
  id_entreprise: null,
  password: '',
  confirmPassword: ''
})

const handleSignUp = async () => {
  // Vérifier que les mots de passe correspondent
  if (formData.value.password !== formData.value.confirmPassword) {
    error.value = 'Les mots de passe ne correspondent pas'
    return
  }

  // Préparer les données pour l'API
  const signUpData = {
    nom: formData.value.nom,
    prenom: formData.value.prenom,
    username: formData.value.username,
    email: formData.value.email,
    telephone: formData.value.telephone || null,
    id_entreprise: formData.value.id_entreprise,
    password: formData.value.password,
    mot_de_passe: formData.value.password // Pour compatibilité avec l'API
  }

  const result = await signUp(signUpData)
  
  if (result.success) {
    // Rediriger vers le dashboard après inscription réussie
    router.push({ name: 'Dashboard' })
  }
}
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  width: 100%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.auth-wrapper {
  display: flex;
  width: 100%;
  max-width: 1200px;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

/* Left Panel Styles */
.auth-left-panel {
  flex: 1.5;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  overflow-y: auto;
}

.auth-content {
  width: 100%;
  max-width: 450px;
}

.auth-header {
  margin-bottom: 3rem;
}

.app-logo {
  display: flex;
  align-items: center;
  gap: 1rem;
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
  letter-spacing: 0.5px;
}

.auth-title-section {
  margin-bottom: 2.5rem;
}

.auth-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 0.5rem 0;
}

.auth-subtitle {
  font-size: 1rem;
  color: #6b7280;
  margin: 0;
}

.auth-form {
  margin-bottom: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #059669;
}

.form-hint {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.required {
  color: #ef4444;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  padding: 0.5rem;
  background: #fee2e2;
  border-radius: 6px;
}

.auth-button {
  width: 100%;
  padding: 0.875rem;
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.auth-button:hover:not(:disabled) {
  background: #134e3a;
}

.auth-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.auth-footer {
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
}

.auth-link {
  color: #1a5f4a;
  text-decoration: none;
  font-weight: 600;
}

.auth-link:hover {
  text-decoration: underline;
}

/* Right Panel Styles */
.auth-right-panel {
  flex: 1;
  background: linear-gradient(180deg, #1a5f4a 0%, #134e3a 50%, #0f3d2e 100%);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  overflow: hidden;
  border-radius: 0 20px 20px 0;
  align-self: stretch;
}

.auth-right-panel::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 80px;
  background: linear-gradient(to bottom, rgba(26, 95, 74, 0) 0%, rgba(19, 78, 58, 0.5) 40%, rgba(15, 61, 46, 0.9) 80%, rgba(10, 40, 30, 1) 100%);
  pointer-events: none;
  border-radius: 0 0 20px 0;
}

.promo-content {
  width: 100%;
  max-width: 450px;
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  height: 100%;
  justify-content: flex-start;
}

.icon-bar {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 0;
}

.icon-square {
  width: 40px;
  height: 40px;
  background: white;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1a5f4a;
}

.mini-logo {
  font-weight: 700;
  font-size: 0.875rem;
  color: #1a5f4a;
}

.promo-text {
  margin-bottom: 0;
  flex-shrink: 0;
}

.promo-title {
  font-family: 'Georgia', 'Times New Roman', serif;
  font-size: 2rem;
  font-weight: 400;
  color: white;
  line-height: 1.1;
  margin: 0;
  font-style: italic;
}

.promo-title span {
  display: block;
}

.info-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.card-logo {
  width: 32px;
  height: 32px;
  background: #10b981;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
}

.card-title {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.card-balance {
  margin-bottom: 1rem;
}

.balance-amount {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.25rem;
}

.balance-label {
  font-size: 0.8rem;
  color: #6b7280;
}

.card-details {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.detail-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.detail-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1a1a1a;
}

.card-number {
  font-size: 0.875rem;
  color: #9ca3af;
  font-family: monospace;
  margin-top: 0.5rem;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-badge {
  font-size: 0.75rem;
  font-weight: 700;
  color: #1a5f4a;
  letter-spacing: 1px;
}

.view-all-btn {
  padding: 0.5rem 1rem;
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.view-all-btn:hover {
  background: #134e3a;
}

/* Responsive */
@media (max-width: 968px) {
  .auth-container {
    flex-direction: column;
  }

  .auth-right-panel {
    min-height: 50vh;
  }

  .promo-title {
    font-size: 2.5rem;
  }
}
</style>
