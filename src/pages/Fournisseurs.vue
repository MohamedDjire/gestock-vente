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
<!-- Fin du template principal -->
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
    <!-- Formulaire d'ajout/modification (modale harmonisée) -->

    <div v-if="showAddForm || editingFournisseur" class="modal-overlay" @click.self="closeForm">
      <div class="modal-content large" @click.stop>
          <h3>{{ editingFournisseur ? 'Modifier' : 'Ajouter' }} un fournisseur</h3>
          <button @click="closeForm" class="modal-close">×</button>
        <div class="modal-body">
          <form @submit.prevent="submitForm" class="modal-form">
            <div v-if="formError" class="form-error" style="color: #dc2626; font-weight: 600; margin-bottom: 1rem;">{{ formError }}</div>

            <div class="form-section">
              <h4 class="section-title">🏢 Informations fournisseur</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Nom *</label>
                  <input v-model="form.nom" placeholder="Nom du fournisseur" required />
                  <small class="form-hint">Raison sociale ou nom du fournisseur</small>
                </div>
                <div class="form-group">
                  <label>Statut</label>
                  <select v-model="form.statut">
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-section">
              <h4 class="section-title">📞 Coordonnées</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Email</label>
                  <input v-model="form.email" type="email" placeholder="email@exemple.com" />
                </div>
                <div class="form-group">
                  <label>Téléphone</label>
                  <input v-model="form.telephone" placeholder="+225 XX XX XX XX XX" />
                </div>
              </div>
              <div class="form-group">
                <label>Adresse</label>
                <input v-model="form.adresse" placeholder="Adresse complète" />
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" @click="closeForm" class="btn-cancel">Annuler</button>
          <button type="button" @click="submitForm" class="btn-save">Valider</button>
        </div>
      </div>
    </div>

    <!-- Modale de suppression -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
      <div class="modal-content confirmation-modal" @click.stop>
        <div class="modal-header modal-header-with-icon">
          <div class="modal-header-start">
            <span class="modal-header-icon">⚠️</span>
            <h3>Confirmer la suppression</h3>
          </div>
          <button @click="closeDeleteModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir supprimer le fournisseur <b>{{ fournisseurToDelete?.nom }}</b> ?</p>
          <p class="modal-warning">Cette action est irréversible.</p>
        </div>
        <div class="modal-actions">
          <button @click="closeDeleteModal" class="btn-cancel">Annuler</button>
          <button @click="confirmDeleteFournisseur" class="btn-danger">Supprimer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiFournisseur from '../composables/Api/apiFournisseur'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { useAuthStore } from '../stores/auth.js'

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
const authStore = useAuthStore()
const entrepriseNom = authStore.user?.nom_entreprise || 'Nom de l’entreprise'

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
    let actionType = ''
    let fournisseurCreated = null
    if (editingFournisseur.value) {
      await apiFournisseur.update(editingFournisseur.value.id, form.value)
      actionType = 'Modification fournisseur'
    } else {
      fournisseurCreated = await apiFournisseur.create(form.value)
      actionType = 'Ajout fournisseur'
      // Création écriture comptable automatique (achat)
      let id_entreprise = null
      const user = localStorage.getItem('prostock_user')
      if (user) {
        id_entreprise = JSON.parse(user).id_entreprise
      }
      if (id_entreprise) {
      }
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
  const doc = new jsPDF();

  // Header chic : logo rond + nom entreprise + fond
  doc.setFillColor(26,95,74);
  doc.roundedRect(0, 0, 210, 30, 0, 0, 'F');
  doc.setFillColor(255,255,255);
  doc.circle(22, 15, 8, 'F');
  doc.setTextColor(26,95,74);
  doc.setFontSize(13);
  doc.setFont('helvetica', 'bold');
  doc.text('PS', 18, 18);
  doc.setTextColor(255,255,255);
  doc.setFontSize(15);
  doc.text(entrepriseNom, 210-14, 18, { align: 'right' });

  // Titre centré
  doc.setFontSize(16);
  doc.setTextColor(26,95,74);
  doc.setFont('helvetica', 'bold');
  doc.text('Liste des fournisseurs', 105, 38, { align: 'center' });

  // Bloc stats
  const total = fournisseurs.value.length;
  const actifs = fournisseurs.value.filter(f => f.statut === 'actif').length;
  const inactifs = fournisseurs.value.filter(f => f.statut === 'inactif').length;
  doc.setFillColor(240, 253, 244);
  doc.roundedRect(40, 44, 130, 12, 4, 4, 'F');
  doc.setFontSize(11);
  doc.setTextColor(26,95,74);
  doc.text(`Total : ${total}   |   Actifs : ${actifs}   |   Inactifs : ${inactifs}`, 105, 52, { align: 'center' });

  // Tableau stylé
  const rows = fournisseurs.value.map(f => [f.nom, f.email, f.telephone, f.adresse, f.statut]);
  autoTable(doc, {
    head: [['Nom', 'Email', 'Téléphone', 'Adresse', 'Statut']],
    body: rows,
    startY: 60,
    theme: 'striped',
    styles: { fontSize: 10, cellPadding: 2 },
    headStyles: { fillColor: [26, 95, 74], textColor: 255, fontStyle: 'bold' },
    alternateRowStyles: { fillColor: [240, 253, 244] },
    margin: { left: 14, right: 14 }
  });

  // Pied de page chic
  const pageCount = doc.internal.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setDrawColor(220);
    doc.line(14, 285, 196, 285);
    doc.setFontSize(9);
    doc.setTextColor(120,120,120);
    doc.text('ProStock - Export PDF', 14, 290);
    doc.text(`Page ${i} / ${pageCount}`, 200, 290, { align: 'right' });
    doc.text(new Date().toLocaleDateString(), 105, 290, { align: 'center' });
  }

  doc.save('fournisseurs.pdf');
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
/* Modale formulaire et suppression : style.css (modal-overlay, modal-content, modal-form, form-section, btn-cancel, btn-save, btn-danger) */

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
  border: 1px solid #e5e7eb;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.fournisseurs-table {
  width: 100%;
  border-collapse: collapse;
}
.fournisseurs-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}
.fournisseurs-table th {
  padding: 0.875rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.fournisseurs-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #1f2937;
  border-bottom: 1px solid #f3f4f6;
}
.fournisseurs-table tbody tr {
  transition: background 0.2s;
}
.fournisseurs-table tbody tr:hover {
  background: #f9fafb;
}
.fournisseurs-table tr:last-child td {
  border-bottom: none;
}
.actions-column {
  width: 120px;
  text-align: center;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
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
/* Icône supprimer dans le tableau : rouge, sans fond (la modale utilise .btn-danger global) */
.btn-icon.btn-danger {
  color: #dc2626;
}
.btn-icon.btn-danger:hover {
  color: #b91c1c;
  background: #fee2e2;
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
.form-section {
  background: #f6faf9;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  padding: 1.5rem;
  border: 2px solid rgba(26, 95, 74, 0.13);
}
.section-title {
  margin: 0 0 1rem 0;
  color: #1a5f4a;
  font-size: 1.1rem;
  font-weight: 700;
}
.modal-content.large {
  max-width: 700px;
  width: 98vw;
}
.fournisseurs-table tr:last-child td {
  border-bottom: none;
}
</style>
