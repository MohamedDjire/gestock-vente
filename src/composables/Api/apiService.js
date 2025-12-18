import axios from 'axios'
import { useAuthStore } from '../../stores/auth.js'

/**
 * Service API pour les appels HTTP
 * Configure axios avec l'URL de base et les intercepteurs
 */

// URL de base de l'API - À adapter selon votre configuration
// Par défaut : https://aliadjame.com/api-stock
// Pour override : créez un fichier .env avec VITE_API_BASE_URL=votre-url
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://aliadjame.com/api-stock'

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
    // Utiliser le store pour vérifier l'expiration et récupérer le token
    const authStore = useAuthStore()
    
    // Vérifier l'expiration avant chaque requête
    if (authStore.token && authStore.isTokenExpired()) {
      console.warn('⚠️ Token expiré, nettoyage automatique')
      authStore.logout()
      return Promise.reject(new Error('Token expiré'))
    }
    
    // Ajouter le token aux headers si disponible
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
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
    if (error.response) {
      // Erreur de réponse du serveur
      const status = error.response.status
      const data = error.response.data
      
      // Si erreur 401 (Unauthorized), le token est probablement invalide ou expiré
      if (status === 401) {
        console.warn('⚠️ Erreur 401 - Token invalide ou expiré, nettoyage automatique...')
        const authStore = useAuthStore()
        authStore.logout()
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
