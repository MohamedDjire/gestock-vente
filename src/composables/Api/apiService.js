import axios from 'axios'

/**
 * Service API pour les appels HTTP
 * Configure axios avec l'URL de base et les intercepteurs
 */

// URL de base de l'API - À adapter selon votre configuration
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api-stock'

// Créer une instance axios
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Intercepteur pour ajouter le token aux requêtes
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('prostock_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
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
      const message = error.response.data?.message || 'Une erreur est survenue'
      return Promise.reject(new Error(message))
    } else if (error.request) {
      // Requête envoyée mais pas de réponse
      return Promise.reject(new Error('Aucune réponse du serveur'))
    } else {
      // Erreur lors de la configuration de la requête
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
