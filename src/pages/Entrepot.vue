<template>
  <div class="entrepot-page">
          <div class="products-header">
            <h2 class="dashboard-title">Entrepôts</h2>
            <button @click="openCreateModal" class="btn-primary">
              <span>+</span> Nouvel Entrepôt
            </button>
          </div>

          <!-- Contenu masqué jusqu'à ce qu'un entrepôt soit ajouté -->
          <div v-if="entrepots.length > 0">
            <!-- Statistiques -->
            <div class="stats-row">
              <StatCard 
                title="Total Entrepôts" 
                :value="stats.totalEntrepots.toString()" 
                :variation="null" 
                icon="🏭" />
              <StatCard 
                title="Valeur Stock Total (Achat)" 
                :value="formatCurrency(stats.valeurStockAchat)" 
                :variation="null" 
                icon="💶" />
              <StatCard 
                title="Valeur Stock Total (Vente)" 
                :value="formatCurrency(stats.valeurStockVente)" 
                :variation="null" 
                icon="💵" />
              <StatCard 
                title="Stock Total (Unités)" 
                :value="stats.stockTotal.toString()" 
                :variation="null" 
                icon="📊" />
            </div>

            <!-- Filtres et recherche -->
            <div class="products-filters">
              <div class="search-box">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Rechercher un entrepôt..."
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

            <!-- Tableau des entrepôts -->
            <div class="products-table-container">
            <table class="products-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Ville</th>
                  <th>Responsable</th>
                  <th>Produits</th>
                  <th>Stock Total</th>
                  <th>Valeur Stock (Achat)</th>
                  <th>Valeur Stock (Vente)</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="9" class="loading-cell">Chargement...</td>
                </tr>
                <tr v-else-if="filteredEntrepots.length === 0">
                  <td colspan="9" class="empty-cell">Aucun entrepôt trouvé</td>
                </tr>
                <tr v-else v-for="entrepot in filteredEntrepots" :key="entrepot.id_entrepot">
                  <td>
                    <strong>{{ entrepot.nom_entrepot }}</strong>
                  </td>
                  <td>{{ entrepot.ville || '—' }}</td>
                  <td>{{ entrepot.responsable || '—' }}</td>
                  <td>
                    <span class="stock-badge normal">{{ entrepot.nombre_produits || 0 }}</span>
                  </td>
                  <td>
                    <span class="stock-badge normal">{{ entrepot.stock_total || 0 }}</span>
                  </td>
                  <td class="valeur-stock-cell">
                    {{ formatCurrency(entrepot.valeur_stock_achat || 0) }}
                  </td>
                  <td class="valeur-stock-cell">
                    {{ formatCurrency(entrepot.valeur_stock_vente || 0) }}
                  </td>
                  <td>
                    <span :class="['status-badge', entrepot.actif ? 'normal' : 'rupture']">
                      {{ entrepot.actif ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="actions-cell">
                    <button @click="viewEntrepot(entrepot)" class="btn-view" title="Voir détails">👁️</button>
                    <button @click="openEditModal(entrepot)" class="btn-edit" title="Modifier">✏️</button>
                    <button @click="confirmDelete(entrepot)" class="btn-delete" title="Supprimer">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          </div>

    <!-- Modal Création/Modification Entrepôt -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content entrepot-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ isEditMode ? 'Modifier l\'Entrepôt' : 'Nouvel Entrepôt' }}</h3>
          <button @click="closeModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nom de l'Entrepôt *</label>
            <input
              v-model="formData.nom_entrepot"
              type="text"
              required
              placeholder="Ex: Magasin Principal"
            />
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
                placeholder="Téléphone"
              />
            </div>
            <div class="form-group">
              <label>Email</label>
              <input
                v-model="formData.email"
                type="email"
                placeholder="Email"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Responsable</label>
              <input
                v-model="formData.responsable"
                type="text"
                placeholder="Nom du responsable"
              />
            </div>
            <div class="form-group">
              <label>Capacité Maximale</label>
              <input
                v-model.number="formData.capacite_max"
                type="number"
                min="0"
                placeholder="Capacité en unités"
              />
            </div>
          </div>
          <div class="form-group">
            <label>
              <input
                v-model="formData.actif"
                type="checkbox"
              />
              Entrepôt actif
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn-secondary">Annuler</button>
          <button @click="saveEntrepot" class="btn-primary" :disabled="saving">
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Détails Entrepôt avec Produits -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetailsModal">
      <div class="modal-content details-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ selectedEntrepot?.nom_entrepot }}</h3>
          <button @click="closeDetailsModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div v-if="loadingProduits" class="loading-cell">Chargement des produits...</div>
          <div v-else-if="produitsEntrepot.length === 0" class="empty-cell">
            Aucun produit dans cet entrepôt
          </div>
          <table v-else class="products-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Nom du Produit</th>
                <th>Prix Achat</th>
                <th>Prix Vente</th>
                <th>Stock</th>
                <th>Valeur Stock (Achat)</th>
                <th>Valeur Stock (Vente)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="produit in produitsEntrepot" :key="produit.id_produit">
                <td><strong>{{ produit.code_produit }}</strong></td>
                <td>{{ produit.nom }}</td>
                <td>{{ formatCurrency(produit.prix_achat) }}</td>
                <td>{{ formatCurrency(produit.prix_vente) }}</td>
                <td>
                  <span class="stock-badge normal">{{ produit.quantite_stock }}</span>
                </td>
                <td>{{ formatCurrency(produit.valeur_stock_achat || 0) }}</td>
                <td>{{ formatCurrency(produit.valeur_stock_vente || 0) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button @click="closeDetailsModal" class="btn-primary">Fermer</button>
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
import Sidebar from '../components/Sidebar.vue'
import Topbar from '../components/Topbar.vue'
import StatCard from '../components/StatCard.vue'
import { apiService } from '../composables/Api/apiService.js'

const router = useRouter()
const selectedCurrency = inject('selectedCurrency', ref('F CFA'))

const entrepots = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filterActif = ref(null)
const showModal = ref(false)
const isEditMode = ref(false)
const saving = ref(false)
const showDetailsModal = ref(false)
const selectedEntrepot = ref(null)
const produitsEntrepot = ref([])
const loadingProduits = ref(false)

const formData = ref({
  nom_entrepot: '',
  adresse: '',
  ville: '',
  pays: '',
  telephone: '',
  email: '',
  responsable: '',
  capacite_max: null,
  actif: 1
})

const notification = ref({ show: false, type: 'success', title: '', message: '' })
const confirmation = ref({ show: false, title: '', message: '', action: null })

const stats = computed(() => {
  const totalEntrepots = entrepots.value.length
  const valeurStockAchat = entrepots.value.reduce((sum, e) => sum + (parseFloat(e.valeur_stock_achat) || 0), 0)
  const valeurStockVente = entrepots.value.reduce((sum, e) => sum + (parseFloat(e.valeur_stock_vente) || 0), 0)
  const stockTotal = entrepots.value.reduce((sum, e) => sum + (parseInt(e.stock_total) || 0), 0)
  
  return {
    totalEntrepots,
    valeurStockAchat,
    valeurStockVente,
    stockTotal
  }
})

const filteredEntrepots = computed(() => {
  let filtered = entrepots.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(e => 
      e.nom_entrepot?.toLowerCase().includes(query) ||
      e.ville?.toLowerCase().includes(query) ||
      e.responsable?.toLowerCase().includes(query)
    )
  }

  if (filterActif.value !== null) {
    filtered = filtered.filter(e => e.actif == filterActif.value)
  }

  return filtered
})

const formatCurrency = (amount) => {
  const num = parseFloat(amount) || 0
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: selectedCurrency.value === 'F CFA' ? 'XOF' : selectedCurrency.value,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(num).replace('XOF', 'F CFA')
}

const loadEntrepots = async () => {
  loading.value = true
  try {
    const response = await apiService.get('/api_entrepot.php?action=all')
    if (response && response.success) {
      entrepots.value = response.data || []
    } else {
      console.error('Erreur API:', response)
      showNotification('error', 'Erreur', response?.message || 'Erreur lors du chargement des entrepôts')
    }
  } catch (error) {
    console.error('Erreur lors du chargement des entrepôts:', error)
    showNotification('error', 'Erreur', error?.message || 'Erreur lors du chargement des entrepôts')
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  isEditMode.value = false
  formData.value = {
    nom_entrepot: '',
    adresse: '',
    ville: '',
    pays: '',
    telephone: '',
    email: '',
    responsable: '',
    capacite_max: null,
    actif: 1
  }
  showModal.value = true
}

const openEditModal = (entrepot) => {
  isEditMode.value = true
  formData.value = {
    id_entrepot: entrepot.id_entrepot,
    nom_entrepot: entrepot.nom_entrepot,
    adresse: entrepot.adresse || '',
    ville: entrepot.ville || '',
    pays: entrepot.pays || '',
    telephone: entrepot.telephone || '',
    email: entrepot.email || '',
    responsable: entrepot.responsable || '',
    capacite_max: entrepot.capacite_max || null,
    actif: entrepot.actif ? 1 : 0
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formData.value = {
    nom_entrepot: '',
    adresse: '',
    ville: '',
    pays: '',
    telephone: '',
    email: '',
    responsable: '',
    capacite_max: null,
    actif: 1
  }
}

const saveEntrepot = async () => {
  if (!formData.value.nom_entrepot?.trim()) {
    showNotification('error', 'Erreur de validation', 'Le nom de l\'entrepôt est requis')
    return
  }

  saving.value = true
  try {
    let response
    if (isEditMode.value) {
      response = await apiService.put('/api_entrepot.php', formData.value)
    } else {
      response = await apiService.post('/api_entrepot.php', formData.value)
    }

    if (response.success) {
      showNotification('success', 'Succès', isEditMode.value ? 'Entrepôt mis à jour' : 'Entrepôt créé')
      closeModal()
      await loadEntrepots()
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de l\'enregistrement')
    }
  } catch (error) {
    showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

const viewEntrepot = async (entrepot) => {
  selectedEntrepot.value = entrepot
  loadingProduits.value = true
  showDetailsModal.value = true
  
  try {
    const response = await apiService.get(`/api_entrepot.php?action=produits&id_entrepot=${entrepot.id_entrepot}`)
    if (response.success) {
      produitsEntrepot.value = response.data || []
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors du chargement des produits')
    }
  } catch (error) {
    showNotification('error', 'Erreur', 'Erreur lors du chargement des produits')
  } finally {
    loadingProduits.value = false
  }
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedEntrepot.value = null
  produitsEntrepot.value = []
}

const confirmDelete = (entrepot) => {
  confirmation.value = {
    show: true,
    title: 'Supprimer l\'entrepôt',
    message: `Êtes-vous sûr de vouloir supprimer l'entrepôt "${entrepot.nom_entrepot}" ?`,
    action: async () => {
      try {
        const response = await apiService.delete(`/api_entrepot.php?id_entrepot=${entrepot.id_entrepot}`)
        if (response.success) {
          showNotification('success', 'Succès', 'Entrepôt supprimé')
          await loadEntrepots()
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

onMounted(() => {
  loadEntrepots()
})
</script>

<style scoped>
/* Styles similaires à Products.vue */
.dashboard-layout {
  display: flex;
  width: 100vw;
  background: #f6f7fa;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100vw - 280px);
  display: flex;
  flex-direction: column;
}

.dashboard-wrapper {
  background: #fff;
  border-radius: 0 32px 32px 0;
  box-shadow: 0 8px 32px 0 rgba(26, 95, 74, 0.10);
  width: 100%;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.2s;
}

.dashboard-content {
  padding: 1rem 2.5rem 2.5rem 2.5rem;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  box-sizing: border-box;
  background: #f6f7fa;
  position: relative;
}

.dashboard-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
  margin-bottom: 1rem;
  letter-spacing: 0.01em;
}

.products-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
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
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: flex-start;
  margin-bottom: 1rem;
}

.stats-row :deep(.stat-card) {
  flex: 1 1 180px;
  min-width: 180px;
  padding: 0.9rem 1rem 0.7rem 1rem;
}

.products-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.5rem;
  align-items: center;
  justify-content: flex-start;
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

.details-modal {
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
  color: #1a5f4a;
}

.modal-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #6b7280;
  line-height: 1;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1.5px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #1a5f4a;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
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
  border-radius: 12px;
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.notification-modal.success {
  border-left: 4px solid #10b981;
}

.notification-modal.error {
  border-left: 4px solid #ef4444;
}

.notification-title {
  font-weight: 700;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.notification-message {
  margin-bottom: 1rem;
  color: #6b7280;
}

.notification-close {
  background: #1a5f4a;
  color: white;
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: 6px;
  cursor: pointer;
  width: 100%;
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
  border-radius: 12px;
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.confirmation-title {
  font-weight: 700;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
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
</style>

