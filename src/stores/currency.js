import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

/**
 * Store de gestion des devises avec conversion automatique
 * et suivi des variations dans le temps
 */
export const useCurrencyStore = defineStore('currency', () => {
  // Devise actuellement sélectionnée
  const currency = ref(localStorage.getItem('selectedCurrency') || 'XOF')
  
  // Taux de change (base: 1 USD)
  // Les taux sont mis à jour périodiquement ou via API
  const exchangeRates = ref({
    USD: 1,
    EUR: 0.92,
    XOF: 600,        // Franc CFA Ouest-Africain
    XAF: 600,        // Franc CFA Centrafricain
    NGN: 1500,       // Naira nigérian
    ZAR: 18.5,      // Rand sud-africain
    EGP: 30.9,      // Livre égyptienne
    MAD: 10.1,      // Dirham marocain
    TND: 3.1,       // Dinar tunisien
    DZD: 134.5,     // Dinar algérien
    GHS: 12.3,      // Cedi ghanéen
    KES: 130,       // Shilling kényan
    UGX: 3700,      // Shilling ougandais
    TZS: 2300,      // Shilling tanzanien
    ETB: 55,        // Birr éthiopien
    RWF: 1300,      // Franc rwandais
    MZN: 64,        // Metical mozambicain
    AOA: 830,       // Kwanza angolais
    CDF: 2700,      // Franc congolais
    XAF: 600,       // Franc CFA BEAC
    CNY: 7.1,       // Yuan chinois
    GBP: 0.79,      // Livre sterling
    JPY: 150,       // Yen japonais
    INR: 83,        // Roupie indienne
    BRL: 5.0,       // Real brésilien
    CAD: 1.35,      // Dollar canadien
    AUD: 1.52,      // Dollar australien
    CHF: 0.88,      // Franc suisse
    AED: 3.67,      // Dirham émirati
    SAR: 3.75,      // Riyal saoudien
    TRY: 32.5       // Lire turque
  })

  // Symboles des devises
  const currencySymbols = ref({
    USD: '$',
    EUR: '€',
    XOF: 'F CFA',
    XAF: 'F CFA',
    NGN: '₦',
    ZAR: 'R',
    EGP: 'E£',
    MAD: 'د.م.',
    TND: 'د.ت',
    DZD: 'د.ج',
    GHS: '₵',
    KES: 'KSh',
    UGX: 'USh',
    TZS: 'TSh',
    ETB: 'Br',
    RWF: 'RF',
    MZN: 'MT',
    AOA: 'Kz',
    CDF: 'FC',
    CNY: '¥',
    GBP: '£',
    JPY: '¥',
    INR: '₹',
    BRL: 'R$',
    CAD: 'C$',
    AUD: 'A$',
    CHF: 'CHF',
    AED: 'د.إ',
    SAR: '﷼',
    TRY: '₺'
  })

  // Noms complets des devises
  const currencyNames = ref({
    USD: 'Dollar US',
    EUR: 'Euro',
    XOF: 'Franc CFA Ouest-Africain',
    XAF: 'Franc CFA Centrafricain',
    NGN: 'Naira Nigérian',
    ZAR: 'Rand Sud-Africain',
    EGP: 'Livre Égyptienne',
    MAD: 'Dirham Marocain',
    TND: 'Dinar Tunisien',
    DZD: 'Dinar Algérien',
    GHS: 'Cedi Ghanéen',
    KES: 'Shilling Kényan',
    UGX: 'Shilling Ougandais',
    TZS: 'Shilling Tanzanien',
    ETB: 'Birr Éthiopien',
    RWF: 'Franc Rwandais',
    MZN: 'Metical Mozambicain',
    AOA: 'Kwanza Angolais',
    CDF: 'Franc Congolais',
    CNY: 'Yuan Chinois',
    GBP: 'Livre Sterling',
    JPY: 'Yen Japonais',
    INR: 'Roupie Indienne',
    BRL: 'Real Brésilien',
    CAD: 'Dollar Canadien',
    AUD: 'Dollar Australien',
    CHF: 'Franc Suisse',
    AED: 'Dirham Émirati',
    SAR: 'Riyal Saoudien',
    TRY: 'Lire Turque'
  })

  // Historique des variations (pour tracking)
  const rateHistory = ref({})
  
  // Date de dernière mise à jour
  const lastUpdate = ref(null)

  // Liste des devises organisées par région
  const currenciesByRegion = computed(() => ({
    'Afrique de l\'Ouest': ['XOF', 'NGN', 'GHS'],
    'Afrique Centrale': ['XAF', 'CDF', 'AOA'],
    'Afrique de l\'Est': ['KES', 'UGX', 'TZS', 'ETB', 'RWF'],
    'Afrique du Nord': ['EGP', 'MAD', 'TND', 'DZD'],
    'Afrique Australe': ['ZAR', 'MZN'],
    'Internationales': ['USD', 'EUR', 'GBP', 'CNY', 'JPY', 'INR'],
    'Autres': ['BRL', 'CAD', 'AUD', 'CHF', 'AED', 'SAR', 'TRY']
  }))

  // Toutes les devises disponibles
  const availableCurrencies = computed(() => {
    return Object.keys(exchangeRates.value).map(code => ({
      code,
      name: currencyNames.value[code] || code,
      symbol: currencySymbols.value[code] || code,
      rate: exchangeRates.value[code]
    }))
  })

  // Variation de la devise (comparaison avec la valeur précédente)
  const getCurrencyVariation = computed(() => {
    return (currencyCode) => {
      if (!rateHistory.value[currencyCode] || rateHistory.value[currencyCode].length < 2) {
        return null
      }
      const history = rateHistory.value[currencyCode]
      const current = history[history.length - 1].rate
      const previous = history[history.length - 2].rate
      const variation = ((current - previous) / previous) * 100
      return {
        value: variation,
        isPositive: variation >= 0,
        current,
        previous
      }
    }
  })

  /**
   * Convertit un montant d'une devise à une autre
   * @param {number} amount - Montant à convertir
   * @param {string} fromCurrency - Code de la devise source (par défaut: USD)
   * @param {string} toCurrency - Code de la devise cible (par défaut: devise sélectionnée)
   * @returns {number} Montant converti
   */
  const convert = (amount, fromCurrency = 'USD', toCurrency = null) => {
    if (!amount || amount === 0) return 0
    
    const targetCurrency = toCurrency || currency.value
    const fromRate = exchangeRates.value[fromCurrency] || 1
    const toRate = exchangeRates.value[targetCurrency] || 1
    
    // Conversion: USD -> Devise cible
    const amountInUSD = amount / fromRate
    const convertedAmount = amountInUSD * toRate
    
    return convertedAmount
  }

  /**
   * Formate un prix avec la devise sélectionnée
   * @param {number} price - Prix à formater
   * @param {string} sourceCurrency - Devise source (par défaut: USD)
   * @param {object} options - Options de formatage
   * @returns {string} Prix formaté
   */
  const formatPrice = (price, sourceCurrency = 'USD', options = {}) => {
    if (!price && price !== 0) return `0 ${currencySymbols.value[currency.value]}`
    
    const converted = convert(price, sourceCurrency, currency.value)
    const symbol = currencySymbols.value[currency.value] || currency.value
    const cur = currency.value
    
    // Devises avec formatage spécial (USD, EUR, GBP, etc.)
    if (['USD', 'EUR', 'GBP', 'CNY', 'JPY', 'INR', 'BRL', 'CAD', 'AUD', 'CHF', 'AED', 'SAR'].includes(cur)) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: cur === 'XOF' ? 'XOF' : cur,
        minimumFractionDigits: options.minDecimals || 0,
        maximumFractionDigits: options.maxDecimals || 2
      }).format(converted)
    }
    
    // Autres devises (formatage manuel)
    const rounded = options.round !== false ? Math.round(converted) : converted
    return `${rounded.toLocaleString('fr-FR', {
      minimumFractionDigits: options.minDecimals || 0,
      maximumFractionDigits: options.maxDecimals || 2
    })} ${symbol}`
  }

  /**
   * Met à jour les taux de change
   * @param {object} newRates - Nouveaux taux de change
   */
  const updateRates = (newRates) => {
    // Sauvegarder l'historique avant mise à jour
    Object.keys(newRates).forEach(code => {
      if (!rateHistory.value[code]) {
        rateHistory.value[code] = []
      }
      rateHistory.value[code].push({
        rate: newRates[code],
        date: new Date().toISOString()
      })
      // Garder seulement les 30 derniers jours
      if (rateHistory.value[code].length > 30) {
        rateHistory.value[code].shift()
      }
    })
    
    exchangeRates.value = { ...exchangeRates.value, ...newRates }
    lastUpdate.value = new Date().toISOString()
    
    // Sauvegarder dans localStorage
    localStorage.setItem('currencyRates', JSON.stringify(exchangeRates.value))
    localStorage.setItem('currencyRateHistory', JSON.stringify(rateHistory.value))
    localStorage.setItem('currencyLastUpdate', lastUpdate.value)
  }

  /**
   * Met à jour les taux de change depuis une API externe
   * Utilise l'API gratuite exchangerate-api.com
   */
  const fetchLatestRates = async () => {
    try {
      // API gratuite pour les taux de change (sans clé API requise)
      // Alternative: utiliser exchangerate-api.com ou fixer.io avec clé API
      const response = await fetch('https://api.exchangerate-api.com/v4/latest/USD')
      
      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`)
      }
      
      const data = await response.json()
      
      if (data.rates) {
        // Convertir les taux de l'API (base USD) vers notre format
        const newRates = {
          USD: 1,
          EUR: data.rates.EUR || exchangeRates.value.EUR,
          GBP: data.rates.GBP || exchangeRates.value.GBP,
          JPY: data.rates.JPY || exchangeRates.value.JPY,
          CNY: data.rates.CNY || exchangeRates.value.CNY,
          INR: data.rates.INR || exchangeRates.value.INR,
          BRL: data.rates.BRL || exchangeRates.value.BRL,
          CAD: data.rates.CAD || exchangeRates.value.CAD,
          AUD: data.rates.AUD || exchangeRates.value.AUD,
          CHF: data.rates.CHF || exchangeRates.value.CHF,
          AED: data.rates.AED || exchangeRates.value.AED,
          SAR: data.rates.SAR || exchangeRates.value.SAR,
          TRY: data.rates.TRY || exchangeRates.value.TRY,
          // Pour les devises africaines, utiliser des sources spécialisées ou garder les valeurs par défaut
          // car elles ne sont pas toujours disponibles dans les APIs gratuites
          XOF: data.rates.XOF || exchangeRates.value.XOF,
          XAF: data.rates.XAF || exchangeRates.value.XAF,
          NGN: data.rates.NGN || exchangeRates.value.NGN,
          ZAR: data.rates.ZAR || exchangeRates.value.ZAR,
          EGP: data.rates.EGP || exchangeRates.value.EGP,
          MAD: data.rates.MAD || exchangeRates.value.MAD,
          TND: data.rates.TND || exchangeRates.value.TND,
          DZD: data.rates.DZD || exchangeRates.value.DZD,
          GHS: data.rates.GHS || exchangeRates.value.GHS,
          KES: data.rates.KES || exchangeRates.value.KES,
          UGX: data.rates.UGX || exchangeRates.value.UGX,
          TZS: data.rates.TZS || exchangeRates.value.TZS,
          ETB: data.rates.ETB || exchangeRates.value.ETB,
          RWF: data.rates.RWF || exchangeRates.value.RWF,
          MZN: data.rates.MZN || exchangeRates.value.MZN,
          AOA: data.rates.AOA || exchangeRates.value.AOA,
          CDF: data.rates.CDF || exchangeRates.value.CDF
        }
        
        updateRates(newRates)
        console.log('✅ Taux de change mis à jour avec succès')
        return true
      }
    } catch (error) {
      console.warn('⚠️ Impossible de mettre à jour les taux de change depuis l\'API:', error.message)
      console.log('📌 Utilisation des taux de change par défaut')
      return false
    }
  }

  /**
   * Charge les taux depuis localStorage ou API
   */
  const loadRates = async () => {
    // Charger depuis localStorage
    const savedRates = localStorage.getItem('currencyRates')
    const savedHistory = localStorage.getItem('currencyRateHistory')
    const savedUpdate = localStorage.getItem('currencyLastUpdate')
    
    if (savedRates) {
      try {
        exchangeRates.value = { ...exchangeRates.value, ...JSON.parse(savedRates) }
      } catch (e) {
        console.error('Erreur lors du chargement des taux:', e)
      }
    }
    
    if (savedHistory) {
      try {
        rateHistory.value = JSON.parse(savedHistory)
      } catch (e) {
        console.error('Erreur lors du chargement de l\'historique:', e)
      }
    }
    
    if (savedUpdate) {
      lastUpdate.value = savedUpdate
    }
    
    // Vérifier si les taux sont à jour (mise à jour quotidienne recommandée)
    const shouldUpdate = !lastUpdate.value || 
      (new Date() - new Date(lastUpdate.value)) > 24 * 60 * 60 * 1000
    
    if (shouldUpdate) {
      // Mettre à jour les taux depuis l'API
      await fetchLatestRates()
    }
  }

  /**
   * Change la devise sélectionnée
   * @param {string} newCurrency - Code de la nouvelle devise
   */
  const setCurrency = (newCurrency) => {
    if (exchangeRates.value[newCurrency]) {
      currency.value = newCurrency
      localStorage.setItem('selectedCurrency', newCurrency)
    }
  }

  // Initialiser au chargement
  loadRates()

  return {
    // State
    currency,
    exchangeRates,
    currencySymbols,
    currencyNames,
    rateHistory,
    lastUpdate,
    
    // Computed
    currenciesByRegion,
    availableCurrencies,
    getCurrencyVariation,
    
    // Methods
    convert,
    formatPrice,
    updateRates,
    loadRates,
    fetchLatestRates,
    setCurrency
  }
})







