import { useCurrencyStore } from '../stores/currency'

/**
 * Composable pour utiliser le store de devises
 * Fournit des fonctions utilitaires pour la conversion et le formatage
 */
export function useCurrency() {
  const currencyStore = useCurrencyStore()

  /**
   * Formate un prix avec la devise sélectionnée
   * @param {number} price - Prix à formater
   * @param {string} sourceCurrency - Devise source (par défaut: 'XOF' car les valeurs en DB sont en F CFA)
   * @param {object} options - Options de formatage
   * @returns {string} Prix formaté
   */
  const formatPrice = (price, sourceCurrency = 'XOF', options = {}) => {
    return currencyStore.formatPrice(price, sourceCurrency, options)
  }

  /**
   * Convertit un montant d'une devise à une autre
   * @param {number} amount - Montant à convertir
   * @param {string} fromCurrency - Code de la devise source
   * @param {string} toCurrency - Code de la devise cible (optionnel, utilise la devise sélectionnée par défaut)
   * @returns {number} Montant converti
   */
  const convert = (amount, fromCurrency = 'USD', toCurrency = null) => {
    return currencyStore.convert(amount, fromCurrency, toCurrency)
  }

  /**
   * Obtient la variation d'une devise
   * @param {string} currencyCode - Code de la devise
   * @returns {object|null} Objet avec value, isPositive, current, previous ou null
   */
  const getVariation = (currencyCode) => {
    return currencyStore.getCurrencyVariation(currencyCode)
  }

  return {
    currency: currencyStore.currency,
    currencySymbol: currencyStore.currencySymbols[currencyStore.currency] || currencyStore.currency,
    formatPrice,
    convert,
    getVariation,
    setCurrency: currencyStore.setCurrency,
    availableCurrencies: currencyStore.availableCurrencies
  }
}
