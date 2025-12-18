/**
 * Composable pour gérer le localStorage
 * Gère le stockage et la récupération des données utilisateur et du token
 */

export function useStorage() {
  const STORAGE_KEYS = {
    TOKEN: 'prostock_token',
    USER: 'prostock_user',
    EXPIRES_AT: 'prostock_expires_at'
  }

  /**
   * Sauvegarder le token d'authentification
   */
  const setToken = (token) => {
    if (token) {
      localStorage.setItem(STORAGE_KEYS.TOKEN, token)
    } else {
      localStorage.removeItem(STORAGE_KEYS.TOKEN)
    }
  }

  /**
   * Récupérer le token d'authentification
   */
  const getToken = () => {
    return localStorage.getItem(STORAGE_KEYS.TOKEN)
  }

  /**
   * Sauvegarder les informations utilisateur
   */
  const setUser = (user) => {
    if (user) {
      localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user))
    } else {
      localStorage.removeItem(STORAGE_KEYS.USER)
    }
  }

  /**
   * Récupérer les informations utilisateur
   */
  const getUser = () => {
    const userStr = localStorage.getItem(STORAGE_KEYS.USER)
    if (userStr) {
      try {
        return JSON.parse(userStr)
      } catch (e) {
        console.error('Erreur lors de la lecture des données utilisateur:', e)
        return null
      }
    }
    return null
  }

  /**
   * Sauvegarder la date d'expiration du token
   */
  const setExpiresAt = (expiresIn) => {
    if (expiresIn) {
      const expiresAt = Date.now() + (expiresIn * 1000)
      localStorage.setItem(STORAGE_KEYS.EXPIRES_AT, expiresAt.toString())
    } else {
      localStorage.removeItem(STORAGE_KEYS.EXPIRES_AT)
    }
  }

  /**
   * Vérifier si le token est expiré
   */
  const isTokenExpired = () => {
    const expiresAt = localStorage.getItem(STORAGE_KEYS.EXPIRES_AT)
    if (!expiresAt) return true
    return Date.now() > parseInt(expiresAt)
  }

  /**
   * Sauvegarder toutes les données d'authentification
   */
  const saveAuthData = (token, user, expiresIn) => {
    setToken(token)
    setUser(user)
    setExpiresAt(expiresIn)
  }

  /**
   * Supprimer toutes les données d'authentification
   */
  const clearAuthData = () => {
    localStorage.removeItem(STORAGE_KEYS.TOKEN)
    localStorage.removeItem(STORAGE_KEYS.USER)
    localStorage.removeItem(STORAGE_KEYS.EXPIRES_AT)
  }

  /**
   * Vérifier si l'utilisateur est authentifié
   */
  const isAuthenticated = () => {
    const token = getToken()
    const user = getUser()
    return token && user && !isTokenExpired()
  }

  return {
    setToken,
    getToken,
    setUser,
    getUser,
    setExpiresAt,
    isTokenExpired,
    saveAuthData,
    clearAuthData,
    isAuthenticated
  }
}
