/**
 * Helpers pour accéder à localStorage de façon sécurisée.
 * Gère le blocage par "Tracking Prevention" (Safari, Firefox strict, Brave, etc.)
 * qui peut lever SecurityError ou bloquer l'accès. En cas d'erreur, on dégrade
 * gracieusement (get => null, set/remove => no-op).
 */

function safeGet (key) {
  try {
    return localStorage.getItem(key)
  } catch (e) {
    // Tracking Prevention, SecurityError, disabled storage, etc.
    return null
  }
}

function safeSet (key, value) {
  try {
    if (value != null) localStorage.setItem(key, value)
    else localStorage.removeItem(key)
  } catch (e) {
    // Blocage silencieux (Tracking Prevention, SecurityError, etc.)
  }
}

function safeRemove (key) {
  try {
    localStorage.removeItem(key)
  } catch (e) {}
}

function safeClear () {
  try {
    localStorage.clear()
  } catch (e) {}
}

export {
  safeGet as getLocalStorage,
  safeSet as setLocalStorage,
  safeRemove as removeLocalStorage,
  safeClear as clearLocalStorage
}
