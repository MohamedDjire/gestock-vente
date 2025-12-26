import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiService } from '../composables/Api/apiService.js'
import router from '../router'

/**
 * Store Pinia pour la gestion de l'authentification
 * Gère le token, les données utilisateur et vérifie l'expiration automatiquement
 */
export const useAuthStore = defineStore('auth', () => {
  // =====================================================
  // ÉTAT (STATE)
  // =====================================================
  const token = ref(localStorage.getItem('prostock_token') || null)
  const user = ref(null)
  try {
    const userStr = localStorage.getItem('prostock_user')
    if (userStr) user.value = JSON.parse(userStr)
  } catch (e) {
    user.value = null
  }
  const loading = ref(false)
  const error = ref(null)

  // =====================================================
  // COMPUTED (GETTERS)
  // =====================================================

  /**
   * Vérifie si l'utilisateur est authentifié
   */
  const isAuthenticated = computed(() => {
    if (!token.value || !user.value) return false
    
    // Vérifier l'expiration du token JWT
    if (isTokenExpired()) {
      // Token expiré, nettoyer automatiquement (mais ne pas appeler logout dans computed)
      // logout() sera appelé ailleurs pour éviter les effets de bord
      return false
    }
    
    return true
  })

  /**
   * Rôle de l'utilisateur
   */
  const userRole = computed(() => {
    return user.value?.role || null
  })

  /**
   * ID de l'entreprise
   */
  const enterpriseId = computed(() => {
    return user.value?.id_entreprise || null
  })

  /**
   * Date d'expiration du token (depuis le JWT)
   */
  const tokenExpiresAt = computed(() => {
    if (!token.value) return null
    
    try {
      const payload = decodeJWT(token.value)
      if (payload && payload.exp) {
        return new Date(payload.exp * 1000) // Convertir timestamp en Date
      }
    } catch (e) {
      console.error('Erreur lors du décodage du token:', e)
    }
    return null
  })

  /**
   * Temps restant avant expiration (en secondes)
   */
  const tokenExpiresIn = computed(() => {
    const expiresAt = tokenExpiresAt.value
    if (!expiresAt) return 0
    
    const now = Math.floor(Date.now() / 1000)
    const exp = Math.floor(expiresAt.getTime() / 1000)
    return Math.max(0, exp - now)
  })

  /**
   * Vérifie si le token est expiré
   */
  function isTokenExpired() {
    if (!token.value) return true
    
    try {
      const payload = decodeJWT(token.value)
      if (!payload || !payload.exp) return true
      
      // Vérifier si la date d'expiration est passée
      const now = Math.floor(Date.now() / 1000)
      return payload.exp < now
    } catch (e) {
      console.error('Erreur lors de la vérification du token:', e)
      return true // En cas d'erreur, considérer comme expiré
    }
  }

  /**
   * Décoder un token JWT (sans vérifier la signature)
   */
  function decodeJWT(token) {
    try {
      const parts = token.split('.')
      if (parts.length !== 3) return null
      
      // Décoder le payload (partie 2)
      const payload = parts[1]
      // Remplacer les caractères Base64Url
      const base64 = payload.replace(/-/g, '+').replace(/_/g, '/')
      // Ajouter le padding si nécessaire
      const padded = base64 + '='.repeat((4 - base64.length % 4) % 4)
      const decoded = atob(padded)
      
      return JSON.parse(decoded)
    } catch (e) {
      console.error('Erreur lors du décodage JWT:', e)
      return null
    }
  }

  // =====================================================
  // ACTIONS
  // =====================================================

  /**
   * Connexion utilisateur
   */
  async function login(email, password) {
    loading.value = true
    error.value = null

    try {
      const response = await apiService.post('/login.php', {
        email,
        password
      })

      if (response && response.success && response.data) {
        const { token: authToken, user: userData, expires_in } = response.data
        
        // Sauvegarder dans le store et localStorage
        setAuthData(authToken, userData)
        
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
  async function signUp(userData) {
    loading.value = true
    error.value = null

    try {
      const response = await apiService.post('/register.php', userData)

      if (response.success && response.data) {
        const { token: authToken, user: userDataResponse } = response.data
        
        // Sauvegarder dans le store et localStorage
        setAuthData(authToken, userDataResponse)
        
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
  function logout() {
    token.value = null
    user.value = null
    error.value = null
    
    // Nettoyer le localStorage
    localStorage.removeItem('prostock_token')
    localStorage.removeItem('prostock_user')
    localStorage.removeItem('prostock_expires_at')
    
    // Rediriger vers la page de login (seulement si router est disponible)
    if (router && typeof router.push === 'function') {
      router.push({ name: 'Login' }).catch(() => {
        // Ignorer les erreurs de navigation
      })
    } else if (typeof window !== 'undefined' && window.location) {
      window.location.href = '/login'
    }
  }

  /**
   * Sauvegarder les données d'authentification
   */
  function setAuthData(authToken, userData) {
    token.value = authToken
    user.value = userData
    
    // Sauvegarder dans localStorage
    localStorage.setItem('prostock_token', authToken)
    localStorage.setItem('prostock_user', JSON.stringify(userData))
    
    // Calculer et sauvegarder la date d'expiration depuis le JWT
    const payload = decodeJWT(authToken)
    if (payload && payload.exp) {
      const expiresAt = payload.exp * 1000 // Convertir en millisecondes
      localStorage.setItem('prostock_expires_at', expiresAt.toString())
    }
  }

  /**
   * Vérifier et nettoyer le token si expiré
   * À appeler au chargement de l'application ou avant chaque requête
   */
  function checkTokenExpiration() {
    if (isTokenExpired()) {
      console.warn('⚠️ Token expiré, déconnexion automatique')
      logout()
      return false
    }
    return true
  }

  /**
   * Initialiser le store depuis localStorage
   */
  function initFromStorage() {
    const storedToken = localStorage.getItem('prostock_token')
    const storedUser = localStorage.getItem('prostock_user')
    
    if (storedToken && storedUser) {
      try {
        token.value = storedToken
        user.value = JSON.parse(storedUser)
        
        // Vérifier l'expiration
        if (isTokenExpired()) {
          console.warn('⚠️ Token expiré au chargement, nettoyage...')
          logout()
        }
      } catch (e) {
        console.error('Erreur lors de l\'initialisation depuis le storage:', e)
        logout()
      }
    }
  }

  // Initialiser au chargement du store
  initFromStorage()

  return {
    // État
    token,
    user,
    loading,
    error,
    
    // Computed
    isAuthenticated,
    userRole,
    enterpriseId,
    tokenExpiresAt,
    tokenExpiresIn,
    
    // Actions
    login,
    signUp,
    logout,
    setAuthData,
    checkTokenExpiration,
    initFromStorage,
    isTokenExpired
  }
})


