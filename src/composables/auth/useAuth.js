import { ref, computed } from 'vue'
import { useStorage } from '../storage/useStorage.js'
import { apiService } from '../Api/apiService.js'

/**
 * Composable pour gérer l'authentification
 * Utilise le storage pour persister les données
 */
export function useAuth() {
  const storage = useStorage()
  
  // État réactif
  const user = ref(storage.getUser())
  const token = ref(storage.getToken())
  const loading = ref(false)
  const error = ref(null)

  /**
   * Connexion utilisateur
   */
  const login = async (email, password) => {
    loading.value = true
    error.value = null

    try {
      console.log('🔐 Tentative de connexion:', { email })
      const response = await apiService.post('/login.php', {
        email,
        password
      })

      // Debug: afficher la réponse complète
      console.log('✅ Réponse API login reçue:', response)

      if (response && response.success && response.data) {
        const { token: authToken, user: userData, expires_in } = response.data
        
        // Sauvegarder dans le storage
        storage.saveAuthData(authToken, userData, expires_in)
        
        // Mettre à jour l'état
        token.value = authToken
        user.value = userData

        return { success: true, user: userData }
      } else {
        const errorMessage = response?.message || response?.error || 'Erreur lors de la connexion'
        throw new Error(errorMessage)
      }
    } catch (err) {
      console.error('Erreur login:', err)
      error.value = err.message || 'Une erreur est survenue lors de la connexion'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  /**
   * Inscription utilisateur
   */
  const signUp = async (userData) => {
    loading.value = true
    error.value = null

    try {
      const response = await apiService.post('/register.php', userData)

      if (response.success && response.data) {
        const { token: authToken, user: userDataResponse, expires_in } = response.data
        
        // Sauvegarder dans le storage
        storage.saveAuthData(authToken, userDataResponse, expires_in)
        
        // Mettre à jour l'état
        token.value = authToken
        user.value = userDataResponse

        return { success: true, user: userDataResponse }
      } else {
        throw new Error(response.message || 'Erreur lors de l\'inscription')
      }
    } catch (err) {
      error.value = err.message || 'Une erreur est survenue lors de l\'inscription'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  /**
   * Déconnexion
   */
  const logout = () => {
    storage.clearAuthData()
    user.value = null
    token.value = null
    error.value = null
  }

  /**
   * Vérifier l'authentification au chargement
   */
  const checkAuth = () => {
    if (storage.isAuthenticated()) {
      user.value = storage.getUser()
      token.value = storage.getToken()
      return true
    } else {
      logout()
      return false
    }
  }

  /**
   * Récupérer les informations utilisateur depuis le storage
   */
  const refreshUser = () => {
    const storedUser = storage.getUser()
    if (storedUser) {
      user.value = storedUser
    }
  }

  // Computed properties
  const isAuthenticated = computed(() => {
    return !!token.value && !!user.value && storage.isAuthenticated()
  })

  const userRole = computed(() => {
    return user.value?.role || null
  })

  const enterpriseId = computed(() => {
    return user.value?.id_entreprise || null
  })

  // Vérifier l'authentification au chargement
  checkAuth()

  return {
    // État
    user,
    token,
    loading,
    error,
    
    // Computed
    isAuthenticated,
    userRole,
    enterpriseId,
    
    // Méthodes
    login,
    signUp,
    logout,
    checkAuth,
    refreshUser
  }
}
