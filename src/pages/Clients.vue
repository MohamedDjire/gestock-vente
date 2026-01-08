<template>
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
              <button @click="exportExcel" class="btn-primary" style="background:#2563eb; margin-left:8px;">Exporter Excel</button>
              <button @click="exportPDF" class="btn-primary" style="background:#dc2626; margin-left:8px;">Exporter PDF</button>
            </div>
          </div>

    <div class="table-container">
            <div v-if="loading" class="loading-message">Chargement des clients...</div>
            <div v-else-if="error" class="error-message">
              <p>⚠️ {{ error }}</p>
              <button @click="fetchClients" class="btn-retry">Réessayer</button>
            </div>
            <table v-else class="clients-table">
              <thead>
                <tr>
                  <th>Nom complet</th>
                  <th>Email</th>
                  <th>Téléphone</th>
                  <th>Ajouté par</th>
                  <th>Statut</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredClients.length === 0">
                  <td colspan="6" class="empty-message">Aucun client trouvé</td>
                </tr>
                <tr v-else v-for="client in filteredClients" :key="client.id">
                  <td class="font-medium">
                    <span v-if="client.type === 'entreprise'">{{ client.nom_entreprise }}</span>
                    <span v-else>{{ client.nom }} {{ client.prenom }}</span>
                  </td>
                  <td>{{ client.email }}</td>
                  <td>{{ client.telephone }}</td>
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
                    <button @click="openDeleteModal(client)" class="btn-icon btn-danger" title="Supprimer">
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
            <!-- ...svg... -->
          </button>
        </div>
        <form @submit.prevent="submitForm" class="modal-form">
          <div v-if="formError" class="form-error" style="color: #dc2626; font-weight: 600; margin-bottom: 1rem;">{{ formError }}</div>
          <div class="form-group">
            <label>Type de client *</label>
            <select v-model="form.type" class="form-input" required>
              <option value="particulier">Particulier</option>
              <option value="entreprise">Entreprise</option>
            </select>
          </div>
          <div v-if="form.type === 'particulier'" class="form-row">
            <div class="form-group">
              <label>Nom *</label>
              <input v-model="form.nom" placeholder="Nom" required class="form-input" />
            </div>
            <div class="form-group">
              <label>Prénom *</label>
              <input v-model="form.prenom" placeholder="Prénom" required class="form-input" />
            </div>
          </div>
          <div v-else class="form-group">
            <label>Nom de l'entreprise *</label>
            <input v-model="form.nom_entreprise" placeholder="Nom de l'entreprise" required class="form-input" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email</label>
              <input v-model="form.email" type="email" placeholder="email@exemple.com" class="form-input" />
            </div>
            <div class="form-group">
              <label>Téléphone</label>
              <input v-model="form.telephone" placeholder="+225 XX XX XX XX XX" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label>Adresse</label>
            <input v-model="form.adresse" placeholder="Adresse du client" class="form-input" />
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

    <!-- Modale de suppression harmonisée -->
    <div v-if="showDeleteModal" class="confirmation-overlay" @click.self="closeDeleteModal">
      <div class="confirmation-modal">
        <div class="confirmation-header">
          <span class="confirmation-icon">⚠️</span>
          <h3>Confirmer la suppression</h3>
        </div>
        <div class="confirmation-body">
          <p>Êtes-vous sûr de vouloir supprimer ce client&nbsp;?
            <span v-if="clientToDelete?.type === 'entreprise'">Entreprise : <b>{{ clientToDelete.nom_entreprise }}</b></span>
            <span v-else>Client : <b>{{ clientToDelete.nom }} {{ clientToDelete.prenom }}</b></span>
          </p>
          <p style="color:#dc2626;font-weight:600;">Cette action est irréversible.</p>
        </div>
        <div class="confirmation-actions">
          <button @click="closeDeleteModal" class="btn-cancel">Annuler</button>
          <button @click="confirmDeleteClient" class="btn-danger">Supprimer</button>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
  /* Modale de suppression harmonisée (style Produits/Stock) */
  .confirmation-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
  }
  .confirmation-modal {
    background: #fff;
    border-radius: 12px;
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
  }
  .confirmation-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
  }
  .confirmation-header h3 {
    margin: 0;
    flex: 1;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a5f4a;
  }
  .confirmation-icon {
    font-size: 2rem;
  }
  .confirmation-body {
    padding: 1.5rem 0;
  }
  .confirmation-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
  }
  .btn-cancel {
    background: #e5e7eb;
    color: #1a1a1a;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
  }
  .btn-cancel:hover {
    background: #d1d5db;
  }
  .btn-danger {
    background: #dc2626;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
  }
  .btn-danger:hover {
    background: #991b1b;
  }
</style>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { logJournal } from '../composables/useJournal'
import apiClient from '../composables/Api/apiClient'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const clients = ref([])
const search = ref('')
const showAddForm = ref(false)
const formError = ref('')
const editingClient = ref(null)
const loading = ref(false)
const error = ref(null)
const form = ref({
  type: 'particulier',
  nom: '',
  prenom: '',
  nom_entreprise: '',
  email: '',
  telephone: '',
  adresse: '',
  statut: 'actif',
})

const getUserId = () => {
  const userStr = localStorage.getItem('prostock_user')
  if (userStr) {
    try {
      const user = JSON.parse(userStr)
      return user.id_utilisateur || user.id || null
    } catch {
      return null
    }
  }
  return null
}

const getEnterpriseId = () => {
  const userStr = localStorage.getItem('prostock_user')
  if (userStr) {
    try {
      const user = JSON.parse(userStr)
      return user.id_entreprise || null
    } catch {
      return null
    }
  }
  return null
}

const fetchClients = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await apiClient.get('/clients.php')
    if (res && res.data) {
      clients.value = Array.isArray(res.data) ? res.data : []
      console.log('Clients rechargés', clients.value)
    } else {
      clients.value = []
    }
  } catch (err) {
    console.error('Erreur lors du chargement des clients:', err)
    error.value = err.message || 'Erreur lors du chargement des clients'
    clients.value = []
  } finally {
    loading.value = false
  }
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
  formError.value = '';
  let response;
  let actionType = '';
  
  const id_utilisateur = getUserId()
  const id_entreprise = getEnterpriseId()
  
  if (!id_utilisateur || !id_entreprise) {
    formError.value = "Erreur: Impossible de récupérer les informations utilisateur. Veuillez vous reconnecter."
    return
  }
  
  try {
    if (editingClient.value) {
      const data = {
        ...form.value,
        id_utilisateur,
        id_entreprise,
        id: editingClient.value.id
      };
      response = await apiClient.post('/clients.php?_method=PUT', data);
      actionType = 'Modification client';
    } else {
      const data = { ...form.value, id_utilisateur, id_entreprise };
      response = await apiClient.post('/clients.php', data);
      actionType = 'Ajout client';
    }
    if (response.data && response.data.error && response.data.message && response.data.message.includes('Duplicate entry')) {
      formError.value = "L'email est déjà utilisé par un autre client.";
      return;
    }
    await logJournal({
      user: getJournalUser(),
      action: actionType,
      details: `Email: ${form.value.email}`
    });
    closeForm();
    fetchClients();
  } catch (e) {
    formError.value = "Erreur lors de l'envoi du formulaire.";
  }
}

const editClient = (client) => {
  editingClient.value = client
  form.value = {
    type: client.type || 'particulier',
    nom: client.nom || '',
    prenom: client.prenom || '',
    nom_entreprise: client.nom_entreprise || '',
    email: client.email || '',
    telephone: client.telephone || '',
    adresse: client.adresse || '',
    statut: client.statut || 'actif',
  }
}

const deleteClient = async (id) => {
  showDeleteModal.value = false
  clientToDelete.value = null
  const response = await apiClient.delete(`/clients.php?id=${id}`)
  console.log('Réponse suppression', response.data)
  await logJournal({
    user: getJournalUser(),
    action: 'Suppression client',
    details: `ID: ${id}`
  });
  fetchClients()
}

const showDeleteModal = ref(false)
const clientToDelete = ref(null)
const openDeleteModal = (client) => {
  clientToDelete.value = client
  showDeleteModal.value = true
}
const closeDeleteModal = () => {
  showDeleteModal.value = false
  clientToDelete.value = null
}
const confirmDeleteClient = async () => {
  if (clientToDelete.value) {
    await deleteClient(clientToDelete.value.id)
    closeDeleteModal()
  }
}

const closeForm = () => {
  showAddForm.value = false
  editingClient.value = null
  form.value = { type: 'particulier', nom: '', prenom: '', nom_entreprise: '', email: '', telephone: '', adresse: '', statut: 'actif' }
}

function exportExcel() {
  const data = clients.value.map(c => ({
    Nom: c.type === 'entreprise' ? c.nom_entreprise : c.nom + ' ' + c.prenom,
    Email: c.email,
    Téléphone: c.telephone,
    Statut: c.statut,
    AjoutéPar: c.nom_utilisateur
  }))
  const ws = XLSX.utils.json_to_sheet(data)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Clients')
  XLSX.writeFile(wb, 'clients.xlsx')
}

function exportPDF() {
  const doc = new jsPDF()
  doc.text('Liste des clients', 14, 16)
  const rows = clients.value.map(c => [c.type === 'entreprise' ? c.nom_entreprise : c.nom + ' ' + c.prenom, c.email, c.telephone, c.statut, c.nom_utilisateur])
  autoTable(doc, {
    head: [['Nom', 'Email', 'Téléphone', 'Statut', 'Ajouté par']],
    body: rows,
    startY: 22,
    theme: 'grid',
    styles: { fontSize: 10 }
  })
  doc.save('clients.pdf')
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
</script>

<style scoped>
  /* --- Harmonisation modale suppression avec produits --- */
  .modal-header.confirmation {
    background: #fffbeb;
    border-bottom: 1px solid #f59e0b;
  }
  .confirmation-icon {
    color: #f59e0b;
    font-size: 2rem;
    margin-right: 1rem;
  }
  .btn-danger {
    background: #ef4444;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }
  .btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
  }
  .btn-cancel {
    padding: 0.75rem 1.5rem;
    border: 1.5px solid #6b7280;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
  }
.clients-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  width: 100%;
  font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
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

  .loading-message, .error-message, .empty-message {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
  }

  .error-message {
    color: #dc2626;
  }

  .error-message p {
    margin-bottom: 1rem;
  }

  .btn-retry {
    padding: 0.5rem 1rem;
    background: #1a5f4a;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
  }

  .btn-retry:hover {
    background: #145040;
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