import axios from 'axios'
import { getLocalStorage, setLocalStorage, removeLocalStorage } from '../../utils/safeStorage.js'

/**
 * Service API pour les appels HTTP
 * Configure axios avec l'URL de base et les intercepteurs
 * Utilise safeStorage pour éviter les erreurs si "Tracking Prevention" bloque l'accès.
 */


// URL de base de l'API
// - En dev: on passe par le proxy Vite (/api-stock) pour éviter CORS
// - En prod: par défaut on utilise aussi /api-stock (même origine), sauf si surchargé par VITE_API_BASE_URL
const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ||
  '/api-stock'

// Créer une instance axios
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Intercepteur pour ajouter le token aux requêtes et vérifier l'expiration
apiClient.interceptors.request.use(
  (config) => {
    // Récupérer le token depuis localStorage (évite la dépendance circulaire)
    const token = getLocalStorage('prostock_token')
    
    if (token) {
      // Décoder le JWT pour vérifier l'expiration
      try {
        const parts = token.split('.')
        if (parts.length === 3) {
          const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')))
          if (payload.exp && payload.exp < Math.floor(Date.now() / 1000)) {
            // Token expiré, nettoyer et rediriger
            console.warn('⚠️ Token expiré, nettoyage automatique')
            removeLocalStorage('prostock_token')
            removeLocalStorage('prostock_user')
            removeLocalStorage('prostock_expires_at')
            
            // Rediriger vers login si on est dans le navigateur
            if (typeof window !== 'undefined' && window.location) {
              window.location.href = '/login'
            }
            
            return Promise.reject(new Error('Token expiré'))
          }
        }
      } catch (e) {
        console.error('Erreur lors de la vérification du token:', e)
      }
      
      // Ajouter le token : Authorization uniquement (évite CORS sur les API qui n’autorisent pas X-Auth-Token)
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // Vérifier le forfait pour toutes les requêtes sauf login, signup et vérification du forfait
    const url = config.url || ''
    const isAuthRoute = url.includes('login.php') || url.includes('register.php') || url.includes('api_forfait.php')
    
    if (!isAuthRoute && token) {
      // Vérifier le statut du forfait depuis localStorage
      try {
        const forfaitStatus = getLocalStorage('forfait_status')
        if (forfaitStatus) {
          const status = JSON.parse(forfaitStatus)
          // Ne bloquer que si :
          // 1. Le forfait est explicitement expiré (pas juste "no_subscription")
          // 2. Il n'y a pas d'erreur de connexion
          // 3. Ce n'est pas un cas de "no_subscription" (première utilisation)
          if ((status.expire === true || status.actif === false) && !status.error && !status.no_subscription) {
            // Forfait expiré, bloquer la requête
            return Promise.reject(new Error('FORFAIT_EXPIRE'))
          }
        }
      } catch (e) {
        console.error('Erreur lors de la vérification du forfait:', e)
      }
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Intercepteur pour gérer les erreurs de réponse
apiClient.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    // Vérifier si c'est une erreur de forfait expiré
    if (error.message === 'FORFAIT_EXPIRE' || (error.response && error.response.status === 403 && error.response.data?.message?.includes('forfait'))) {
      // Mettre à jour le statut du forfait
      try {
        const forfaitStatus = getLocalStorage('forfait_status')
        if (forfaitStatus) {
          const status = JSON.parse(forfaitStatus)
          status.expire = true
          status.actif = false
          setLocalStorage('forfait_status', JSON.stringify(status))
        }
      } catch (e) {
        console.error('Erreur lors de la mise à jour du statut du forfait:', e)
      }
      
      // Émettre un événement pour notifier les composants
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('forfait-expired'))
      }
      
      return Promise.reject({
        message: 'Votre forfait a expiré. Veuillez renouveler votre abonnement pour continuer.',
        code: 'FORFAIT_EXPIRE'
      })
    }
    
    if (error.response) {
      // Erreur de réponse du serveur
      const status = error.response.status
      const data = error.response.data
      
      // Si erreur 401 (Unauthorized), le token est probablement invalide ou expiré
      if (status === 401) {
        console.warn('⚠️ Erreur 401 - Token invalide ou expiré, nettoyage automatique...')
        removeLocalStorage('prostock_token')
        removeLocalStorage('prostock_user')
        removeLocalStorage('prostock_expires_at')
        
        // Rediriger vers login si on est dans le navigateur
        if (typeof window !== 'undefined' && window.location) {
          window.location.href = '/login'
        }
      }
      
      // Afficher plus de détails en console pour le débogage
      console.error('❌ Erreur API:', {
        status,
        url: error.config?.url,
        baseURL: error.config?.baseURL,
        data: data,
        fullResponse: error.response
      })
      
      let message = 'Une erreur est survenue'
      
      // Parser la réponse si c'est une string JSON
      let parsedData = data
      if (typeof data === 'string') {
        try {
          parsedData = JSON.parse(data)
        } catch (e) {
          // Si ce n'est pas du JSON, utiliser la string directement
          message = data
          return Promise.reject(new Error(message))
        }
      }
      
      if (status === 500) {
        message = parsedData?.message || parsedData?.error || 'Erreur serveur (500). Vérifiez que l\'API est accessible et que la base de données est configurée correctement.'
      } else if (parsedData?.message) {
        message = parsedData.message
      } else if (parsedData?.error) {
        message = parsedData.error
      } else if (typeof parsedData === 'string') {
        message = parsedData
      }
      
      return Promise.reject(new Error(message))
    } else if (error.request) {
      // Requête envoyée mais pas de réponse
      console.error('⚠️ Aucune réponse du serveur:', {
        url: error.config?.url,
        baseURL: error.config?.baseURL
      })
      return Promise.reject(new Error('Aucune réponse du serveur. Vérifiez que l\'API est accessible à l\'URL: ' + (error.config?.baseURL || API_BASE_URL)))
    } else {
      // Erreur lors de la configuration de la requête
      console.error('❌ Erreur de configuration:', error)
      return Promise.reject(error)
    }
  }
)

export const apiService = {
  get: (url, config = {}) => apiClient.get(url, config),
  post: (url, data = {}, config = {}) => apiClient.post(url, data, config),
  put: (url, data = {}, config = {}) => apiClient.put(url, data, config),
  delete: (url, config = {}) => apiClient.delete(url, config)
}








