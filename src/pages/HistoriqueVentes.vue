<template>
  <div class="historique-ventes-page">
    <div class="page-header">
      <h1>Historique des Ventes</h1>
      <button @click="$router.push('/ventes')" class="btn-back">
        ← Retour aux ventes
      </button>
    </div>

    <div v-if="selectedPointVente" class="point-vente-header">
      <h2>🏪 {{ selectedPointVente.nom_point_vente }}</h2>
    </div>

    <!-- Sélecteur de période -->
    <div class="period-selector-section">
      <div class="period-selector">
        <label>Période:</label>
        <select v-model="selectedPeriod" @change="updatePeriodDates" class="period-select">
          <option value="jour">Aujourd'hui</option>
          <option value="semaine">Cette semaine</option>
          <option value="mois">Ce mois</option>
          <option value="annee">Cette année</option>
          <option value="personnalise">Personnalisé</option>
        </select>
      </div>
      <div v-if="selectedPeriod === 'personnalise'" class="custom-dates">
        <div class="filter-group">
          <label>Date début:</label>
          <input v-model="dateDebut" type="date" class="date-input" @change="applyFilters" />
        </div>
        <div class="filter-group">
          <label>Date fin:</label>
          <input v-model="dateFin" type="date" class="date-input" @change="applyFilters" />
        </div>
      </div>
      <div v-else class="period-info">
        <span class="period-label">{{ periodLabel }}</span>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
          <div class="stat-label">Total Ventes</div>
          <div class="stat-value">{{ formatPrice(stats.totalVentes) }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
          <div class="stat-label">Nombre de Ventes</div>
          <div class="stat-value">{{ stats.nombreVentes }}</div>
        </div>
      </div>
      <div class="stat-card cancelled">
        <div class="stat-icon">❌</div>
        <div class="stat-content">
          <div class="stat-label">Ventes Annulées</div>
          <div class="stat-value">{{ stats.ventesAnnulees }}</div>
        </div>
      </div>
      <div class="stat-card credit">
        <div class="stat-icon">💳</div>
        <div class="stat-content">
          <div class="stat-label">Total Crédits</div>
          <div class="stat-value">{{ formatPrice(stats.totalCredits) }}</div>
        </div>
      </div>
      <div class="stat-card partial">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
          <div class="stat-label">Paiements Partiels</div>
          <div class="stat-value">{{ formatPrice(stats.totalPartiels) }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-content">
          <div class="stat-label">Ventes Validées</div>
          <div class="stat-value">{{ stats.ventesValidees }}</div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-section">
      <div class="filter-group">
        <input 
          v-model="searchQuery"
          type="text"
          placeholder="🔍 Rechercher par nom de client..."
          class="search-input"
        />
      </div>
      <div class="filter-group">
        <label>Date début:</label>
        <input v-model="dateDebut" type="date" class="date-input" />
      </div>
      <div class="filter-group">
        <label>Date fin:</label>
        <input v-model="dateFin" type="date" class="date-input" />
      </div>
      <div class="filter-group">
        <button @click="applyFilters" class="btn-filter">🔍 Filtrer</button>
        <button @click="resetFilters" class="btn-reset">🔄 Réinitialiser</button>
      </div>
    </div>

    <!-- Tableau des ventes -->
    <div class="ventes-table-container">
      <div v-if="loading" class="loading-state">Chargement...</div>
      <div v-else-if="filteredVentes.length === 0" class="empty-state">
        <p>Aucune vente trouvée</p>
      </div>
      <table v-else class="ventes-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Client</th>
            <th>Produits</th>
            <th>Qté</th>
            <th>Montant</th>
            <th>Mode Paiement</th>
            <th>Reste à payer</th>
            <th>Vendeur</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="v in filteredVentes" :key="v.id_vente + '_' + (v.date_vente || '')">
            <td>{{ formatDate(v.date_vente) }}</td>
            <td>
              <span v-if="v.client_nom">{{ v.client_nom }} {{ v.client_prenom || '' }}</span>
              <span v-else class="text-muted">Client anonyme</span>
            </td>
            <td>
              <div class="produits-list">
                <span v-for="(p, i) in (v.produits || [])" :key="i" class="produit-badge">
                  {{ p.produit_nom || p.nom }} ({{ p.quantite }})
                </span>
              </div>
            </td>
            <td>{{ v.nombre_produits || (v.produits?.length || 0) }}</td>
            <td class="montant-cell">{{ formatPrice(v.montant_total) }}</td>
            <td>
              <span class="payment-mode-badge" :class="getPaymentModeClass(v.notes)">
                {{ getPaymentModeLabel(v.notes) }}
              </span>
            </td>
            <td>
              <span v-if="v.statut !== 'annule'">
                <span v-if="getPaymentModeClass(v.notes) === 'comptant'" class="text-muted">—</span>
                <span v-else-if="getPaymentModeClass(v.notes) === 'partiel' && getResteAPayer(v) > 0" class="reste-a-payer-cell">
                  {{ formatPrice(getResteAPayer(v)) }}
                </span>
                <span v-else-if="getPaymentModeClass(v.notes) === 'credit'" class="reste-a-payer-cell">
                  {{ formatPrice(v.montant_total || 0) }}
                </span>
                <span v-else class="text-muted">—</span>
              </span>
              <span v-else class="text-muted">—</span>
            </td>
            <td>
              <span v-if="v.user_nom">{{ v.user_nom }} {{ v.user_prenom || '' }}</span>
              <span v-else class="text-muted">—</span>
            </td>
            <td>
              <span :class="['status-badge', v.statut === 'annule' ? 'cancelled' : 'active']">
                {{ v.statut === 'annule' ? 'Annulée' : 'Active' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button 
                  v-if="v.statut !== 'annule'" 
                  @click="cancelVente(v)" 
                  class="btn-action btn-cancel"
                  title="Annuler la vente"
                >
                  ❌
                </button>
                <button 
                  v-if="v.statut !== 'annule' && (getPaymentModeClass(v.notes) === 'partiel' || getPaymentModeClass(v.notes) === 'credit') && getResteAPayer(v) > 0"
                  @click="openPaymentModal(v)" 
                  class="btn-action btn-payment"
                  title="Enregistrer un paiement"
                >
                  💰
                </button>
                <button 
                  v-if="v.statut !== 'annule' && (getPaymentModeClass(v.notes) === 'partiel' || getPaymentModeClass(v.notes) === 'credit') && getResteAPayer(v) > 0"
                  @click="openSolderModal(v)" 
                  class="btn-action btn-solder"
                  title="Solder la vente"
                >
                  ✅
                </button>
                <button 
                  @click="reprintReceipt(v)" 
                  class="btn-action btn-print"
                  title="Réimprimer le reçu"
                >
                  🖨️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Reçu (réutilisé depuis Ventes.vue) -->
    <div v-if="showReceiptModal && lastSaleReceipt" class="modal-overlay receipt-overlay" @click.self="closeReceipt">
      <div class="receipt-modal receipt-a4" @click.stop>
        <div class="receipt-header">
          <h2>Reçu de Vente <span v-if="lastSaleReceipt.is_duplicate" class="duplicate-badge">DUPLICATA</span></h2>
          <div class="receipt-actions">
            <button @click="printReceipt" class="btn-print">🖨️ Imprimer</button>
            <button @click="closeReceipt" class="btn-close-receipt">×</button>
          </div>
        </div>
        <div class="receipt-body">
          <div class="receipt-info">
            <div class="receipt-company-info">
              <h3 class="company-name">{{ entrepriseNom }}</h3>
              <p class="company-address" v-if="selectedPointVente?.adresse">{{ selectedPointVente.adresse }}</p>
            </div>
            <div class="receipt-line">
              <span class="label">Date:</span>
              <span class="value">{{ lastSaleReceipt.date }}</span>
            </div>
            <div class="receipt-line">
              <span class="label">Point de vente:</span>
              <span class="value">{{ lastSaleReceipt.point_vente }}</span>
            </div>
            <div class="receipt-line">
              <span class="label">N° Vente:</span>
              <span class="value">#{{ lastSaleReceipt.id_vente }}</span>
            </div>
            <div class="receipt-line" v-if="lastSaleReceipt.client && lastSaleReceipt.client !== 'Client anonyme'">
              <span class="label">Client:</span>
              <span class="value client-name">{{ lastSaleReceipt.client }}</span>
            </div>
          </div>
          
          <div class="receipt-products">
            <div class="receipt-table-header">
              <div class="col-name">Produit</div>
              <div class="col-qty">Qté</div>
              <div class="col-price">Prix</div>
              <div class="col-total">Total</div>
            </div>
            <div 
              v-for="(produit, index) in lastSaleReceipt.produits" 
              :key="index"
              class="receipt-table-row"
            >
              <div class="col-name">
                <div class="product-name">{{ produit.nom }}</div>
                <div class="product-code">{{ produit.code }}</div>
              </div>
              <div class="col-qty">{{ produit.quantite }}</div>
              <div class="col-price">{{ formatPrice(produit.prix_unitaire) }}</div>
              <div class="col-total">{{ formatPrice(produit.sous_total) }}</div>
            </div>
          </div>
          
          <div class="receipt-totals">
            <div class="receipt-total-row">
              <span class="label">Sous-total:</span>
              <span class="value">{{ formatPrice(lastSaleReceipt.sous_total) }}</span>
            </div>
            <div class="receipt-total-row" v-if="lastSaleReceipt.remise > 0">
              <span class="label">Remise:</span>
              <span class="value discount">-{{ formatPrice(lastSaleReceipt.remise) }}</span>
            </div>
            <div class="receipt-total-row final">
              <span class="label">Total:</span>
              <span class="value">{{ formatPrice(lastSaleReceipt.total) }}</span>
            </div>
          </div>
          
          <!-- Informations de paiement -->
          <div class="receipt-payment-info" v-if="lastSaleReceipt.mode_paiement">
            <div class="payment-mode-badge" :class="lastSaleReceipt.mode_paiement">
              <span v-if="lastSaleReceipt.mode_paiement === 'comptant'">💰 Paiement Comptant</span>
              <span v-else-if="lastSaleReceipt.mode_paiement === 'partiel'">📊 Paiement Partiel</span>
              <span v-else-if="lastSaleReceipt.mode_paiement === 'credit'">💳 Paiement à Crédit</span>
            </div>
            
            <div class="payment-details">
              <div class="payment-method" v-if="lastSaleReceipt.moyen_paiement">
                <span class="payment-label">Moyen de paiement:</span>
                <span class="payment-value">
                  <span v-if="lastSaleReceipt.moyen_paiement === 'espece'">💵 Espèce</span>
                  <span v-else-if="lastSaleReceipt.moyen_paiement === 'mobile_money'">
                    📱 Mobile Money
                    <span v-if="lastSaleReceipt.mobile_money_provider" class="provider-name">
                      ({{ getMobileMoneyProviderName(lastSaleReceipt.mobile_money_provider) }})
                    </span>
                  </span>
                </span>
              </div>
              
              <div v-if="lastSaleReceipt.mobile_money_reference" class="payment-reference">
                <span class="payment-label">Référence:</span>
                <span class="payment-value">{{ lastSaleReceipt.mobile_money_reference }}</span>
              </div>
              
              <div v-if="lastSaleReceipt.mode_paiement === 'partiel' || lastSaleReceipt.mode_paiement === 'credit'" class="payment-amounts">
                <div class="payment-amount-row">
                  <span class="payment-label">Montant payé:</span>
                  <span class="payment-value paid">{{ formatPrice(lastSaleReceipt.montant_paye || 0) }}</span>
                </div>
                <div class="payment-amount-row">
                  <span class="payment-label">Reste à payer:</span>
                  <span class="payment-value remaining">{{ formatPrice(lastSaleReceipt.reste_a_payer || 0) }}</span>
                </div>
              </div>
              <div v-else-if="lastSaleReceipt.mode_paiement === 'comptant'" class="payment-amount-row">
                <span class="payment-label">Montant payé:</span>
                <span class="payment-value paid">{{ formatPrice(lastSaleReceipt.total) }}</span>
              </div>
            </div>
          </div>
          
          <div class="receipt-footer">
            <p>Merci de votre achat !</p>
            <p class="receipt-note">Ce reçu a été enregistré dans l'historique des ventes</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modale d'enregistrement de paiement -->
    <div v-if="showPaymentModal" class="modal-overlay" @click.self="closePaymentModal">
      <div class="modal-content payment-modal" @click.stop>
        <div class="modal-header modal-header-with-icon">
          <div class="modal-header-start">
            <span class="modal-header-icon">💰</span>
            <h3>Enregistrer un paiement</h3>
          </div>
          <button @click="closePaymentModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div v-if="venteToPay" class="payment-vente-info">
            <p><strong>Vente #{{ venteToPay.id_vente }}</strong></p>
            <p>Date: {{ formatDate(venteToPay.date_vente) }}</p>
            <p>Total: {{ formatPrice(venteToPay.montant_total) }}</p>
            <p v-if="venteToPay.client_nom">Client: {{ venteToPay.client_nom }} {{ venteToPay.client_prenom || '' }}</p>
            <div class="payment-summary">
              <div class="payment-summary-row">
                <span>Montant déjà payé:</span>
                <span>{{ formatPrice(getMontantPaye(venteToPay)) }}</span>
              </div>
              <div class="payment-summary-row">
                <span>Reste à payer:</span>
                <span class="remaining-amount">{{ formatPrice(getResteAPayer(venteToPay)) }}</span>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label>Montant du paiement *</label>
            <input 
              v-model.number="paymentAmount" 
              type="number" 
              :min="0.01"
              :max="getResteAPayer(venteToPay)"
              step="0.01"
              class="form-input"
              placeholder="0"
            />
            <p class="form-hint">Maximum: {{ formatPrice(getResteAPayer(venteToPay)) }}</p>
          </div>
          
          <div class="form-group">
            <label>Moyen de paiement *</label>
            <select v-model="paymentMethod" class="form-input">
              <option value="espece">💵 Espèce</option>
              <option value="mobile_money">📱 Mobile Money</option>
            </select>
          </div>
          
          <div v-if="paymentMethod === 'mobile_money'" class="form-group">
            <label>Provider</label>
            <select v-model="mobileMoneyProvider" class="form-input">
              <option value="wave">Wave</option>
              <option value="orange_money">Orange Money</option>
              <option value="mtn_money">MTN Money</option>
            </select>
          </div>
          
          <div v-if="paymentMethod === 'mobile_money'" class="form-group">
            <label>Référence (optionnel)</label>
            <input 
              v-model="mobileMoneyReference" 
              type="text" 
              class="form-input"
              placeholder="Référence du paiement"
            />
          </div>
        </div>
        <div class="modal-actions">
          <button @click="closePaymentModal" class="btn-cancel">Annuler</button>
          <button @click="confirmPayment" class="btn-primary" :disabled="!paymentAmount || paymentAmount <= 0 || paymentAmount > getResteAPayer(venteToPay)">
            Enregistrer le paiement
          </button>
        </div>
      </div>
    </div>
    
    <!-- Modale de confirmation d'annulation -->
    <div v-if="showCancelModal" class="modal-overlay" @click.self="closeCancelModal">
      <div class="modal-content confirmation-modal" @click.stop>
        <div class="modal-header modal-header-with-icon">
          <div class="modal-header-start">
            <span class="modal-header-icon">⚠️</span>
            <h3>Confirmer l'annulation</h3>
          </div>
          <button @click="closeCancelModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir annuler cette vente ?</p>
          <div v-if="venteToCancel" class="cancel-vente-details">
            <p><strong>Vente #{{ venteToCancel.id_vente }}</strong></p>
            <p>Date: {{ formatDate(venteToCancel.date_vente) }}</p>
            <p>Montant: {{ formatPrice(venteToCancel.montant_total) }}</p>
            <p v-if="venteToCancel.client_nom">Client: {{ venteToCancel.client_nom }} {{ venteToCancel.client_prenom || '' }}</p>
          </div>
          <p class="warning-text">⚠️ Le stock sera automatiquement remis en place.</p>
        </div>
        <div class="modal-actions">
          <button @click="closeCancelModal" class="btn-cancel">Annuler</button>
          <button @click="confirmCancelVente" class="btn-danger">Confirmer l'annulation</button>
        </div>
      </div>
    </div>
    
    <!-- Notification Toast -->
    <Transition name="toast">
      <div v-if="notification.show" class="toast-notification" :class="notification.type">
        <div class="toast-content">
          <span class="toast-icon">
            <span v-if="notification.type === 'success'">✅</span>
            <span v-else-if="notification.type === 'error'">❌</span>
            <span v-else-if="notification.type === 'warning'">⚠️</span>
            <span v-else>ℹ️</span>
          </span>
          <div class="toast-text">
            <div class="toast-title">{{ notification.title }}</div>
            <div class="toast-message">{{ notification.message }}</div>
          </div>
          <button @click="closeNotification" class="toast-close">×</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiService } from '../composables/Api/apiService.js'
import { useAuthStore } from '../stores/auth.js'
import { useCurrency } from '../composables/useCurrency.js'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { formatPrice } = useCurrency()

const selectedPointVente = ref(null)
const ventes = ref([])
const allVentes = ref([]) // Toutes les ventes chargées
const loading = ref(false)
const searchQuery = ref('')
const dateDebut = ref('')
const dateFin = ref('')
const selectedPeriod = ref('jour') // Par défaut: jour
const showReceiptModal = ref(false)
const lastSaleReceipt = ref(null)
const showCancelModal = ref(false)
const venteToCancel = ref(null)
const showPaymentModal = ref(false)
const venteToPay = ref(null)
const paymentAmount = ref(0)
const paymentMethod = ref('espece')
const mobileMoneyProvider = ref('wave')
const mobileMoneyReference = ref('')
const showSolderModal = ref(false)
const venteToSolder = ref(null)
const solderPaymentMethod = ref('espece')
const solderMobileMoneyProvider = ref('wave')
const solderMobileMoneyReference = ref('')
const notification = ref({
  show: false,
  type: 'success', // success, error, warning, info
  title: '',
  message: ''
})

const entrepriseNom = computed(() => authStore.user?.nom_entreprise || 'Nom de l\'entreprise')

// Calculer les dates selon la période
const getPeriodDates = (period) => {
  const now = new Date()
  let start, end
  
  switch (period) {
    case 'jour':
      start = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59)
      break
    case 'semaine':
      const dayOfWeek = now.getDay()
      const diff = now.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1) // Lundi
      start = new Date(now.getFullYear(), now.getMonth(), diff)
      end = new Date(now.getFullYear(), now.getMonth(), diff + 6, 23, 59, 59)
      break
    case 'mois':
      start = new Date(now.getFullYear(), now.getMonth(), 1)
      end = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59)
      break
    case 'annee':
      start = new Date(now.getFullYear(), 0, 1)
      end = new Date(now.getFullYear(), 11, 31, 23, 59, 59)
      break
    default:
      return null
  }
  
  return { start, end }
}

const periodLabel = computed(() => {
  if (selectedPeriod.value === 'personnalise') {
    if (dateDebut.value && dateFin.value) {
      return `${dateDebut.value} au ${dateFin.value}`
    }
    return 'Période personnalisée'
  }
  
  const dates = getPeriodDates(selectedPeriod.value)
  if (!dates) return ''
  
  const formatDate = (d) => {
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }
  
  switch (selectedPeriod.value) {
    case 'jour':
      return `Aujourd'hui (${formatDate(dates.start)})`
    case 'semaine':
      return `Cette semaine (${formatDate(dates.start)} - ${formatDate(dates.end)})`
    case 'mois':
      return `Ce mois (${dates.start.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })})`
    case 'annee':
      return `Cette année (${dates.start.getFullYear()})`
    default:
      return ''
  }
})

// Filtrer les ventes selon la période
const ventesFilteredByPeriod = computed(() => {
  if (selectedPeriod.value === 'personnalise') {
    return allVentes.value.filter(v => {
      const venteDate = new Date(v.date_vente)
      const start = dateDebut.value ? new Date(dateDebut.value) : null
      const end = dateFin.value ? new Date(dateFin.value + 'T23:59:59') : null
      
      if (start && venteDate < start) return false
      if (end && venteDate > end) return false
      return true
    })
  }
  
  const dates = getPeriodDates(selectedPeriod.value)
  if (!dates) return allVentes.value
  
  return allVentes.value.filter(v => {
    const venteDate = new Date(v.date_vente)
    return venteDate >= dates.start && venteDate <= dates.end
  })
})

const stats = computed(() => {
  const ventesPeriod = ventesFilteredByPeriod.value
  
  const totalVentes = ventesPeriod
    .filter(v => v.statut !== 'annule')
    .reduce((sum, v) => sum + (parseFloat(v.montant_total) || 0), 0)
  
  const nombreVentes = ventesPeriod.filter(v => v.statut !== 'annule').length
  const ventesAnnulees = ventesPeriod.filter(v => v.statut === 'annule').length
  const ventesValidees = nombreVentes
  
  // Extraire les crédits et partiels depuis les notes
  let totalCredits = 0
  let totalPartiels = 0
  
  ventesPeriod.forEach(v => {
    if (v.statut !== 'annule' && v.notes) {
      // Crédits = uniquement les ventes à crédit (pas les restes de paiements partiels)
      if (v.notes.includes('Paiement à crédit') && !v.notes.includes('Paiement partiel')) {
        // Vente entièrement à crédit
        const creditMatch = v.notes.match(/Paiement à crédit:\s*([\d\s,]+)/)
        if (creditMatch) {
          const montantCredit = parseFloat(creditMatch[1].replace(/\s|,/g, '')) || 0
          totalCredits += montantCredit
        } else {
          // Fallback: utiliser le montant total si pas de format détaillé
          totalCredits += parseFloat(v.montant_total) || 0
        }
      }
      
      // Paiements partiels = reste à payer total (pas le montant payé)
      if (v.notes.includes('Paiement partiel')) {
        const resteAPayer = getResteAPayer(v)
        totalPartiels += resteAPayer
      }
    }
  })
  
  return {
    totalVentes,
    nombreVentes,
    ventesAnnulees,
    ventesValidees,
    totalCredits,
    totalPartiels
  }
})

const filteredVentes = computed(() => {
  let filtered = ventesFilteredByPeriod.value
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(v => {
      const clientNom = `${v.client_nom || ''} ${v.client_prenom || ''}`.toLowerCase()
      return clientNom.includes(query)
    })
  }
  
  return filtered.sort((a, b) => new Date(b.date_vente) - new Date(a.date_vente))
})

const loadPointVente = async () => {
  const pvId = route.query.point_vente
  console.log('📍 [HistoriqueVentes] Chargement point de vente, pvId:', pvId)
  
  if (!pvId) {
    // Charger le premier point de vente disponible
    try {
      const response = await apiService.get('/api_point_vente.php?action=all')
      if (response.success && response.data && response.data.length > 0) {
        selectedPointVente.value = response.data[0]
        console.log('✅ [HistoriqueVentes] Point de vente auto-sélectionné:', selectedPointVente.value.nom_point_vente)
      } else {
        console.warn('⚠️ [HistoriqueVentes] Aucun point de vente disponible')
      }
    } catch (error) {
      console.error('❌ [HistoriqueVentes] Erreur chargement points de vente:', error)
    }
    return
  }
  
  try {
    const response = await apiService.get(`/api_point_vente.php?id_point_vente=${pvId}`)
    console.log('📍 [HistoriqueVentes] Réponse API point de vente:', response)
    if (response.success && response.data) {
      selectedPointVente.value = response.data
      console.log('✅ [HistoriqueVentes] Point de vente chargé:', selectedPointVente.value.nom_point_vente)
    } else {
      console.warn('⚠️ [HistoriqueVentes] Point de vente non trouvé pour id:', pvId)
    }
  } catch (error) {
    console.error('❌ [HistoriqueVentes] Erreur chargement point de vente:', error)
  }
}

const loadVentes = async () => {
  if (!selectedPointVente.value) {
    console.warn('⚠️ [HistoriqueVentes] Aucun point de vente sélectionné')
    return
  }
  
  loading.value = true
  try {
    // Charger TOUTES les ventes sans filtre de date
    const url = `/api_vente.php?action=all&id_point_vente=${selectedPointVente.value.id_point_vente}`
    console.log('📊 [HistoriqueVentes] Chargement ventes pour point de vente:', selectedPointVente.value.id_point_vente, 'url:', url)
    
    const response = await apiService.get(url)
    console.log('📊 [HistoriqueVentes] Réponse API complète:', response)
    console.log('📊 [HistoriqueVentes] response.success:', response?.success)
    console.log('📊 [HistoriqueVentes] response.data type:', typeof response?.data)
    console.log('📊 [HistoriqueVentes] response.data isArray:', Array.isArray(response?.data))
    
    // Gérer différents formats de réponse
    let allVentesData = []
    if (response) {
      if (response.success && response.data) {
        allVentesData = Array.isArray(response.data) ? response.data : []
      } else if (Array.isArray(response)) {
        // Si la réponse est directement un tableau
        allVentesData = response
      } else if (response.data && Array.isArray(response.data)) {
        allVentesData = response.data
      }
    }
    
    console.log('📊 [HistoriqueVentes] Nombre de ventes reçues:', allVentesData.length)
    
    const ventesFiltered = allVentesData.filter(v => {
      const type = v.type_vente
      if (type === 'livraison' || type === 'expedition' || type === 'commande' || type === 'retour') return false
      return true
    })
    
    console.log('📊 [HistoriqueVentes] Ventes filtrées (hors livraison/commande):', ventesFiltered.length)
    
    // Grouper les ventes par transaction
    const ventesGrouped = {}
    ventesFiltered.forEach(vente => {
      const dateKey = new Date(vente.date_vente).toISOString().slice(0, 19)
      const key = `${dateKey}_${vente.id_user}_${vente.id_point_vente}_${vente.id_client || 'null'}`
      if (!ventesGrouped[key]) {
        ventesGrouped[key] = {
          id_vente: vente.id_vente,
          date_vente: vente.date_vente,
          id_user: vente.id_user,
          id_point_vente: vente.id_point_vente,
          id_client: vente.id_client,
          client_nom: vente.client_nom,
          client_prenom: vente.client_prenom,
          user_nom: vente.user_nom,
          user_prenom: vente.user_prenom,
          nom_point_vente: vente.nom_point_vente,
          montant_total: parseFloat(vente.montant_total) || 0,
          nombre_produits: 1,
          statut: vente.statut || 'en_cours',
          notes: vente.notes || null,
          produits: [{
            id_produit: vente.id_produit,
            produit_nom: vente.produit_nom,
            code_produit: vente.code_produit,
            quantite: vente.quantite,
            prix_unitaire: vente.prix_unitaire,
            montant_total: vente.montant_total
          }]
        }
      } else {
        ventesGrouped[key].montant_total += parseFloat(vente.montant_total) || 0
        ventesGrouped[key].nombre_produits += 1
        ventesGrouped[key].produits.push({
          id_produit: vente.id_produit,
          produit_nom: vente.produit_nom,
          code_produit: vente.code_produit,
          quantite: vente.quantite,
          prix_unitaire: vente.prix_unitaire,
          montant_total: vente.montant_total
        })
      }
    })
    
    // Stocker toutes les ventes groupées
    allVentes.value = Object.values(ventesGrouped).sort((a, b) => new Date(b.date_vente) - new Date(a.date_vente))
    
    console.log('✅ [HistoriqueVentes] Ventes groupées stockées:', allVentes.value.length)
    if (allVentes.value.length > 0) {
      console.log('📊 [HistoriqueVentes] Exemple de vente:', allVentes.value[0])
    }
    
    // Mettre à jour les ventes affichées selon la période
    updatePeriodDates()
  } catch (error) {
    console.error('❌ [HistoriqueVentes] Erreur chargement ventes:', error)
    allVentes.value = []
  } finally {
    loading.value = false
  }
}

const updatePeriodDates = () => {
  if (selectedPeriod.value === 'personnalise') {
    // Ne rien faire, l'utilisateur choisit les dates
    return
  }
  
  const dates = getPeriodDates(selectedPeriod.value)
  if (dates) {
    dateDebut.value = dates.start.toISOString().split('T')[0]
    dateFin.value = dates.end.toISOString().split('T')[0]
  }
  
  // Mettre à jour les ventes affichées
  if (ventesFilteredByPeriod.value) {
    ventes.value = ventesFilteredByPeriod.value
  }
}

const applyFilters = () => {
  // Les filtres sont appliqués automatiquement via les computed
  // Cette fonction peut être utilisée pour forcer un refresh si nécessaire
  if (selectedPeriod.value === 'personnalise' && ventesFilteredByPeriod.value) {
    ventes.value = ventesFilteredByPeriod.value
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedPeriod.value = 'jour'
  updatePeriodDates()
}

const formatDate = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return date.toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getPaymentModeLabel = (notes) => {
  if (!notes) return 'Comptant'
  if (notes.includes('Paiement à crédit')) return 'Crédit'
  if (notes.includes('Paiement partiel')) return 'Partiel'
  return 'Comptant'
}

const getPaymentModeClass = (notes) => {
  if (!notes) return 'comptant'
  if (notes.includes('Paiement à crédit') && !notes.includes('Paiement partiel')) return 'credit'
  if (notes.includes('Paiement partiel')) return 'partiel'
  return 'comptant'
}

const getMontantPaye = (vente) => {
  if (!vente || !vente.notes) return 0
  const notes = vente.notes
  
  if (notes.includes('Paiement partiel:')) {
    const match = notes.match(/Paiement partiel:\s*([\d\s,]+)\s*\/\s*([\d\s,]+)/)
    if (match) {
      return parseFloat(match[1].replace(/\s|,/g, '')) || 0
    }
  }
  
  // Extraire tous les paiements supplémentaires
  // Format: "Paiement supplémentaire: X F CFA" ou "Paiement supplémentaire: X,XX F CFA" ou "Paiement supplémentaire: X"
  const paiementsMatches = notes.matchAll(/Paiement supplémentaire:\s*([\d\s,]+(?:\.[\d]+)?)(?:\s*F\s*CFA)?/g)
  let totalPaiements = 0
  for (const match of paiementsMatches) {
    const montant = match[1].replace(/\s/g, '').replace(',', '.')
    totalPaiements += parseFloat(montant) || 0
  }
  
  if (notes.includes('Paiement partiel:')) {
    const match = notes.match(/Paiement partiel:\s*([\d\s,]+)\s*\/\s*([\d\s,]+)/)
    if (match) {
      return (parseFloat(match[1].replace(/\s|,/g, '')) || 0) + totalPaiements
    }
  }
  
  if (notes.includes('Paiement à crédit:') && !notes.includes('Paiement partiel')) {
    return totalPaiements
  }
  
  return vente.montant_total || 0
}

const getResteAPayer = (vente) => {
  if (!vente) return 0
  const notes = vente.notes || ''
  
  // Pour les ventes à crédit (sans paiement partiel), le reste à payer est le montant total
  if (notes.includes('Paiement à crédit:') && !notes.includes('Paiement partiel')) {
    const total = vente.montant_total || 0
    const paye = getMontantPaye(vente)
    return Math.max(0, total - paye)
  }
  
  // Pour les paiements partiels, calculer le reste
  if (notes.includes('Paiement partiel:')) {
    const total = vente.montant_total || 0
    const paye = getMontantPaye(vente)
    return Math.max(0, total - paye)
  }
  
  // Pour les paiements comptant, rien à payer
  return 0
}

const openPaymentModal = (vente) => {
  venteToPay.value = vente
  paymentAmount.value = 0
  paymentMethod.value = 'espece'
  mobileMoneyProvider.value = 'wave'
  mobileMoneyReference.value = ''
  showPaymentModal.value = true
}

const closePaymentModal = () => {
  showPaymentModal.value = false
  venteToPay.value = null
  paymentAmount.value = 0
  paymentMethod.value = 'espece'
  mobileMoneyProvider.value = 'wave'
  mobileMoneyReference.value = ''
}

const confirmPayment = async () => {
  if (!venteToPay.value || !paymentAmount.value || paymentAmount.value <= 0) return
  
  const resteAPayer = getResteAPayer(venteToPay.value)
  if (paymentAmount.value > resteAPayer) {
    showNotification('error', 'Erreur', `Le montant ne peut pas dépasser le reste à payer: ${formatPrice(resteAPayer)}`)
    return
  }
  
  try {
    // Construire les nouvelles notes avec le paiement supplémentaire
    const notes = venteToPay.value.notes || ''
    const notesArray = notes.split(' | ').filter(n => n.trim())
    
    // Ajouter le paiement supplémentaire
    // Utiliser le montant brut sans formatage pour le stockage dans les notes
    const montantBrut = paymentAmount.value
    let paymentNote = `Paiement supplémentaire: ${montantBrut.toFixed(2).replace('.', ',')} F CFA`
    if (paymentMethod.value === 'mobile_money') {
      paymentNote += ` (Mobile Money - ${getMobileMoneyProviderName(mobileMoneyProvider.value)})`
      if (mobileMoneyReference.value) {
        paymentNote += ` - Ref: ${mobileMoneyReference.value}`
      }
    } else {
      paymentNote += ` (Espèce)`
    }
    notesArray.push(paymentNote)
    
    // Mettre à jour les notes dans la base de données
    const response = await apiService.post('/api_vente.php?action=update_payment', {
      id_vente: venteToPay.value.id_vente,
      notes: notesArray.join(' | '),
      montant_paye: getMontantPaye(venteToPay.value) + paymentAmount.value
    })
    
    if (response && response.success) {
      await loadVentes()
      closePaymentModal()
      showNotification('success', 'Paiement enregistré', `Le paiement de ${formatPrice(paymentAmount.value)} a été enregistré avec succès.`)
    } else {
      showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement du paiement: ' + (response?.message || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur lors de l\'enregistrement du paiement:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement du paiement: ' + (error.message || 'Erreur inconnue'))
  }
}

const cancelVente = (vente) => {
  venteToCancel.value = vente
  showCancelModal.value = true
}

const closeCancelModal = () => {
  showCancelModal.value = false
  venteToCancel.value = null
}

const confirmCancelVente = async () => {
  if (!venteToCancel.value) return
  
  const vente = venteToCancel.value
  closeCancelModal()
  
  try {
    const response = await apiService.post(`/api_vente.php?action=cancel`, { id_vente: vente.id_vente })
    if (response && response.success) {
      await loadVentes()
      showNotification('success', 'Vente annulée', 'La vente a été annulée avec succès. Le stock a été remis en place.')
    } else {
      showNotification('error', 'Erreur', 'Erreur lors de l\'annulation: ' + (response?.message || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur lors de l\'annulation de la vente:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'annulation de la vente: ' + (error.message || 'Erreur inconnue'))
  }
}

const showNotification = (type, title, message) => {
  notification.value = {
    show: true,
    type,
    title,
    message
  }
  
  // Fermer automatiquement après 5 secondes
  setTimeout(() => {
    closeNotification()
  }, 5000)
}

const closeNotification = () => {
  notification.value.show = false
}

const reprintReceipt = (vente) => {
  // Utiliser les fonctions helper pour calculer correctement le montant payé et le reste à payer
  const montantPaye = getMontantPaye(vente)
  const resteAPayer = getResteAPayer(vente)
  
  // Extraire les informations de paiement depuis les notes
  const notes = vente.notes || ''
  let modePaiement = 'comptant'
  let moyenPaiement = 'espece'
  let mobileMoneyProvider = null
  let mobileMoneyReference = null
  
  // Déterminer le mode de paiement
  if (notes.includes('Paiement partiel:')) {
    modePaiement = 'partiel'
  } else if (notes.includes('Paiement à crédit:') && !notes.includes('Paiement partiel')) {
    modePaiement = 'credit'
  }
  
  // Extraire le moyen de paiement
  if (notes.includes('Mobile Money')) {
    moyenPaiement = 'mobile_money'
    const providerMatch = notes.match(/Mobile Money\s*\(([^)]+)\)/)
    if (providerMatch) {
      mobileMoneyProvider = providerMatch[1].toLowerCase().replace(/\s+/g, '_')
    }
    // Chercher la référence dans les notes (peut être dans n'importe quel paiement)
    const refMatch = notes.match(/Ref:\s*([A-Z0-9]+)/i)
    if (refMatch && refMatch[1] !== 'N/A') {
      mobileMoneyReference = refMatch[1]
    }
  }
  
  lastSaleReceipt.value = {
    id_vente: vente.id_vente,
    date: formatDate(vente.date_vente),
    point_vente: vente.nom_point_vente || selectedPointVente.value?.nom_point_vente || '',
    client: vente.client_nom ? `${vente.client_nom} ${vente.client_prenom || ''}`.trim() : 'Client anonyme',
    produits: vente.produits || [],
    nombre_articles: vente.nombre_produits || 0,
    sous_total: vente.montant_total || 0,
    remise: 0,
    total: vente.montant_total || 0,
    mode_paiement: modePaiement,
    moyen_paiement: moyenPaiement,
    montant_paye: montantPaye,
    reste_a_payer: resteAPayer,
    mobile_money_provider: mobileMoneyProvider,
    mobile_money_reference: mobileMoneyReference,
    is_duplicate: true
  }
  showReceiptModal.value = true
}

const getMobileMoneyProviderName = (provider) => {
  const providers = {
    'wave': 'Wave',
    'orange_money': 'Orange Money',
    'mtn_money': 'MTN Money'
  }
  return providers[provider] || provider
}

const closeReceipt = () => {
  showReceiptModal.value = false
  lastSaleReceipt.value = null
}

const printReceipt = () => {
  const printWindow = window.open('', '_blank')
  if (!printWindow) {
    console.warn('Impossible d\'ouvrir la fenêtre d\'impression')
    return
  }
  
  const receiptElement = document.querySelector('.receipt-modal')
  if (!receiptElement) {
    console.warn('Reçu non trouvé')
    return
  }
  
  const receiptClone = receiptElement.cloneNode(true)
  const actions = receiptClone.querySelector('.receipt-actions')
  if (actions) actions.remove()
  
  const htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Reçu de Vente #${lastSaleReceipt.value.id_vente}</title>
      <style>
        @page { size: A4; margin: 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 0; background: white; width: 210mm; margin: 0 auto; }
        .receipt-modal { background: white; width: 100%; padding: 0; }
        .receipt-header { background: linear-gradient(135deg, #1a5f4a 0%, #145040 100%); color: white; padding: 2rem; text-align: center; }
        .receipt-header h2 { margin: 0; font-size: 2rem; font-weight: 700; }
        .duplicate-badge { display: inline-block; background: #f59e0b; color: white; padding: 0.5rem 1rem; border-radius: 4px; font-size: 1rem; font-weight: 700; margin-left: 1rem; text-transform: uppercase; }
        .receipt-body { padding: 2rem; }
        .receipt-company-info { text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 3px solid #1a5f4a; }
        .company-name { font-size: 2rem; font-weight: 700; color: #1a5f4a; margin: 0 0 0.5rem 0; }
        .company-address { font-size: 1rem; color: #6b7280; margin: 0; }
        .receipt-info { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; }
        .receipt-line { display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 1rem; }
        .receipt-line .label { color: #6b7280; font-weight: 600; }
        .receipt-line .value { color: #111827; font-weight: 600; }
        .receipt-line .value.client-name { color: #1a5f4a; font-weight: 700; font-size: 1.05rem; }
        .receipt-payment-info { background: #f0f9ff; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 2px solid #bae6fd; }
        .payment-mode-badge { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 700; font-size: 0.95rem; margin-bottom: 1rem; }
        .payment-mode-badge.comptant { background: #d1fae5; color: #065f46; border: 2px solid #10b981; }
        .payment-mode-badge.partiel { background: #fef3c7; color: #92400e; border: 2px solid #f59e0b; }
        .payment-mode-badge.credit { background: #fee2e2; color: #991b1b; border: 2px solid #ef4444; }
        .payment-details { display: flex; flex-direction: column; gap: 0.75rem; }
        .payment-method, .payment-reference, .payment-amount-row { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb; }
        .payment-amount-row:last-child { border-bottom: none; }
        .payment-label { color: #6b7280; font-weight: 600; font-size: 0.9rem; }
        .payment-value { color: #111827; font-weight: 700; font-size: 0.95rem; }
        .payment-value.paid { color: #10b981; }
        .payment-value.remaining { color: #ef4444; }
        .provider-name { color: #1a5f4a; font-weight: 600; }
        .receipt-products { margin-bottom: 2rem; }
        .receipt-table-header { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px; font-weight: 700; color: #374151; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .receipt-table-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem; padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        .receipt-table-row:last-child { border-bottom: none; }
        .col-name { display: flex; flex-direction: column; }
        .product-name { font-weight: 600; color: #111827; margin-bottom: 0.25rem; }
        .product-code { font-size: 0.85rem; color: #6b7280; }
        .col-qty, .col-price, .col-total { text-align: right; font-weight: 600; color: #111827; }
        .receipt-totals { background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 2px solid #e5e7eb; }
        .receipt-total-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1rem; }
        .receipt-total-row.final { margin-top: 0.5rem; padding-top: 0.75rem; border-top: 2px solid #e5e7eb; font-size: 1.3rem; }
        .receipt-total-row.final .value { font-weight: 700; color: #1a5f4a; font-size: 1.5rem; }
        .receipt-total-row .value.discount { color: #dc2626; font-size: 1.3rem; font-weight: 700; }
        .receipt-footer { text-align: center; padding-top: 2rem; border-top: 2px solid #e5e7eb; margin-top: 2rem; }
        .receipt-footer p { margin: 0.5rem 0; color: #6b7280; font-size: 1rem; }
        @media print { body { padding: 0; width: 210mm; } .receipt-modal { box-shadow: none; max-width: 100%; width: 100%; } .receipt-header { border-radius: 0; } }
      </style>
    </head>
    <body>
      ${receiptClone.outerHTML}
    </body>
    </html>
  `
  
  printWindow.document.write(htmlContent)
  printWindow.document.close()
  
  printWindow.onload = () => {
    setTimeout(() => {
      printWindow.print()
    }, 250)
  }
}

// Watcher pour mettre à jour automatiquement les ventes affichées quand la période change
watch([ventesFilteredByPeriod, selectedPeriod], () => {
  if (ventesFilteredByPeriod.value && ventesFilteredByPeriod.value.length >= 0) {
    ventes.value = ventesFilteredByPeriod.value
  }
})

onMounted(async () => {
  console.log('🚀 [HistoriqueVentes] Initialisation de la page')
  await loadPointVente()
  if (selectedPointVente.value) {
    await loadVentes()
    // Initialiser avec la période "jour" par défaut
    updatePeriodDates()
  } else {
    console.warn('⚠️ [HistoriqueVentes] Aucun point de vente disponible, impossible de charger les ventes')
  }
})
</script>

<style scoped>
.historique-ventes-page {
  padding: 2rem;
  background: #f5f7fa;
  min-height: calc(100vh - 70px);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  margin: 0;
  font-size: 2rem;
  color: #1a5f4a;
}

.btn-back {
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-back:hover {
  background: #4b5563;
}

.point-vente-header {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.point-vente-header h2 {
  margin: 0;
  color: #1a5f4a;
}

.period-selector-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.period-selector {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.period-selector label {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
}

.period-select {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  color: #111827;
  background: white;
  cursor: pointer;
  min-width: 180px;
}

.period-select:focus {
  outline: none;
  border-color: #1a5f4a;
  box-shadow: 0 0 0 3px rgba(26, 95, 74, 0.1);
}

.custom-dates {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
}

.period-info {
  flex: 1;
  display: flex;
  align-items: center;
}

.period-label {
  font-size: 0.95rem;
  color: #6b7280;
  font-weight: 500;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  border-left: 4px solid #1a5f4a;
}

.stat-card.cancelled {
  border-left-color: #dc2626;
}

.stat-card.credit {
  border-left-color: #f59e0b;
}

.stat-card.partial {
  border-left-color: #2563eb;
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  gap: 1rem;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.search-input,
.date-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  min-width: 200px;
}

.btn-filter,
.btn-reset {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-filter {
  background: #1a5f4a;
  color: white;
}

.btn-filter:hover {
  background: #145040;
}

.btn-reset {
  background: #f3f4f6;
  color: #374151;
}

.btn-reset:hover {
  background: #e5e7eb;
}

.ventes-table-container {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow-x: auto;
}

.ventes-table {
  width: 100%;
  border-collapse: collapse;
}

.ventes-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.ventes-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.ventes-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.ventes-table tbody tr:hover {
  background-color: #f9fafb;
}

.ventes-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #111827;
}

.montant-cell {
  font-weight: 600;
  color: #059669;
}

.payment-mode-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.payment-mode-badge.comptant {
  background: #d1fae5;
  color: #065f46;
}

.payment-mode-badge.credit {
  background: #fef3c7;
  color: #92400e;
}

.payment-mode-badge.partiel {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  background: transparent;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.5rem;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-action.btn-cancel:hover {
  background: #fee2e2;
  border-color: #dc2626;
  color: #dc2626;
}

.btn-action.btn-print:hover {
  background: #dbeafe;
  border-color: #2563eb;
  color: #2563eb;
}

.produits-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.produit-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  background: #eff6ff;
  color: #1e40af;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.text-muted {
  color: #9ca3af;
  font-style: italic;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

/* Styles pour le reçu (réutilisés depuis Ventes.vue) */
.receipt-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}

.receipt-modal {
  background: white;
  border-radius: 16px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.receipt-a4 {
  max-width: 210mm;
  width: 100%;
  padding: 20mm;
  margin: 0 auto;
}

.receipt-header {
  background: linear-gradient(135deg, #1a5f4a 0%, #145040 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 16px 16px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.receipt-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.duplicate-badge {
  display: inline-block;
  background: #f59e0b;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 700;
  margin-left: 1rem;
  text-transform: uppercase;
}

.receipt-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-print {
  background: rgba(255,255,255,0.2);
  color: white;
  border: 1px solid rgba(255,255,255,0.3);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}

.btn-print:hover {
  background: rgba(255,255,255,0.3);
}

.btn-close-receipt {
  background: none;
  border: none;
  color: white;
  font-size: 2rem;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.receipt-body {
  padding: 2rem;
}

.receipt-company-info {
  text-align: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e5e7eb;
}

.company-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
  margin: 0 0 0.5rem 0;
}

.company-address {
  font-size: 0.9rem;
  color: #6b7280;
  margin: 0;
}

.receipt-info {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.receipt-line {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
}

.receipt-line .label {
  color: #6b7280;
  font-weight: 600;
}

.receipt-line .value {
  color: #111827;
  font-weight: 600;
}

.receipt-line .value.client-name {
  color: #1a5f4a;
  font-weight: 700;
  font-size: 1.05rem;
}

/* Informations de paiement */
.receipt-payment-info {
  background: #f0f9ff;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  border: 2px solid #bae6fd;
}

.payment-mode-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 700;
  font-size: 0.95rem;
  margin-bottom: 1rem;
}

.payment-mode-badge.comptant {
  background: #d1fae5;
  color: #065f46;
  border: 2px solid #10b981;
}

.payment-mode-badge.partiel {
  background: #fef3c7;
  color: #92400e;
  border: 2px solid #f59e0b;
}

.payment-mode-badge.credit {
  background: #fee2e2;
  color: #991b1b;
  border: 2px solid #ef4444;
}

.payment-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.payment-method,
.payment-reference,
.payment-amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e5e7eb;
}

.payment-amount-row:last-child {
  border-bottom: none;
}

.payment-label {
  color: #6b7280;
  font-weight: 600;
  font-size: 0.9rem;
}

.payment-value {
  color: #111827;
  font-weight: 700;
  font-size: 0.95rem;
}

.payment-value.paid {
  color: #10b981;
}

.payment-value.remaining {
  color: #ef4444;
}

.provider-name {
  color: #1a5f4a;
  font-weight: 600;
}

/* Modale de confirmation */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.confirmation-modal {
  max-width: 450px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header-with-icon {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.modal-header-start {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.modal-header-icon {
  font-size: 1.5rem;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #111827;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #f3f4f6;
  color: #111827;
}

.modal-body {
  padding: 1.5rem;
}

.modal-body p {
  margin: 0 0 1rem 0;
  color: #374151;
  line-height: 1.6;
}

.cancel-vente-details {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  margin: 1rem 0;
  border-left: 4px solid #1a5f4a;
}

.cancel-vente-details p {
  margin: 0.5rem 0;
  font-size: 0.95rem;
}

.warning-text {
  color: #dc2626;
  font-weight: 600;
  margin-top: 1rem !important;
}

.modal-actions {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.btn-cancel {
  padding: 0.75rem 1.5rem;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  color: #374151;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-danger {
  padding: 0.75rem 1.5rem;
  background: #dc2626;
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover {
  background: #b91c1c;
}

.btn-action.btn-payment {
  background: #10b981;
  color: white;
}

.btn-action.btn-payment:hover {
  background: #059669;
}

.btn-action.btn-solder {
  background: #3b82f6;
  color: white;
}

.btn-action.btn-solder:hover {
  background: #2563eb;
}

.payment-modal {
  max-width: 500px;
}

.payment-vente-info {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  border-left: 4px solid #1a5f4a;
}

.payment-vente-info p {
  margin: 0.5rem 0;
  font-size: 0.95rem;
}

.payment-summary {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.payment-summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.remaining-amount {
  color: #ef4444;
  font-weight: 700;
}

.solder-warning {
  margin-top: 1rem;
  padding: 1rem;
  background: #fef3c7;
  border-left: 4px solid #f59e0b;
  border-radius: 4px;
}

.solder-warning p {
  margin: 0;
  color: #92400e;
  font-size: 0.95rem;
}

.form-hint {
  font-size: 0.85rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: #1a5f4a;
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #145040;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.reste-a-payer-cell {
  color: #ef4444;
  font-weight: 600;
  font-size: 0.95rem;
}

/* Toast Notifications */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 2000;
  min-width: 320px;
  max-width: 500px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  border-left: 4px solid;
  animation: slideInRight 0.3s ease-out;
}

.toast-notification.success {
  border-left-color: #10b981;
}

.toast-notification.error {
  border-left-color: #ef4444;
}

.toast-notification.warning {
  border-left-color: #f59e0b;
}

.toast-notification.info {
  border-left-color: #3b82f6;
}

.toast-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 1.25rem;
}

.toast-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.toast-text {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.toast-message {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.5;
  word-wrap: break-word;
}

.toast-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-top: -0.25rem;
  margin-right: -0.25rem;
}

.toast-close:hover {
  background: #f3f4f6;
  color: #374151;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.receipt-products {
  margin-bottom: 2rem;
}

.receipt-table-header {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  font-weight: 700;
  color: #374151;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.receipt-table-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.receipt-table-row:last-child {
  border-bottom: none;
}

.col-name {
  display: flex;
  flex-direction: column;
}

.product-name {
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.25rem;
}

.product-code {
  font-size: 0.85rem;
  color: #6b7280;
}

.col-qty,
.col-price,
.col-total {
  text-align: right;
  font-weight: 600;
  color: #111827;
}

.receipt-totals {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.receipt-total-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 1rem;
}

.receipt-total-row:last-child {
  margin-bottom: 0;
}

.receipt-total-row.final {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 2px solid #e5e7eb;
  font-size: 1.3rem;
}

.receipt-total-row.final .label {
  font-weight: 700;
  color: #111827;
}

.receipt-total-row.final .value {
  font-weight: 700;
  color: #1a5f4a;
  font-size: 1.5rem;
}

.receipt-total-row .value.discount {
  color: #dc2626;
  font-size: 1.3rem;
  font-weight: 700;
}

.receipt-footer {
  text-align: center;
  padding-top: 1.5rem;
  border-top: 2px solid #e5e7eb;
}

.receipt-footer p {
  margin: 0.5rem 0;
  color: #6b7280;
}

.receipt-note {
  font-size: 0.85rem;
  color: #9ca3af;
  font-style: italic;
}
</style>
