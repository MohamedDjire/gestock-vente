<template>
  <div class="settings-page">
    <div class="settings-header">
      <h1>Paramètres & Administration</h1>
    </div>

    <div class="settings-tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.id"
        :class="['tab-button', { active: activeTab === tab.id }]"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Onglet Forfaits -->
    <div v-if="activeTab === 'forfaits'" class="settings-content">
      <div class="section-header">
        <h2>Gestion des Forfaits</h2>
        <button @click="openForfaitModal" class="btn-primary">Nouveau Forfait</button>
      </div>

      <!-- Statut du forfait actuel -->
      <div class="current-forfait-card">
        <h3>Forfait Actuel</h3>
        <div v-if="currentForfait" class="forfait-info">
          <div class="info-row">
            <span class="label">Nom:</span>
            <span class="value">{{ currentForfait.nom }}</span>
          </div>
          <div class="info-row">
            <span class="label">Prix:</span>
            <span class="value">{{ formatPrice(currentForfait.prix) }}</span>
          </div>
          <div class="info-row">
            <span class="label">Durée:</span>
            <span class="value">{{ currentForfait.duree_jours }} jours</span>
          </div>
          <div class="info-row">
            <span class="label">Date de début:</span>
            <span class="value">{{ formatDate(currentForfait.date_debut) }}</span>
          </div>
          <div class="info-row">
            <span class="label">Date de fin:</span>
            <span class="value" :class="getForfaitStatusClass(currentForfait.date_fin)">
              {{ formatDate(currentForfait.date_fin) }}
            </span>
          </div>
          <div class="info-row">
            <span class="label">Statut:</span>
            <span class="value" :class="currentForfait.statut === 'actif' ? 'status-active' : 'status-expired'">
              {{ currentForfait.statut === 'actif' ? 'Actif' : 'Expiré' }}
            </span>
          </div>
        </div>
        <div v-else class="no-forfait">
          <p>Aucun forfait actif</p>
        </div>
      </div>

      <!-- Liste des forfaits disponibles -->
      <div class="forfaits-list">
        <h3>Forfaits Disponibles</h3>
        <div v-if="loadingForfaits" class="loading">Chargement...</div>
        <div v-else-if="availableForfaits.length === 0" class="empty-state">
          <p>Aucun forfait disponible</p>
        </div>
        <div v-else class="forfaits-grid">
          <div 
            v-for="forfait in availableForfaits" 
            :key="forfait.id_forfait"
            class="forfait-card"
            :class="{ 'forfait-active': forfait.id_forfait === currentForfait?.id_forfait }"
          >
            <div class="forfait-header">
              <h4>{{ forfait.nom }}</h4>
              <span class="forfait-price">{{ formatPrice(forfait.prix) }}</span>
            </div>
            <div class="forfait-details">
              <p><strong>Durée:</strong> {{ forfait.duree_jours }} jours</p>
              <p><strong>Description:</strong> {{ forfait.description || 'Aucune description' }}</p>
            </div>
            <button 
              @click="subscribeToForfait(forfait.id_forfait)"
              class="btn-subscribe"
              :disabled="forfait.id_forfait === currentForfait?.id_forfait"
            >
              {{ forfait.id_forfait === currentForfait?.id_forfait ? 'Actif' : 'Souscrire' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Onglet Utilisateurs -->
    <div v-if="activeTab === 'users'" class="settings-content">
      <div class="section-header">
        <h2>Gestion des Utilisateurs</h2>
        <button @click="openUserModal" class="btn-primary">Nouvel Utilisateur</button>
      </div>

      <!-- Filtres et recherche -->
      <div class="users-filters">
        <input 
          v-model="userSearchQuery"
          type="text"
          placeholder="Rechercher un utilisateur..."
          class="search-input"
        />
        <select v-model="userRoleFilter" class="filter-select">
          <option value="">Tous les rôles</option>
          <option value="admin">Admin</option>
          <option value="user">Utilisateur</option>
          <option value="superadmin">Super Admin</option>
        </select>
      </div>

      <!-- Liste des utilisateurs -->
      <div class="users-table-container">
        <div v-if="loadingUsers" class="loading">Chargement...</div>
        <div v-else-if="filteredUsers.length === 0" class="empty-state">
          <p>Aucun utilisateur trouvé</p>
        </div>
        <table v-else class="users-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Rôle</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id_utilisateur">
              <td>{{ user.prenom }} {{ user.nom }}</td>
              <td>{{ user.email }}</td>
              <td>
                <span class="role-badge" :class="`role-${user.role}`">
                  {{ user.role }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="user.statut === 'actif' ? 'status-active' : 'status-inactive'">
                  {{ user.statut === 'actif' ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td>
                <button @click="editUser(user)" class="btn-icon" title="Modifier">✏️</button>
                <button @click="deleteUser(user)" class="btn-icon btn-danger" title="Supprimer">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Forfait -->
    <div v-if="showForfaitModal" class="modal-overlay" @click.self="closeForfaitModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingForfait ? 'Modifier le Forfait' : 'Nouveau Forfait' }}</h3>
          <button @click="closeForfaitModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nom du forfait</label>
            <input v-model="forfaitForm.nom" type="text" placeholder="Ex: Forfait Basique" />
          </div>
          <div class="form-group">
            <label>Prix (F CFA)</label>
            <input v-model.number="forfaitForm.prix" type="number" placeholder="3000" />
          </div>
          <div class="form-group">
            <label>Durée (jours)</label>
            <input v-model.number="forfaitForm.duree_jours" type="number" placeholder="30" />
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="forfaitForm.description" placeholder="Description du forfait"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeForfaitModal" class="btn-secondary">Annuler</button>
          <button @click="saveForfait" class="btn-primary">Enregistrer</button>
        </div>
      </div>
    </div>

    <!-- Modal Utilisateur -->
    <div v-if="showUserModal" class="modal-overlay" @click.self="closeUserModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingUser ? 'Modifier l\'Utilisateur' : 'Nouvel Utilisateur' }}</h3>
          <button @click="closeUserModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Prénom</label>
            <input v-model="userForm.prenom" type="text" required />
          </div>
          <div class="form-group">
            <label>Nom</label>
            <input v-model="userForm.nom" type="text" required />
          </div>
          <div class="form-group">
            <label>Email</label>
            <input v-model="userForm.email" type="email" required />
          </div>
          <div class="form-group">
            <label>Nom d'utilisateur</label>
            <input v-model="userForm.username" type="text" required />
          </div>
          <div class="form-group" v-if="!editingUser">
            <label>Mot de passe</label>
            <input v-model="userForm.password" type="password" required />
          </div>
          <div class="form-group">
            <label>Rôle</label>
            <select v-model="userForm.role">
              <option value="user">Utilisateur</option>
              <option value="admin">Admin</option>
              <option value="superadmin">Super Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label>Statut</label>
            <select v-model="userForm.statut">
              <option value="actif">Actif</option>
              <option value="inactif">Inactif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeUserModal" class="btn-secondary">Annuler</button>
          <button @click="saveUser" class="btn-primary">Enregistrer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiService } from '../composables/Api/apiService.js'
import { useCurrency } from '../composables/useCurrency.js'

const { formatPrice } = useCurrency()

const activeTab = ref('forfaits')
const tabs = [
  { id: 'forfaits', label: 'Forfaits' },
  { id: 'users', label: 'Utilisateurs' }
]

// Forfaits
const loadingForfaits = ref(false)
const currentForfait = ref(null)
const availableForfaits = ref([])
const showForfaitModal = ref(false)
const editingForfait = ref(null)
const forfaitForm = ref({
  nom: '',
  prix: 0,
  duree_jours: 30,
  description: ''
})

// Utilisateurs
const loadingUsers = ref(false)
const users = ref([])
const userSearchQuery = ref('')
const userRoleFilter = ref('')
const showUserModal = ref(false)
const editingUser = ref(null)
const userForm = ref({
  prenom: '',
  nom: '',
  email: '',
  username: '',
  password: '',
  role: 'user',
  statut: 'actif'
})

const filteredUsers = computed(() => {
  let filtered = users.value

  if (userSearchQuery.value) {
    const query = userSearchQuery.value.toLowerCase()
    filtered = filtered.filter(u => 
      u.nom.toLowerCase().includes(query) ||
      u.prenom.toLowerCase().includes(query) ||
      u.email.toLowerCase().includes(query) ||
      u.username.toLowerCase().includes(query)
    )
  }

  if (userRoleFilter.value) {
    filtered = filtered.filter(u => u.role === userRoleFilter.value)
  }

  return filtered
})

// Charger les données
const loadForfaitStatus = async () => {
  try {
    const response = await apiService.get('/api_forfait.php?action=status')
    if (response.success && response.data) {
      currentForfait.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement du statut du forfait:', error)
  }
}

const loadAvailableForfaits = async () => {
  loadingForfaits.value = true
  try {
    const response = await apiService.get('/api_forfait.php?action=forfaits')
    if (response.success) {
      availableForfaits.value = response.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des forfaits:', error)
  } finally {
    loadingForfaits.value = false
  }
}

const loadUsers = async () => {
  loadingUsers.value = true
  try {
    const response = await apiService.get('/index.php?action=all')
    if (response.success) {
      users.value = response.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  } finally {
    loadingUsers.value = false
  }
}

// Forfaits
const openForfaitModal = () => {
  editingForfait.value = null
  forfaitForm.value = {
    nom: '',
    prix: 0,
    duree_jours: 30,
    description: ''
  }
  showForfaitModal.value = true
}

const closeForfaitModal = () => {
  showForfaitModal.value = false
  editingForfait.value = null
}

const subscribeToForfait = async (forfaitId) => {
  try {
    const response = await apiService.post('/api_forfait.php', {
      id_forfait: forfaitId
    })
    if (response.success) {
      alert('Forfait souscrit avec succès!')
      await loadForfaitStatus()
      await loadAvailableForfaits()
    }
  } catch (error) {
    console.error('Erreur lors de la souscription:', error)
    alert('Erreur lors de la souscription au forfait')
  }
}

const saveForfait = async () => {
  // TODO: Implémenter la création/modification de forfait via API
  alert('Fonctionnalité à implémenter')
  closeForfaitModal()
}

// Utilisateurs
const openUserModal = () => {
  editingUser.value = null
  userForm.value = {
    prenom: '',
    nom: '',
    email: '',
    username: '',
    password: '',
    role: 'user',
    statut: 'actif'
  }
  showUserModal.value = true
}

const closeUserModal = () => {
  showUserModal.value = false
  editingUser.value = null
}

const editUser = (user) => {
  editingUser.value = user
  userForm.value = {
    prenom: user.prenom || '',
    nom: user.nom || '',
    email: user.email || '',
    username: user.username || '',
    password: '',
    role: user.role || 'user',
    statut: user.statut || 'actif'
  }
  showUserModal.value = true
}

const saveUser = async () => {
  try {
    let response
    if (editingUser.value) {
      // Mise à jour
      response = await apiService.put(`/index.php?id=${editingUser.value.id_utilisateur}`, userForm.value)
    } else {
      // Création
      response = await apiService.post('/index.php', userForm.value)
    }
    
    if (response.success) {
      alert(editingUser.value ? 'Utilisateur modifié avec succès!' : 'Utilisateur créé avec succès!')
      await loadUsers()
      closeUserModal()
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    alert('Erreur lors de la sauvegarde de l\'utilisateur')
  }
}

const deleteUser = async (user) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${user.prenom} ${user.nom}?`)) {
    return
  }
  
  try {
    const response = await apiService.delete(`/index.php?id=${user.id_utilisateur}`)
    if (response.success) {
      alert('Utilisateur supprimé avec succès!')
      await loadUsers()
    }
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    alert('Erreur lors de la suppression de l\'utilisateur')
  }
}

// Utilitaires
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const getForfaitStatusClass = (dateFin) => {
  if (!dateFin) return ''
  const now = new Date()
  const fin = new Date(dateFin)
  const daysLeft = Math.ceil((fin - now) / (1000 * 60 * 60 * 24))
  
  if (daysLeft < 0) return 'status-expired'
  if (daysLeft <= 2) return 'status-red'
  if (daysLeft <= 7) return 'status-orange'
  return 'status-ok'
}

onMounted(async () => {
  await loadForfaitStatus()
  await loadAvailableForfaits()
  await loadUsers()
})
</script>

<style scoped>
.settings-page {
  padding: 2rem;
}

.settings-header h1 {
  margin-bottom: 2rem;
  color: #333;
}

.settings-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #e0e0e0;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 1rem;
  color: #666;
  transition: all 0.3s;
}

.tab-button.active {
  color: #4a90e2;
  border-bottom-color: #4a90e2;
}

.settings-content {
  margin-top: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.section-header h2 {
  margin: 0;
  color: #333;
}

.current-forfait-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.current-forfait-card h3 {
  margin-top: 0;
  margin-bottom: 1rem;
}

.forfait-info {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.info-row {
  display: flex;
  flex-direction: column;
}

.label {
  font-weight: 600;
  color: #666;
  font-size: 0.9rem;
}

.value {
  color: #333;
  margin-top: 0.25rem;
}

.status-active {
  color: #28a745;
}

.status-expired {
  color: #dc3545;
}

.status-red {
  color: #dc3545;
}

.status-orange {
  color: #ff9800;
}

.status-ok {
  color: #28a745;
}

.forfaits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.forfait-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: transform 0.2s;
}

.forfait-card:hover {
  transform: translateY(-2px);
}

.forfait-card.forfait-active {
  border: 2px solid #4a90e2;
}

.forfait-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.forfait-price {
  font-size: 1.5rem;
  font-weight: bold;
  color: #4a90e2;
}

.users-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.search-input,
.filter-select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.search-input {
  flex: 1;
}

.users-table-container {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.users-table th {
  background: #f5f5f5;
  font-weight: 600;
}

.role-badge,
.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 500;
}

.role-admin {
  background: #e3f2fd;
  color: #1976d2;
}

.role-user {
  background: #f3e5f5;
  color: #7b1fa2;
}

.role-superadmin {
  background: #fff3e0;
  color: #e65100;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 0.25rem 0.5rem;
  margin: 0 0.25rem;
}

.btn-icon:hover {
  opacity: 0.7;
}

.btn-danger {
  color: #dc3545;
}

.btn-primary,
.btn-secondary,
.btn-subscribe {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.3s;
}

.btn-primary {
  background: #4a90e2;
  color: white;
}

.btn-primary:hover {
  background: #357abd;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-subscribe {
  width: 100%;
  margin-top: 1rem;
  background: #28a745;
  color: white;
}

.btn-subscribe:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-group textarea {
  min-height: 100px;
  resize: vertical;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e0e0e0;
}

.loading,
.empty-state {
  text-align: center;
  padding: 2rem;
  color: #666;
}
</style>
