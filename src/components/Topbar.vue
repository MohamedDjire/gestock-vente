<template>
  <header class="topbar">
    <div style="flex:1; display: flex; align-items: center;">
      <!-- Affichage de la date d'expiration du forfait -->
      <div v-if="loading && !forfaitStatus" class="forfait-expiration forfait-loading">
        <span>Chargement du forfait...</span>
      </div>
      <div v-else-if="forfaitStatus" class="forfait-expiration" :class="forfaitStatusColor">
        <template v-if="forfaitStatus.date_fin">
          <span class="forfait-label">Forfait expire le:</span>
          <span class="forfait-date">{{ formatDateExpiration }}</span>
          <span v-if="joursRestants !== null && joursRestants > 0" class="forfait-jours">
            ({{ joursRestants }} jour{{ joursRestants > 1 ? 's' : '' }} restant{{ joursRestants > 1 ? 's' : '' }})
          </span>
          <span v-else-if="isExpired" class="forfait-expired">⚠️ Expiré</span>
        </template>
        <template v-else>
          <span class="forfait-label">⚠️ Aucun forfait actif</span>
        </template>
      </div>
      <div v-else class="forfait-expiration forfait-no-subscription">
        <span class="forfait-label">⚠️ Aucun forfait</span>
      </div>
    </div>
    <div class="topbar-actions">
      <div class="currency-select-topbar">
        <label>Devise:</label>
        <select v-model="currencyCode" class="currency-select-input" @change="handleCurrencyChange">
          <optgroup label="Afrique de l'Ouest">
            <option v-for="code in currenciesByRegion['Afrique de l\'Ouest']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Afrique Centrale">
            <option v-for="code in currenciesByRegion['Afrique Centrale']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Afrique de l'Est">
            <option v-for="code in currenciesByRegion['Afrique de l\'Est']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Afrique du Nord">
            <option v-for="code in currenciesByRegion['Afrique du Nord']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Afrique Australe">
            <option v-for="code in currenciesByRegion['Afrique Australe']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Internationales">
            <option v-for="code in currenciesByRegion['Internationales']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Autres">
            <option v-for="code in currenciesByRegion['Autres']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
        </select>
        <span v-if="currencyVariation" class="currency-variation" :class="{ positive: currencyVariation.isPositive, negative: !currencyVariation.isPositive }">
          {{ currencyVariation.isPositive ? '↑' : '↓' }} {{ Math.abs(currencyVariation.value).toFixed(2) }}%
        </span>
      </div>
      <span class="notif-icon">🔔</span>
      <div class="profile">
        <img :src="user?.avatar || 'https://randomuser.me/api/portraits/women/44.jpg'" alt="profile" />
        <span class="profile-name">{{ user?.nom || 'Danielle Campbell' }}</span>
      </div>
    </div>
  </header>
</template>

<script setup>
import { useStorage } from '../composables/storage/useStorage'
import { useCurrencyStore } from '../stores/currency'
import { useAuthStore } from '../stores/auth.js'
import { useForfait } from '../composables/useForfait.js'
import { ref, onMounted, provide, watch, computed, onUnmounted } from 'vue'

const { getUser } = useStorage()
const user = ref(null)
const currencyStore = useCurrencyStore()

// Gestion du forfait
const {
  forfaitStatus,
  loading,
  isExpired,
  joursRestants,
  statusColor: forfaitStatusColor,
  formatDateExpiration,
  checkForfait,
  startAutoCheck,
  stopAutoCheck,
  loadFromStorage
} = useForfait()

// Utiliser le store pour la devise
const currencyCode = ref(currencyStore.currency || 'XOF')

// Computed pour accéder aux données du store
const currencyNames = computed(() => currencyStore.currencyNames)
const currencySymbols = computed(() => currencyStore.currencySymbols)
const currenciesByRegion = computed(() => currencyStore.currenciesByRegion)

// Variation de la devise actuelle
const currencyVariation = computed(() => {
  const variation = currencyStore.getCurrencyVariation(currencyCode.value)
  return variation
})

// Fournir la devise aux composants enfants (pour compatibilité)
provide('selectedCurrency', currencyCode)

const handleCurrencyChange = () => {
  currencyStore.setCurrency(currencyCode.value)
}

onMounted(() => {
  user.value = getUser()
  // S'assurer que la devise du store est synchronisée
  currencyCode.value = currencyStore.currency || 'XOF'
  
  // Ne vérifier le forfait que si l'utilisateur est authentifié
  const authStore = useAuthStore()
  if (authStore.isAuthenticated) {
    // Charger le statut du forfait depuis localStorage (pour synchronisation rapide)
    loadFromStorage()
    
    // Démarrer la vérification automatique du forfait
    startAutoCheck()
    
    // Vérifier aussi lors du changement de focus de la fenêtre
    window.addEventListener('focus', checkForfait)
  }
})

onUnmounted(() => {
  stopAutoCheck()
  window.removeEventListener('focus', checkForfait)
})

// Synchroniser avec le store
watch(() => currencyStore.currency, (newCurrency) => {
  currencyCode.value = newCurrency
})
</script>

<style scoped>
/* Topbar moderne, fond blanc, arrondi, avatar, notification, recherche */
.topbar {
  display: flex;
  align-items: center;
  padding: 1rem 2.2rem;
  background: #fff;
  box-shadow: 0 2px 12px #0001;
  border-radius: 0 32px 0 0;
  min-height: 70px;
  position: fixed;
  top: 0;
  left: 280px;
  right: 0;
  z-index: 1002;
  width: calc(100vw - 280px);
  flex-shrink: 0;
}

@media (max-width: 1100px) {
  .topbar {
    left: 0;
    width: 100vw;
    border-radius: 0;
  }
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 1.7rem;
}

.currency-select-topbar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f5f6fa;
  border-radius: 12px;
  padding: 0.4rem 0.8rem;
}

.currency-select-topbar label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1a5f4a;
}

.currency-select-input {
  padding: 0.4rem 0.6rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
  color: #1f2937;
  cursor: pointer;
  transition: all 0.2s;
}

.currency-select-input:focus {
  outline: none;
  border-color: #1a5f4a;
  box-shadow: 0 0 0 3px rgba(26, 95, 74, 0.1);
}

.currency-variation {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  margin-left: 0.25rem;
}

.currency-variation.positive {
  background: #d1fae5;
  color: #065f46;
}

.currency-variation.negative {
  background: #fee2e2;
  color: #991b1b;
}
.notif-icon {
  font-size: 1.5rem;
  color: #1a5f4a;
  cursor: pointer;
}
.profile {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  background: #f5f6fa;
  border-radius: 20px;
  padding: 0.3rem 1rem;
}
.profile img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  box-shadow: 0 2px 8px #1a5f4a22;
}
.profile-name {
  font-weight: 600;
  color: #1a5f4a;
  font-size: 1.08rem;
}

/* Affichage de l'expiration du forfait */
.forfait-expiration {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  white-space: nowrap;
  min-width: fit-content;
}

.forfait-expiration.forfait-loading {
  color: #6b7280;
  background: #f3f4f6;
}

.forfait-expiration.forfait-no-subscription {
  color: #991b1b;
  background: #fee2e2;
}

.forfait-expiration.black {
  color: #1f2937;
  background: #f3f4f6;
}

.forfait-expiration.orange {
  color: #92400e;
  background: #fef3c7;
}

.forfait-expiration.red {
  color: #991b1b;
  background: #fee2e2;
}

.forfait-label {
  font-weight: 600;
}

.forfait-date {
  font-weight: 700;
}

.forfait-jours {
  font-size: 0.8rem;
  opacity: 0.8;
}

.forfait-expired {
  font-weight: 700;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}
</style>
