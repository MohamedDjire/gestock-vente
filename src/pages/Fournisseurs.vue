<template>
  <div class="fournisseurs-page">
    <div class="page-header">
      <h1 class="page-title">Fournisseurs</h1>
      <div class="actions">
        <div class="search-box">
          <input v-model="search" placeholder="Rechercher un fournisseur..." class="search-input" />
        </div>
        <button @click="showAddForm = true" class="btn-primary">Ajouter un fournisseur</button>
        <button @click="exportExcel" class="btn-primary" style="background:#2563eb; margin-left:8px;">Exporter Excel</button>
        <button @click="exportPDF" class="btn-primary" style="background:#dc2626; margin-left:8px;">Exporter PDF</button>
      </div>
    </div>

    <div class="table-container">
      <table class="fournisseurs-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Adresse</th>
            <th>Statut</th>
            <th class="actions-column">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6">Chargement...</td>
          </tr>
          <tr v-else-if="error">
            <td colspan="6" class="error">{{ error }}</td>
          </tr>
          <tr v-else-if="filteredFournisseurs.length === 0">
            <td colspan="6">Aucun fournisseur trouvé</td>
          </tr>
          <tr v-else v-for="fournisseur in filteredFournisseurs" :key="fournisseur.id">
            <td>{{ fournisseur.nom }}</td>
            <td>{{ fournisseur.email }}</td>
            <td>{{ fournisseur.telephone }}</td>
            <td>{{ fournisseur.adresse }}</td>
            <td>{{ fournisseur.statut }}</td>
            <td class="actions-column">
              <button @click="editFournisseur(fournisseur)" class="btn-icon" title="Modifier" aria-label="Modifier">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              <button @click="openDeleteModal(fournisseur)" class="btn-icon btn-danger" title="Supprimer" aria-label="Supprimer">
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
    <div v-if="showAddForm || editingFournisseur" class="modal" @click.self="closeForm">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">{{ editingFournisseur ? 'Modifier' : 'Ajouter' }} un fournisseur</h2>
          <button @click="closeForm" class="btn-close">×</button>
        </div>
        <form @submit.prevent="submitForm" class="modal-form">
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="form-group">
            <label>Nom *</label>
            <input v-model="form.nom" placeholder="Nom du fournisseur" required class="form-input" />
          </div>
          <div class="form-group">
            <label>Email</label>
            <input v-model="form.email" placeholder="Email" type="email" class="form-input" />
          </div>
          <div class="form-group">
            <label>Téléphone</label>
            <input v-model="form.telephone" placeholder="Téléphone" class="form-input" />
          </div>
          <div class="form-group">
            <label>Adresse</label>
            <input v-model="form.adresse" placeholder="Adresse" class="form-input" />
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

    <!-- Modale de suppression -->
    <div v-if="showDeleteModal" class="modal" @click.self="closeDeleteModal">
      <div class="modal-content">
        <h2>Confirmer la suppression</h2>
        <p>Supprimer le fournisseur <b>{{ fournisseurToDelete?.nom }}</b> ?</p>
        <div class="modal-footer">
          <button @click="closeDeleteModal">Annuler</button>
          <button @click="confirmDeleteFournisseur" class="btn-danger">Supprimer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiFournisseur from '../composables/api/apiFournisseur'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const fournisseurs = ref([])
const loading = ref(false)
const error = ref('')
const search = ref('')
const showAddForm = ref(false)
const editingFournisseur = ref(null)
const showDeleteModal = ref(false)
const fournisseurToDelete = ref(null)
const formError = ref('')
const form = ref({ nom: '', email: '', telephone: '', adresse: '', statut: 'actif' })

const filteredFournisseurs = computed(() => {
  if (!search.value) return fournisseurs.value
  const s = search.value.toLowerCase()
  return fournisseurs.value.filter(f =>
    (f.nom && f.nom.toLowerCase().includes(s)) ||
    (f.email && f.email.toLowerCase().includes(s)) ||
    (f.telephone && f.telephone.toLowerCase().includes(s)) ||
    (f.adresse && f.adresse.toLowerCase().includes(s))
  )
})

const fetchFournisseurs = async () => {
  loading.value = true
  error.value = ''
  try {
    fournisseurs.value = await apiFournisseur.getAll()
  } catch (e) {
    error.value = e.message || 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
}

onMounted(fetchFournisseurs)

function editFournisseur(f) {
  editingFournisseur.value = { ...f }
  form.value = { ...f }
  showAddForm.value = false
}
function openDeleteModal(f) {
  fournisseurToDelete.value = f
  showDeleteModal.value = true
}
function closeDeleteModal() {
  showDeleteModal.value = false
  fournisseurToDelete.value = null
}
function closeForm() {
  showAddForm.value = false
  editingFournisseur.value = null
  form.value = { nom: '', email: '', telephone: '', adresse: '', statut: 'actif' }
  formError.value = ''
}
async function submitForm() {
  if (!form.value.nom) {
    formError.value = 'Le nom est obligatoire'
    return
  }
  formError.value = ''
  try {
    if (editingFournisseur.value) {
      await apiFournisseur.update(editingFournisseur.value.id, form.value)
    } else {
      await apiFournisseur.create(form.value)
    }
    closeForm()
    await fetchFournisseurs()
  } catch (e) {
    formError.value = e.message || 'Erreur lors de l\'enregistrement'
  }
}
async function confirmDeleteFournisseur() {
  if (!fournisseurToDelete.value) return
  try {
    await apiFournisseur.delete(fournisseurToDelete.value.id)
    closeDeleteModal()
    await fetchFournisseurs()
  } catch (e) {
    error.value = e.message || 'Erreur lors de la suppression'
  }
}

function exportExcel() {
  const data = fournisseurs.value.map(f => ({
    Nom: f.nom,
    Email: f.email,
    Téléphone: f.telephone,
    Adresse: f.adresse,
    Statut: f.statut
  }))
  const ws = XLSX.utils.json_to_sheet(data)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Fournisseurs')
  XLSX.writeFile(wb, 'fournisseurs.xlsx')
}

function exportPDF() {
  const doc = new jsPDF()
  doc.text('Liste des fournisseurs', 14, 16)
  const rows = fournisseurs.value.map(f => [f.nom, f.email, f.telephone, f.adresse, f.statut])
  autoTable(doc, {
    head: [['Nom', 'Email', 'Téléphone', 'Adresse', 'Statut']],
    body: rows,
    startY: 22,
    theme: 'grid',
    styles: { fontSize: 10 }
  })
  doc.save('fournisseurs.pdf')
}
</script>

<style scoped>
.fournisseurs-page {
  padding: 2rem;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}
.page-title {
  font-size: 2rem;
  font-weight: 700;
}
.actions {
  display: flex;
  gap: 1rem;
}
.search-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.search-input {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  background: #f8fafc;
}
.table-container {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: auto;
}
.fournisseurs-table {
  width: 100%;
  border-collapse: collapse;
}
.fournisseurs-table th, .fournisseurs-table td {
  padding: 0.75rem 0.875rem;
  border-bottom: 1px solid #f1f5f9;
  text-align: left;
}
.fournisseurs-table th {
  background: #f8fafc;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
}
.actions-column {
  width: 120px;
  text-align: center;
}
.btn-primary {
  background: #1a5f4a;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.25rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-primary:hover {
  background: #134e3a;
}
.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  margin: 0 0.25rem;
}
.btn-danger {
  color: #dc2626;
}
.modal {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content {
  background: #fff;
  border-radius: 12px;
  padding: 2rem;
  min-width: 320px;
  max-width: 95vw;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  position: relative;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
}
.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
  margin-left: 1rem;
}
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.form-input {
  padding: 0.625rem 0.875rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  background: #fff;
}
.form-input:focus {
  outline: none;
  border-color: #1a5f4a;
  box-shadow: 0 0 0 3px rgba(26, 95, 74, 0.1);
}
.btn-secondary {
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
.btn-secondary:hover {
  background: #d1d5db;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}
.form-error {
  color: #dc2626;
  margin-bottom: 1rem;
  font-weight: 600;
}
.error {
  color: #dc2626;
  font-weight: 600;
}
</style>
