import { ref, computed } from 'vue'
import { apiService } from './Api/apiService.js'

/**
 * Composable pour gérer le forfait de l'entreprise
 * Vérifie le statut du forfait et bloque les actions si expiré
 */
export function useForfait() {
  const forfaitStatus = ref(null)
  const loading = ref(false)
  const lastCheck = ref(null)
  const checkInterval = ref(null)

  /**
   * Vérifie le statut du forfait
   */
  const checkForfait = async () => {
    try {
      loading.value = true
      const response = await apiService.get('/api_forfait.php?action=status')
      
      if (response && response.success) {
        forfaitStatus.value = response.data
        lastCheck.value = new Date()
        
        console.log('✅ Forfait chargé:', forfaitStatus.value)
        
        // Sauvegarder dans localStorage pour synchronisation entre onglets
        localStorage.setItem('forfait_status', JSON.stringify(response.data))
        localStorage.setItem('forfait_last_check', lastCheck.value.toISOString())
      } else {
        console.warn('⚠️ Réponse API invalide:', response)
        // Si pas de réponse valide, créer un statut par défaut pour l'affichage
        forfaitStatus.value = {
          actif: false,
          date_fin: null,
          expire: true,
          no_subscription: true
        }
        localStorage.setItem('forfait_status', JSON.stringify(forfaitStatus.value))
      }
    } catch (error) {
      console.error('❌ Erreur lors de la vérification du forfait:', error)
      // En cas d'erreur, créer un statut par défaut pour l'affichage
      forfaitStatus.value = {
        actif: false,
        date_fin: null,
        expire: true,
        no_subscription: true,
        error: error.message
      }
      localStorage.setItem('forfait_status', JSON.stringify(forfaitStatus.value))
    } finally {
      loading.value = false
    }
  }

  /**
   * Vérification rapide (pour les vérifications périodiques)
   */
  const quickCheck = async () => {
    try {
      const response = await apiService.get('/api_forfait.php?action=check')
      if (response && response.success) {
        if (!response.data.actif) {
          // Si le forfait n'est plus actif, faire une vérification complète
          await checkForfait()
        }
      }
    } catch (error) {
      console.error('Erreur lors de la vérification rapide:', error)
    }
  }

  /**
   * Démarre la vérification automatique toutes les 5 minutes
   */
  const startAutoCheck = () => {
    // Vérifier immédiatement
    checkForfait()
    
    // Puis toutes les 5 minutes (300000 ms)
    if (checkInterval.value) {
      clearInterval(checkInterval.value)
    }
    
    checkInterval.value = setInterval(() => {
      quickCheck()
    }, 5 * 60 * 1000) // 5 minutes
  }

  /**
   * Arrête la vérification automatique
   */
  const stopAutoCheck = () => {
    if (checkInterval.value) {
      clearInterval(checkInterval.value)
      checkInterval.value = null
    }
  }

  /**
   * Vérifie si le forfait est actif
   */
  const isActive = computed(() => {
    return forfaitStatus.value?.actif === true
  })

  /**
   * Vérifie si le forfait est expiré
   */
  const isExpired = computed(() => {
    // Ne considérer comme expiré que si ce n'est pas un cas de "no_subscription"
    if (forfaitStatus.value?.no_subscription) {
      return false // Pas encore d'abonnement, on ne bloque pas
    }
    return forfaitStatus.value?.expire === true || (forfaitStatus.value?.actif === false && !forfaitStatus.value?.no_subscription)
  })

  /**
   * Obtient les jours restants
   */
  const joursRestants = computed(() => {
    if (!forfaitStatus.value?.date_fin) return null
    
    const dateFin = new Date(forfaitStatus.value.date_fin)
    const now = new Date()
    const diff = dateFin - now
    const jours = Math.ceil(diff / (1000 * 60 * 60 * 24))
    
    return jours > 0 ? jours : 0
  })

  /**
   * Obtient la couleur selon le temps restant
   */
  const statusColor = computed(() => {
    const jours = joursRestants.value
    
    if (jours === null || isExpired.value) return 'red'
    if (jours <= 2) return 'red'
    if (jours <= 7) return 'orange'
    return 'black'
  })

  /**
   * Formate la date d'expiration
   */
  const formatDateExpiration = computed(() => {
    if (!forfaitStatus.value?.date_fin) return 'N/A'
    
    try {
      const date = new Date(forfaitStatus.value.date_fin)
      if (isNaN(date.getTime())) return 'Date invalide'
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    } catch (e) {
      console.error('Erreur formatage date:', e)
      return 'Erreur'
    }
  })

  /**
   * Charge le statut depuis localStorage (pour synchronisation entre onglets)
   */
  const loadFromStorage = () => {
    try {
      const saved = localStorage.getItem('forfait_status')
      if (saved) {
        forfaitStatus.value = JSON.parse(saved)
        console.log('📥 Forfait chargé depuis localStorage:', forfaitStatus.value)
      } else {
        console.log('ℹ️ Aucun forfait dans localStorage')
      }
    } catch (error) {
      console.error('❌ Erreur lors du chargement du forfait depuis localStorage:', error)
    }
  }

  /**
   * Vérifie si une action est autorisée
   */
  const canPerformAction = (actionType = 'general') => {
    // La connexion est toujours autorisée
    if (actionType === 'login') return true
    
    // Pour toutes les autres actions, vérifier que le forfait est actif
    return isActive.value
  }

  return {
    forfaitStatus,
    loading,
    isActive,
    isExpired,
    joursRestants,
    statusColor,
    formatDateExpiration,
    checkForfait,
    quickCheck,
    startAutoCheck,
    stopAutoCheck,
    loadFromStorage,
    canPerformAction
  }
}
