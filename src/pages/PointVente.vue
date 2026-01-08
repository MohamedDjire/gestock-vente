<template>
  <div class="point-vente-page">
    <div class="products-header">
      <h2 class="dashboard-title">Points de Vente</h2>
      <button @click="openCreateModal" class="btn-primary">
        <span>+</span> Nouveau Point de Vente
      </button>
    </div>

    <!-- Contenu masqué jusqu'à ce qu'un point de vente soit ajouté -->
    <div v-if="pointsVente.length > 0">
      <!-- Dashboard des Ventes - Métriques comme dans la capture -->
      <div class="ventes-dashboard">
        <div class="ventes-dashboard-header">
          <h3>Gestion des Ventes - Point de Vente</h3>
        </div>
        <div class="ventes-metrics-grid">
          <div class="vente-metric-card">
            <div class="vente-metric-label">VENTES (AUJOURD'HUI)</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.ventesAujourdhui }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">CA JOURNALIER</div>
            <div class="vente-metric-value">
              <span class="vente-value-text">{{ formatCurrency(statsGlobales.caJournalier) }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">RETOURS</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.retours }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">À LIVRER</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.aLivrer }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">À EXPÉDIER</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.aExpedier }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">COMMANDES</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.commandes }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="stats-row">
        <StatCard 
          title="Total Points de Vente" 
          :value="stats.totalPointsVente.toString()" 
          :variation="null" 
          icon="🏪" />
        <StatCard 
          title="Ventes Journalières" 
          :value="stats.ventesJournalieres.toString()" 
          :variation="null" 
          icon="📈" />
        <StatCard 
          title="Chiffre d'Affaires (Aujourd'hui)" 
          :value="formatCurrency(stats.chiffreAffairesJournalier)" 
          :variation="null" 
          icon="💰" />
        <StatCard 
          title="Commandes en Attente" 
          :value="stats.commandesEnAttente.toString()" 
          :variation="null" 
          icon="📦" />
      </div>

      <!-- Filtres et recherche -->
      <div class="products-filters">
        <div class="search-box">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher un point de vente..."
            class="search-input"
          />
        </div>
        <div class="filter-buttons">
          <button 
            @click="filterActif = null" 
            :class="['filter-btn', { active: filterActif === null }]"
          >
            Tous
          </button>
          <button 
            @click="filterActif = 1" 
            :class="['filter-btn', { active: filterActif === 1 }]"
          >
            Actifs
          </button>
          <button 
            @click="filterActif = 0" 
            :class="['filter-btn', { active: filterActif === 0 }]"
          >
            Inactifs
          </button>
        </div>
      </div>

      <!-- Tableau des points de vente -->
      <div class="products-table-container">
        <table class="products-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Entrepôt</th>
              <th>Ville</th>
              <th>Responsable</th>
              <th>Ventes (Aujourd'hui)</th>
              <th>CA Journalier</th>
              <th>Retours</th>
              <th>À Livrer</th>
              <th>À Expédier</th>
              <th>Commandes</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="12" class="loading-cell">Chargement...</td>
            </tr>
            <tr v-else-if="filteredPointsVente.length === 0">
              <td colspan="12" class="empty-cell">Aucun point de vente trouvé</td>
            </tr>
            <tr v-else v-for="pointVente in filteredPointsVente" :key="pointVente.id_point_vente" @click="goToPointVenteDashboard(pointVente)" style="cursor: pointer;">
              <td>
                <strong>{{ pointVente.nom_point_vente }}</strong>
              </td>
              <td>{{ pointVente.nom_entrepot || 'Magasin' }}</td>
              <td>{{ pointVente.ville || '—' }}</td>
              <td>{{ pointVente.responsable || '—' }}</td>
              <td>
                <span class="stock-badge normal">{{ pointVente.ventes_journalieres || 0 }}</span>
              </td>
              <td class="valeur-stock-cell">
                {{ formatCurrency(pointVente.chiffre_affaires_journalier || 0) }}
              </td>
              <td>
                <span class="stock-badge" :class="pointVente.nombre_retours > 0 ? 'alerte' : 'normal'">
                  {{ pointVente.nombre_retours || 0 }}
                </span>
              </td>
              <td>
                <span class="stock-badge" :class="pointVente.a_livrer > 0 ? 'alerte' : 'normal'">
                  {{ pointVente.a_livrer || 0 }}
                </span>
              </td>
              <td>
                <span class="stock-badge" :class="pointVente.a_expedier > 0 ? 'alerte' : 'normal'">
                  {{ pointVente.a_expedier || 0 }}
                </span>
              </td>
              <td>
                <span class="stock-badge" :class="pointVente.commandes_en_attente > 0 ? 'alerte' : 'normal'">
                  {{ pointVente.commandes_en_attente || 0 }}
                </span>
              </td>
              <td>
                <span :class="['status-badge', pointVente.actif ? 'normal' : 'rupture']">
                  {{ pointVente.actif ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="actions-cell" @click.stop>
                <button @click.stop="viewPointVente(pointVente)" class="btn-view" title="Voir détails">👁️</button>
                <button @click.stop="openRapportModal(pointVente)" class="btn-rapport" title="Rapport">📊</button>
                <button @click.stop="openEditModal(pointVente)" class="btn-edit" title="Modifier">✏️</button>
                <button @click.stop="confirmDelete(pointVente)" class="btn-delete" title="Supprimer">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Création/Modification Point de Vente -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content point-vente-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ isEditMode ? 'Modifier le Point de Vente' : 'Nouveau Point de Vente' }}</h3>
          <button @click="closeModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nom du Point de Vente *</label>
            <input
              v-model="formData.nom_point_vente"
              type="text"
              required
              placeholder="Ex: Boutique Centre-Ville"
            />
          </div>
          <div class="form-group">
            <label>Entrepôt associé</label>
            <select v-model="formData.id_entrepot" class="form-input">
              <option :value="null">Magasin (Par défaut)</option>
              <option v-for="entrepot in entrepots" :key="entrepot.id_entrepot" :value="entrepot.id_entrepot">
                {{ entrepot.nom_entrepot }}
              </option>
            </select>
            <small class="form-hint">Sélectionnez l'entrepôt qui alimente ce point de vente</small>
          </div>
          <div class="form-group">
            <label>Adresse</label>
            <textarea
              v-model="formData.adresse"
              rows="2"
              placeholder="Adresse complète"
            ></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Ville</label>
              <input
                v-model="formData.ville"
                type="text"
                placeholder="Ville"
              />
            </div>
            <div class="form-group">
              <label>Pays</label>
              <input
                v-model="formData.pays"
                type="text"
                placeholder="Pays"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Téléphone</label>
              <input
                v-model="formData.telephone"
                type="text"
                placeholder="+225 XX XX XX XX XX"
              />
            </div>
            <div class="form-group">
              <label>Email</label>
              <input
                v-model="formData.email"
                type="email"
                placeholder="email@exemple.com"
              />
            </div>
          </div>
          <div class="form-group">
            <label>Responsable</label>
            <input
              v-model="formData.responsable"
              type="text"
              placeholder="Nom du responsable"
            />
          </div>
          <div class="form-group">
            <label>Statut</label>
            <select v-model="formData.actif" class="form-input">
              <option :value="1">Actif</option>
              <option :value="0">Inactif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn-secondary">Annuler</button>
          <button @click="savePointVente" class="btn-primary" :disabled="saving">
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Détails Point de Vente -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetailsModal">
      <div class="modal-content details-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ selectedPointVente?.nom_point_vente }}</h3>
          <button @click="closeDetailsModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="details-grid">
            <div class="detail-card">
              <div class="detail-icon">🏭</div>
              <div class="detail-content">
                <div class="detail-label">Entrepôt</div>
                <div class="detail-value">{{ selectedPointVente?.nom_entrepot || 'Magasin' }}</div>
              </div>
            </div>
            <div class="detail-card">
              <div class="detail-icon">📍</div>
              <div class="detail-content">
                <div class="detail-label">Ville</div>
                <div class="detail-value">{{ selectedPointVente?.ville || '—' }}</div>
              </div>
            </div>
            <div class="detail-card">
              <div class="detail-icon">👤</div>
              <div class="detail-content">
                <div class="detail-label">Responsable</div>
                <div class="detail-value">{{ selectedPointVente?.responsable || '—' }}</div>
              </div>
            </div>
            <div class="detail-card">
              <div class="detail-icon">📞</div>
              <div class="detail-content">
                <div class="detail-label">Téléphone</div>
                <div class="detail-value">{{ selectedPointVente?.telephone || '—' }}</div>
              </div>
            </div>
            <div class="detail-card">
              <div class="detail-icon">📧</div>
              <div class="detail-content">
                <div class="detail-label">Email</div>
                <div class="detail-value">{{ selectedPointVente?.email || '—' }}</div>
              </div>
            </div>
            <div class="detail-card" v-if="selectedPointVente?.adresse">
              <div class="detail-icon">🏠</div>
              <div class="detail-content">
                <div class="detail-label">Adresse</div>
                <div class="detail-value">{{ selectedPointVente.adresse }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="goToPointVenteDashboard(selectedPointVente)" class="btn-primary">Accéder au Dashboard</button>
          <button @click="closeDetailsModal" class="btn-secondary">Fermer</button>
        </div>
      </div>
    </div>

    <!-- Modal Rapport Point de Vente -->
    <div v-if="showRapportModal" class="modal-overlay" @click.self="closeRapportModal">
      <div class="modal-content rapport-modal" @click.stop>
        <div class="modal-header">
          <h3>📊 Rapport - {{ rapportPointVente?.nom_point_vente }}</h3>
          <button @click="closeRapportModal" class="modal-close">×</button>
        </div>
        <div class="rapport-content">
          <div v-if="loadingRapport" class="loading-cell">Chargement du rapport...</div>
          <div v-else>
            <!-- Statistiques du rapport -->
            <div class="rapport-stats">
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">📈</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Ventes Journalières</div>
                  <div class="rapport-stat-value">{{ rapportData.ventes_journalieres?.length || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">↩️</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Retours</div>
                  <div class="rapport-stat-value">{{ rapportData.retours || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">🚚</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">À Livrer</div>
                  <div class="rapport-stat-value">{{ rapportData.a_livrer || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">📦</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">À Expédier</div>
                  <div class="rapport-stat-value">{{ rapportData.a_expedier || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">🛒</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Commandes</div>
                  <div class="rapport-stat-value">{{ rapportData.commandes_en_attente || 0 }}</div>
                </div>
              </div>
            </div>

            <!-- Ventes journalières (7 derniers jours) -->
            <div class="rapport-section" v-if="rapportData.ventes_journalieres && rapportData.ventes_journalieres.length > 0">
              <h4 class="rapport-section-title">📈 Ventes Journalières (7 derniers jours)</h4>
              <div class="rapport-ventes-journalieres">
                <div 
                  v-for="vente in rapportData.ventes_journalieres" 
                  :key="vente.date"
                  class="rapport-vente-item"
                >
                  <div class="vente-date">{{ formatDate(vente.date) }}</div>
                  <div class="vente-info">
                    <span>{{ vente.nombre_ventes }} ventes</span>
                    <strong>{{ formatCurrency(vente.chiffre_affaires) }}</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeRapportModal" class="btn-secondary">Fermer</button>
        </div>
      </div>
    </div>

    <!-- Notifications -->
    <div v-if="notification.show" class="notification-overlay" @click="closeNotification">
      <div :class="['notification-modal', notification.type]">
        <div class="notification-title">{{ notification.title }}</div>
        <div class="notification-message">{{ notification.message }}</div>
        <button @click="closeNotification" class="notification-close">OK</button>
      </div>
    </div>

    <!-- Confirmations -->
    <div v-if="confirmation.show" class="confirmation-overlay" @click.self="closeConfirmation">
      <div class="confirmation-modal">
        <div class="confirmation-title">{{ confirmation.title }}</div>
        <div class="confirmation-message">{{ confirmation.message }}</div>
        <div class="confirmation-actions">
          <button @click="closeConfirmation" class="btn-secondary">Annuler</button>
          <button @click="confirmAction" class="btn-primary">Confirmer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import StatCard from '../components/StatCard.vue'
import { apiService } from '../composables/Api/apiService.js'
import { useCurrency } from '../composables/useCurrency.js'
import { logJournal } from '../composables/useJournal'

const router = useRouter()

const { formatPrice: formatCurrency } = useCurrency()

const pointsVente = ref([])
const entrepots = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filterActif = ref(null)
const showModal = ref(false)
const isEditMode = ref(false)
const saving = ref(false)
const showDetailsModal = ref(false)
const selectedPointVente = ref(null)
const showRapportModal = ref(false)
const rapportPointVente = ref(null)
const rapportData = ref({
  ventes_journalieres: [],
  retours: 0,
  a_livrer: 0,
  a_expedier: 0,
  commandes_en_attente: 0
})
const loadingRapport = ref(false)

const formData = ref({
  nom_point_vente: '',
  id_entrepot: null,
  adresse: '',
  ville: '',
  pays: '',
  telephone: '',
  email: '',
  responsable: '',
  actif: 1
})

const notification = ref({ show: false, type: 'success', title: '', message: '' })
const confirmation = ref({ show: false, title: '', message: '', action: null })

const statsGlobales = ref({
  ventesAujourdhui: 0,
  caJournalier: 0,
  retours: 0,
  aLivrer: 0,
  aExpedier: 0,
  commandes: 0
})

const stats = computed(() => {
  const totalPointsVente = pointsVente.value.length
  const ventesJournalieres = pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.ventes_journalieres) || 0), 0)
  const chiffreAffairesJournalier = pointsVente.value.reduce((sum, pv) => sum + (parseFloat(pv.chiffre_affaires_journalier) || 0), 0)
  const commandesEnAttente = pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.commandes_en_attente) || 0), 0)
  
  // Mettre à jour les statistiques globales pour le dashboard
  statsGlobales.value = {
    ventesAujourdhui: ventesJournalieres,
    caJournalier: chiffreAffairesJournalier,
    retours: pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.nombre_retours) || 0), 0),
    aLivrer: pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.a_livrer) || 0), 0),
    aExpedier: pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.a_expedier) || 0), 0),
    commandes: commandesEnAttente
  }
  
  return {
    totalPointsVente,
    ventesJournalieres,
    chiffreAffairesJournalier,
    commandesEnAttente
  }
})

const filteredPointsVente = computed(() => {
  let filtered = pointsVente.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(pv => 
      pv.nom_point_vente?.toLowerCase().includes(query) ||
      pv.ville?.toLowerCase().includes(query) ||
      pv.responsable?.toLowerCase().includes(query) ||
      pv.nom_entrepot?.toLowerCase().includes(query)
    )
  }

  if (filterActif.value !== null) {
    filtered = filtered.filter(pv => pv.actif == filterActif.value)
  }

  return filtered
})

// formatCurrency est maintenant fourni par useCurrency() via formatPrice
// Les valeurs sont supposées être en XOF (F CFA) par défaut dans la base de données

const formatDate = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric'
  })
}

const loadEntrepots = async () => {
  try {
    const response = await apiService.get('/api_entrepot.php?action=all')
    if (response && response.success) {
      entrepots.value = response.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des entrepôts:', error)
  }
}

const loadPointsVente = async () => {
  loading.value = true
  try {
    const response = await apiService.get('/api_point_vente.php?action=all')
    if (response && response.success) {
      pointsVente.value = response.data || []
    } else {
      console.error('Erreur API:', response)
      showNotification('error', 'Erreur', response?.message || 'Erreur lors du chargement des points de vente')
    }
  } catch (error) {
    console.error('Erreur lors du chargement des points de vente:', error)
    showNotification('error', 'Erreur', error?.message || 'Erreur lors du chargement des points de vente')
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  isEditMode.value = false
  formData.value = {
    nom_point_vente: '',
    id_entrepot: null,
    adresse: '',
    ville: '',
    pays: '',
    telephone: '',
    email: '',
    responsable: '',
    actif: 1
  }
  showModal.value = true
}

const openEditModal = (pointVente) => {
  isEditMode.value = true
  formData.value = {
    id_point_vente: pointVente.id_point_vente,
    nom_point_vente: pointVente.nom_point_vente,
    id_entrepot: pointVente.id_entrepot || null,
    adresse: pointVente.adresse || '',
    ville: pointVente.ville || '',
    pays: pointVente.pays || '',
    telephone: pointVente.telephone || '',
    email: pointVente.email || '',
    responsable: pointVente.responsable || '',
    actif: pointVente.actif ? 1 : 0
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formData.value = {
    nom_point_vente: '',
    id_entrepot: null,
    adresse: '',
    ville: '',
    pays: '',
    telephone: '',
    email: '',
    responsable: '',
    actif: 1
  }
}

const savePointVente = async () => {
  if (!formData.value.nom_point_vente?.trim()) {
    showNotification('error', 'Erreur de validation', 'Le nom du point de vente est requis')
    return
  }

  saving.value = true
  try {
    let response
    if (isEditMode.value) {
      response = await apiService.put('/api_point_vente.php', formData.value)
      logJournal({ user: getJournalUser(), action: 'Modifier Point de Vente', details: `Point de vente ${formData.value.nom_point_vente} modifié` })
    } else {
      response = await apiService.post('/api_point_vente.php', formData.value)
      logJournal({ user: getJournalUser(), action: 'Ajouter Point de Vente', details: `Point de vente ${formData.value.nom_point_vente} ajouté` })
    }

    if (response.success) {
      showNotification('success', 'Succès', isEditMode.value ? 'Point de vente mis à jour' : 'Point de vente créé')
      closeModal()
      await loadPointsVente()
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de l\'enregistrement')
    }
  } catch (error) {
    showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

const viewPointVente = (pointVente) => {
  selectedPointVente.value = pointVente
  showDetailsModal.value = true
}

const goToPointVenteDashboard = (pointVente) => {
  // Naviguer vers le dashboard avec l'ID du point de vente
  router.push(`/dashboard?point_vente=${pointVente.id_point_vente}`)
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedPointVente.value = null
}

const openRapportModal = async (pointVente) => {
  rapportPointVente.value = pointVente
  showRapportModal.value = true
  loadingRapport.value = true
  
  try {
    const response = await apiService.get(`/api_point_vente.php?action=stats&id_point_vente=${pointVente.id_point_vente}`)
    if (response.success) {
      rapportData.value = response.data || {
        ventes_journalieres: [],
        retours: 0,
        a_livrer: 0,
        a_expedier: 0,
        commandes_en_attente: 0
      }
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors du chargement du rapport')
    }
  } catch (error) {
    console.error('Erreur lors du chargement du rapport:', error)
    showNotification('error', 'Erreur', 'Erreur lors du chargement du rapport')
  } finally {
    loadingRapport.value = false
  }
}

const closeRapportModal = () => {
  showRapportModal.value = false
  rapportPointVente.value = null
  rapportData.value = {
    ventes_journalieres: [],
    retours: 0,
    a_livrer: 0,
    a_expedier: 0,
    commandes_en_attente: 0
  }
}

const confirmDelete = (pointVente) => {
  confirmation.value = {
    show: true,
    title: 'Supprimer le point de vente',
    message: `Êtes-vous sûr de vouloir supprimer le point de vente "${pointVente.nom_point_vente}" ?`,
    action: async () => {
      try {
        const response = await apiService.delete(`/api_point_vente.php?id_point_vente=${pointVente.id_point_vente}`)
        logJournal({ user: getJournalUser(), action: 'Supprimer Point de Vente', details: `Point de vente ${pointVente.nom_point_vente} supprimé` })
        if (response.success) {
          showNotification('success', 'Succès', 'Point de vente supprimé')
          await loadPointsVente()
        } else {
          showNotification('error', 'Erreur', response.message || 'Erreur lors de la suppression')
        }
      } catch (error) {
        showNotification('error', 'Erreur', 'Erreur lors de la suppression')
      }
    }
  }
}

const showNotification = (type, title, message) => {
  notification.value = { show: true, type, title, message }
}

const closeNotification = () => {
  notification.value = { show: false, type: 'success', title: '', message: '' }
}

const closeConfirmation = () => {
  confirmation.value = { show: false, title: '', message: '', action: null }
}

const confirmAction = () => {
  if (confirmation.value.action) {
    confirmation.value.action()
  }
  closeConfirmation()
}

function getJournalUser() {
  const userStr = localStorage.getItem('prostock_user');
  if (userStr) {
    try {
      const user = JSON.parse(userStr);
      return user.nom || user.email || 'inconnu';
    } catch {
      return 'inconnu';
    }
  }
  return 'inconnu';
}

onMounted(() => {
  loadEntrepots()
  loadPointsVente()
})
</script>

<style scoped>
/* Styles similaires à Entrepot.vue */
.point-vente-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  width: 100%;
}

.products-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.dashboard-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
  margin: 0;
  letter-spacing: 0.01em;
}

.btn-primary {
  background: #1a5f4a;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #134e3a;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.stats-row {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.products-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.5rem;
  align-items: center;
}

.search-box {
  flex: 0 0 50%;
  max-width: 480px;
}

.search-input {
  width: 100%;
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
}

.filter-buttons {
  display: flex;
  gap: 0.5rem;
}

.filter-btn {
  padding: 0.5rem 1rem;
  border: 1.5px solid #10b981;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-btn.active {
  background: #1a5f4a;
  color: white;
  border-color: #1a5f4a;
}

.products-table-container {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-top: 0;
  width: 100%;
}

.products-table {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
  display: table;
  table-layout: auto;
}

.products-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.products-table th {
  padding: 0.875rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.products-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #1f2937;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
  line-height: 1.5;
}

.products-table tbody tr {
  transition: background 0.2s;
}

.products-table tbody tr:hover {
  background: #f9fafb;
}

.loading-cell, .empty-cell {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.stock-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.875rem;
}

.stock-badge.normal {
  background: #d1fae5;
  color: #065f46;
}

.stock-badge.alerte {
  background: #fef3c7;
  color: #92400e;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-badge.normal {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.rupture {
  background: #fee2e2;
  color: #991b1b;
}

.valeur-stock-cell {
  font-weight: 600;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.actions-cell button {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: all 0.2s;
}

.btn-view {
  background: #dbeafe;
  color: #1e40af;
}

.btn-rapport {
  background: #e0e7ff;
  color: #3730a3;
}

.btn-edit {
  background: #fef3c7;
  color: #92400e;
}

.btn-delete {
  background: #fee2e2;
  color: #991b1b;
}

.actions-cell button:hover {
  transform: scale(1.1);
}

/* Modals */
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
  border-radius: 16px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.details-modal, .rapport-modal {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.modal-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
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
  color: #1f2937;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #059669;
}

.form-hint {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  transition: all 0.2s;
}

.detail-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #1a5f4a;
}

.detail-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.detail-content {
  flex: 1;
}

.detail-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.detail-value {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
}

/* Styles pour le rapport */
.rapport-content {
  padding: 1.5rem;
}

.rapport-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.rapport-stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.rapport-stat-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.rapport-stat-info {
  flex: 1;
}

.rapport-stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.rapport-stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
}

.rapport-section {
  margin-top: 2rem;
}

.rapport-section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a5f4a;
  margin-bottom: 1rem;
}

.rapport-ventes-journalieres {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-height: 300px;
  overflow-y: auto;
}

.rapport-vente-item {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 10px;
  border-left: 4px solid #10b981;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.vente-date {
  font-weight: 600;
  color: #1f2937;
}

.vente-info {
  display: flex;
  gap: 1rem;
  align-items: center;
  font-size: 0.875rem;
  color: #6b7280;
}

.vente-info strong {
  color: #1a5f4a;
  font-size: 1rem;
}

/* Notifications */
.notification-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.notification-modal {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  min-width: 320px;
  max-width: 90vw;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.notification-modal.success {
  border-top: 4px solid #10b981;
}

.notification-modal.error {
  border-top: 4px solid #ef4444;
}

.notification-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1f2937;
}

.notification-message {
  text-align: center;
  color: #6b7280;
}

.notification-close {
  background: #1a5f4a;
  color: white;
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

/* Confirmations */
.confirmation-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.confirmation-modal {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  min-width: 400px;
  max-width: 90vw;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
}

.confirmation-title {
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #1a5f4a;
}

.confirmation-message {
  margin-bottom: 1.5rem;
  color: #6b7280;
}

.confirmation-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

/* Dashboard des Ventes */
.ventes-dashboard {
  background: #ffffff;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.ventes-dashboard-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.ventes-dashboard-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a5f4a;
}

.ventes-metrics-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 1rem;
}

.vente-metric-card {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.vente-metric-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-align: center;
  line-height: 1.3;
}

.vente-metric-value {
  text-align: center;
  display: flex;
  justify-content: center;
  align-items: center;
}

.vente-value-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: #d1fae5;
  color: #065f46;
  border-radius: 20px;
  font-size: 1.25rem;
  font-weight: 700;
  min-width: 60px;
}

.vente-value-text {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
}

@media (max-width: 1200px) {
  .ventes-metrics-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .ventes-metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .ventes-metrics-grid {
    grid-template-columns: 1fr;
  }
}
</style>







