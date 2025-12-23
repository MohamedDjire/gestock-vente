<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <div class="dashboard-wrapper">
        <Topbar />
        <div class="clients-page">
          <div class="page-header">
            <h1 class="page-title">Clients</h1>
            <div class="actions">
              <div class="search-box">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"></circle>
                  <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input v-model="search" placeholder="Rechercher un client..." class="search-input" />
              </div>
              <button @click="showAddForm = true" class="btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Ajouter un client
              </button>
            </div>
          </div>

          <div class="table-container">
            <table class="clients-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Prénom</th>
                  <th>Entreprise</th>
                  <th>Email</th>
                  <th>Téléphone</th>
                  <th>Ajouté par</th>
                  <th>Statut</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="client in filteredClients" :key="client.id">
                  <td class="font-medium">{{ client.nom }}</td>
                  <td>{{ client.prenom }}</td>
                  <td>{{ client.nom_entreprise }}</td>
                  <td class="text-muted">{{ client.email }}</td>
                  <td class="text-muted">{{ client.telephone }}</td>
                  <td>{{ client.nom_utilisateur }}</td>
                  <td>
                    <span :class="['status-badge', client.statut === 'actif' ? 'status-active' : 'status-inactive']">
                      {{ client.statut }}
                    </span>
                  </td>
                  <td class="actions-column">
                    <button @click="editClient(client)" class="btn-icon" title="Modifier">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button @click="deleteClient(client.id)" class="btn-icon btn-danger" title="Supprimer">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Formulaire d'ajout/modification -->
          <div v-if="showAddForm || editingClient" class="modal" @click.self="closeForm">
            <div class="modal-content">
              <div class="modal-header">
                <h2 class="modal-title">{{ editingClient ? 'Modifier' : 'Ajouter' }} un client</h2>
                <button @click="closeForm" class="btn-close">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              </div>
              <form @submit.prevent="submitForm" class="modal-form">
                <div class="form-row">
                  <div class="form-group">
                    <label>Nom *</label>
                    <input v-model="form.nom" placeholder="Nom" required class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Prénom</label>
                    <input v-model="form.prenom" placeholder="Prénom" class="form-input" />
                  </div>
                </div>
                <div class="form-group">
                  <label>Entreprise *</label>
                  <input v-model="form.entreprise" placeholder="Nom de l'entreprise" required class="form-input" />
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Email *</label>
                    <input v-model="form.email" type="email" placeholder="email@exemple.com" required class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Téléphone</label>
                    <input v-model="form.telephone" placeholder="+225 XX XX XX XX XX" class="form-input" />
                  </div>
                </div>
                <div class="form-group">
                  <label>Statut</label>
                  <select v-model="form.statut" class="form-input">
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                  </select>
                </div>
                <div class="modal-footer">
                  <button type="button" @click="closeForm" class="btn-secondary">Annuler</button>
                  <button type="submit" class="btn-primary">Valider</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import Sidebar from '../components/Sidebar.vue'
import Topbar from '../components/Topbar.vue'
const APIURL='https://aliadjame.com/api-stock/clients.php'

const clients = ref([])
const search = ref('')
const showAddForm = ref(false)
const editingClient = ref(null)
const form = ref({
  nom: '',
  prenom: '',
  email: '',
  telephone: '',
  entreprise: '',
  statut: 'actif',
})

const id_utilisateur = 1
const id_entreprise = 1

const fetchClients = async () => {
  const res = await axios.get(APIURL)
  clients.value = res.data
}

onMounted(fetchClients)

const filteredClients = computed(() => {
  if (!search.value) return clients.value
  return clients.value.filter(c =>
    c.nom.toLowerCase().includes(search.value.toLowerCase()) ||
    c.prenom?.toLowerCase().includes(search.value.toLowerCase()) ||
    c.email?.toLowerCase().includes(search.value.toLowerCase())
  )
})

const submitForm = async () => {
  const data = { ...form.value, id_utilisateur, id_entreprise }
  if (editingClient.value) {
    await axios.put(APIURL, { ...data, id: editingClient.value.id })
  } else {
    await axios.post(APIURL, data)
  }
  closeForm()
  fetchClients()
}

const editClient = (client) => {
  editingClient.value = client
  form.value = { ...client }
}

const deleteClient = async (id) => {
  if (confirm('Supprimer ce client ?')) {
    await axios.delete(APIURL, { data: { id } })
    fetchClients()
  }
}

const closeForm = () => {
  showAddForm.value = false
  editingClient.value = null
  form.value = { nom: '', prenom: '', email: '', telephone: '', entreprise: '', statut: 'actif' }
}
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  width: 100vw;
  background: #fafbfc;
  overflow-x: auto;
  font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
  overflow: auto;
  margin-left: 250px;
  max-width: 100vw;
  background: #fafbfc;
}

.dashboard-wrapper {
  background: transparent;
  min-height: 100vh;
  width: 100%;
  display: flex;
  flex-direction: column;
  padding-left: 2.5rem;
}

.clients-page {
  flex: 1;
  padding: 2.5rem 2.5rem 2.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  letter-spacing: -0.02em;
}

.actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 1rem;
  color: #9ca3af;
}

.search-input {
  padding: 0.625rem 1rem 0.625rem 2.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  width: 300px;
  transition: all 0.2s;
  background: #ffffff;
}

.search-input:focus {
  outline: none;
  border-color: #1a5f4a;
  box-shadow: 0 0 0 3px rgba(26, 95, 74, 0.1);
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-primary:hover {
  background: #145040;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(26, 95, 74, 0.2);
}

.btn-secondary {
  padding: 0.625rem 1.25rem;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.table-container {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.clients-table {
  width: 100%;
  border-collapse: collapse;
}

.clients-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.clients-table th {
  padding: 0.875rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.clients-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #1f2937;
  border-bottom: 1px solid #f3f4f6;
}

.clients-table tbody tr {
  transition: background 0.2s;
}

.clients-table tbody tr:hover {
  background: #f9fafb;
}

.clients-table tbody tr:last-child td {
  border-bottom: none;
}

.font-medium {
  font-weight: 600;
}

.text-muted {
  color: #6b7280;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-inactive {
  background: #fee2e2;
  color: #991b1b;
}

.actions-column {
  width: 120px;
}

.btn-icon {
  padding: 0.5rem;
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-icon:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.btn-danger:hover {
  background: #fee2e2;
  color: #dc2626;
}

.modal {
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
  backdrop-filter: blur(4px);
}

.modal-content {
  background: #ffffff;
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.btn-close {
  padding: 0.5rem;
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.modal-form {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.form-input {
  padding: 0.625rem 0.875rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s;
  background: #ffffff;
}

.form-input:focus {
  outline: none;
  border-color: #1a5f4a;
  box-shadow: 0 0 0 3px rgba(26, 95, 74, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

@media (max-width: 1100px) {
  .main-content {
    margin-left: 0;
  }
  .dashboard-wrapper {
    padding-left: 1.5rem;
  }
  .clients-page {
    padding: 1.5rem 1.5rem 1.5rem 0;
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .actions {
    flex-direction: column;
  }
  
  .search-input {
    width: 100%;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .clients-table {
    font-size: 0.75rem;
  }
  
  .clients-table th,
  .clients-table td {
    padding: 0.75rem 0.5rem;
  }
}
</style>