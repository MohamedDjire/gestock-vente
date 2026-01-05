<template>
  <div class="main-layout">
    <Sidebar v-if="isAuthenticated" />
    <div class="main-content">
      <div class="dashboard-wrapper">
        <div v-if="isAuthenticated" class="topbar-sticky">
          <Topbar />
        </div>
        <!-- Alerte forfait expiré -->
        <div v-if="forfaitExpired" class="forfait-alert">
          <div class="forfait-alert-content">
            <span class="forfait-alert-icon">⚠️</span>
            <span class="forfait-alert-message">
              Votre forfait a expiré. Veuillez renouveler votre abonnement pour continuer à utiliser l'application.
            </span>
            <button @click="goToForfait" class="forfait-alert-btn">Renouveler</button>
          </div>
        </div>
        <div class="page-content" :class="{ 'blocked': forfaitExpired }">
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
const forfaitExpired = ref(false)

const checkForfaitStatus = () => {
  loadFromStorage()
  const status = localStorage.getItem('forfait_status')
  if (status) {
    try {
      const parsed = JSON.parse(status)
      // Ne bloquer que si le forfait est expiré ET qu'il n'est pas en "no_subscription"
      // (no_subscription signifie qu'il n'y a pas encore d'abonnement, on ne bloque pas dans ce cas)
      forfaitExpired.value = (parsed.expire === true || parsed.actif === false) && !parsed.no_subscription
    } catch (e) {
      console.error('Erreur lors de la vérification du forfait:', e)
    }
  }
}

const goToForfait = () => {
  // Rediriger vers la page de gestion des forfaits
  router.push('/settings')
}

onMounted(() => {
  // Ne vérifier le forfait que si l'utilisateur est authentifié
  if (authStore.isAuthenticated) {
    checkForfaitStatus()
    checkForfait()
    
    // Écouter les événements de changement de forfait
    window.addEventListener('forfait-expired', checkForfaitStatus)
    window.addEventListener('storage', (e) => {
      if (e.key === 'forfait_status') {
        checkForfaitStatus()
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