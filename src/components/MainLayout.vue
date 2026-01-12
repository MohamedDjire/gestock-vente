<template>
  <div class="main-layout">
    <Sidebar v-if="isAuthenticated" />
    <div class="main-content">
      <div class="dashboard-wrapper">
        <div v-if="isAuthenticated" class="topbar-sticky">
          <Topbar />
        </div>
        <!-- Alerte forfait selon l'état -->
        <div v-if="showForfaitAlert" class="forfait-alert" :class="alertClass">
          <div class="forfait-alert-content">
            <span class="forfait-alert-icon">{{ alertIcon }}</span>
            <span class="forfait-alert-message" v-html="alertMessage"></span>
            <button @click="goToForfait" class="forfait-alert-btn" :class="{ 'urgent': isUrgent }">
              {{ alertButtonText }}
            </button>
          </div>
        </div>
        <div class="page-content" :class="{ 'blocked': isBlocked }">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from './Sidebar.vue'
import Topbar from './Topbar.vue'
import { useAuthStore } from '../stores/auth.js'
import { useForfait } from '../composables/useForfait.js'

const router = useRouter()
const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)
const { isExpired, checkForfait, loadFromStorage, forfaitStatus } = useForfait()

// Computed pour déterminer l'état du forfait et les alertes
const showForfaitAlert = computed(() => {
  if (!forfaitStatus.value) return false
  const etat = forfaitStatus.value.etat
  return etat === 'warning' || etat === 'grace' || etat === 'bloque'
})

const isBlocked = computed(() => {
  if (!forfaitStatus.value) return false
  return forfaitStatus.value.etat === 'bloque' || forfaitStatus.value.bloque === true
})

const isUrgent = computed(() => {
  if (!forfaitStatus.value) return false
  return forfaitStatus.value.etat === 'grace' || forfaitStatus.value.en_grace === true
})

const alertClass = computed(() => {
  if (!forfaitStatus.value) return ''
  const etat = forfaitStatus.value.etat
  if (etat === 'bloque') return 'forfait-alert-blocked'
  if (etat === 'grace') return 'forfait-alert-grace'
  if (etat === 'warning') return 'forfait-alert-warning'
  return ''
})

const alertIcon = computed(() => {
  if (!forfaitStatus.value) return '⚠️'
  const etat = forfaitStatus.value.etat
  if (etat === 'bloque') return '🚫'
  if (etat === 'grace') return '⏰'
  if (etat === 'warning') return '⚠️'
  return '⚠️'
})

const alertMessage = computed(() => {
  if (!forfaitStatus.value) return ''
  const etat = forfaitStatus.value.etat
  
  if (etat === 'bloque') {
    return '<strong>Votre forfait a expiré et toutes les fonctionnalités sont bloquées.</strong> Veuillez renouveler votre abonnement immédiatement pour continuer à utiliser l\'application.'
  }
  
  if (etat === 'grace') {
    const jours = forfaitStatus.value.jours_grace_restants || 0
    return `<strong>Votre forfait a expiré !</strong> Il vous reste <strong>${jours} jour${jours > 1 ? 's' : ''}</strong> pour renouveler votre abonnement avant que toutes les fonctionnalités soient bloquées.`
  }
  
  if (etat === 'warning') {
    const jours = forfaitStatus.value.jours_restants || 0
    return `<strong>Attention :</strong> Votre forfait expire dans <strong>${jours} jour${jours > 1 ? 's' : ''}</strong>. Veuillez renouveler votre abonnement pour éviter l'interruption de service.`
  }
  
  return ''
})

const alertButtonText = computed(() => {
  if (!forfaitStatus.value) return 'Renouveler'
  const etat = forfaitStatus.value.etat
  if (etat === 'bloque' || etat === 'grace') return 'Renouveler maintenant'
  return 'Renouveler'
})

const goToForfait = () => {
  // Rediriger vers la page de gestion des forfaits
  router.push('/gestion-compte?tab=forfaits')
}

onMounted(() => {
  // Ne vérifier le forfait que si l'utilisateur est authentifié
  if (authStore.isAuthenticated) {
    checkForfait()
    
    // Écouter les événements de changement de forfait
    window.addEventListener('forfait-expired', checkForfait)
    window.addEventListener('storage', (e) => {
      if (e.key === 'forfait_status') {
        loadFromStorage()
      }
    })
  }
})

onUnmounted(() => {
  window.removeEventListener('forfait-expired', checkForfaitStatus)
})
</script>

<style scoped>
.main-layout {
  display: flex;
  width: 100vw;
  background: #f6f7fa;
}


.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100vw - 280px);
  display: flex;
  flex-direction: column;
}

.dashboard-wrapper {
  background: #fff;
  border-radius: 0 32px 32px 0;
  box-shadow: 0 8px 32px 0 rgba(26, 95, 74, 0.10);
  width: 100%;
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
  overflow: hidden;
  position: relative;
  z-index: 1;
}

.page-content {
  padding: calc(1.5rem + 70px) 2rem 2rem 2rem;
  width: 100%;
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow-x: auto;
}

.topbar-sticky {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #fff;
}

@media (max-width: 1100px) {
  .main-content {
    margin-left: 0;
  }
  .dashboard-wrapper {
    border-radius: 0;
  }
  .page-content {
    padding: 1rem 1rem 1rem 1rem;
    padding-top: calc(1rem + 70px);
  }
}

/* Alerte forfait expiré */
.forfait-alert {
  background: #fee2e2;
  border-left: 4px solid #dc2626;
  padding: 1rem 2rem;
  margin: 0;
}

.forfait-alert-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  max-width: 1400px;
  margin: 0 auto;
}

.forfait-alert-icon {
  font-size: 1.5rem;
}

.forfait-alert-message {
  flex: 1;
  color: #991b1b;
  font-weight: 600;
  font-size: 0.95rem;
}

.forfait-alert-btn {
  background: #dc2626;
  color: white;
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.forfait-alert-btn:hover {
  background: #b91c1c;
}

.forfait-alert-btn.urgent {
  background: #dc2626;
  animation: pulse-button 1s infinite;
}

.forfait-alert-btn.urgent:hover {
  background: #b91c1c;
}

.forfait-alert-warning {
  background: #fee2e2;
  border-left-color: #dc2626;
}

.forfait-alert-grace {
  background: #fee2e2;
  border-left-color: #dc2626;
  animation: pulse-alert 2s infinite;
}

.forfait-alert-blocked {
  background: #dc2626;
  border-left-color: #991b1b;
  color: white;
}

.forfait-alert-blocked .forfait-alert-message {
  color: white;
}

.forfait-alert-blocked .forfait-alert-btn {
  background: white;
  color: #dc2626;
}

.forfait-alert-blocked .forfait-alert-btn:hover {
  background: #f3f4f6;
}

@keyframes pulse-alert {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.95;
  }
}

@keyframes pulse-button {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.forfait-alert-btn.urgent {
  background: #dc2626;
  animation: pulse-button 1s infinite;
}

.forfait-alert-btn.urgent:hover {
  background: #b91c1c;
}

.forfait-alert-warning {
  background: #fee2e2;
  border-left-color: #dc2626;
}

.forfait-alert-grace {
  background: #fee2e2;
  border-left-color: #dc2626;
  animation: pulse-alert 2s infinite;
}

.forfait-alert-blocked {
  background: #dc2626;
  border-left-color: #991b1b;
  color: white;
}

.forfait-alert-blocked .forfait-alert-message {
  color: white;
}

.forfait-alert-blocked .forfait-alert-btn {
  background: white;
  color: #dc2626;
}

.forfait-alert-blocked .forfait-alert-btn:hover {
  background: #f3f4f6;
}

@keyframes pulse-alert {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.95;
  }
}

@keyframes pulse-button {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.page-content.blocked {
  opacity: 0.5;
  pointer-events: none;
  position: relative;
}

.page-content.blocked::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  z-index: 1000;
}
</style>