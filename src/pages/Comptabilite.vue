
<template>
  <div class="page-main compta-page">
    <div class="page-card compta-card">
      <div class="page-header compta-header-flex compta-header-actions">
        <h1 class="page-title compta-title">Comptabilité</h1>
        <button @click="openAddModal()" class="btn-primary compta-add-btn">➕ Ajouter un mouvement</button>
      </div>
      <div class="compta-statcards-row">
        <StatCard icon="💰" :title="'Solde actuel'" :value="formatCurrency(soldeActuel)" />
        <StatCard icon="📈" :title="'Total entrées'" :value="formatCurrency(totalEntrees)" />
        <StatCard icon="📉" :title="'Total sorties'" :value="formatCurrency(totalSorties)" />
      </div>
      <div class="compta-filter-box">
        <input v-model="search" type="text" placeholder="Rechercher (utilisateur, type, détails, référence, commentaire)" class="filter-input" />
        <div class="datepicker-wrapper">
          <VueDatePicker
            v-model="dateRange"
            range
            :format="'yyyy-MM-dd'"
            placeholder="Filtrer par date"
            :input-class="'filter-input datepicker-input'"
            :clearable="true"
            :editable="false"
          />
        </div>
        <select v-model="typeFilter" class="filter-input">
          <option value="">Tous types</option>
          <option value="Entrée">Entrée</option>
          <option value="Sortie">Sortie</option>
        </select>
        <select v-model="categorieFilter" class="filter-input">
          <option value="">Toutes catégories</option>
          <option value="Vente">Vente</option>
          <option value="Achat">Achat</option>
          <option value="Salaire">Salaire</option>
          <option value="Frais">Frais</option>
          <option value="Autre">Autre</option>
        </select>
        <select v-model="statutFilter" class="filter-input">
          <option value="">Tous statuts</option>
          <option value="validé">Validé</option>
          <option value="en attente">En attente</option>
          <option value="rejeté">Rejeté</option>
        </select>
        <select v-model="moyenPaiementFilter" class="filter-input">
          <option value="">Tous paiements</option>
          <option value="Espèces">Espèces</option>
          <option value="Virement">Virement</option>
          <option value="Carte">Carte</option>
          <option value="Chèque">Chèque</option>
          <option value="Autre">Autre</option>
        </select>
        <button @click="exportExcel" class="btn-primary btn-excel">Exporter Excel</button>
        <button @click="exportPDF" class="btn-primary btn-pdf">Exporter PDF</button>
      </div>
      <div class="table-container compta-table-container">
        <table class="main-table compta-main-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Catégorie</th>
              <th>Montant</th>
              <th>Utilisateur</th>
              <th>Moyen paiement</th>
              <th>Statut</th>
              <th>Référence</th>
              <th>Pièce jointe</th>
              <th>Commentaire</th>
              <th>Validateur</th>
              <th>Date validation</th>
              <th>Détails</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="14" class="empty-state">Chargement...</td>
            </tr>
            <tr v-else-if="error">
              <td colspan="14" class="empty-state error-state">{{ error }}</td>
            </tr>
            <tr v-else-if="filteredComptaEntries.length === 0">
              <td colspan="14" class="empty-state">Aucun mouvement trouvé</td>
            </tr>
            <tr v-else v-for="(entry, index) in paginatedComptaEntries" :key="index" class="data-row">
              <td class="date-cell">{{ formatComptaDate(entry.date) }}</td>
              <td><span class="type-badge" :class="getTypeClass(entry.type)">{{ entry.type }}</span></td>
              <td>{{ entry.categorie }}</td>
              <td class="montant-cell">{{ formatCurrency(entry.montant) }}</td>
              <td class="user-cell">{{ entry.user }}</td>
              <td>{{ entry.moyen_paiement }}</td>
              <td>{{ entry.statut }}</td>
              <td>{{ entry.reference }}</td>
              <td>
                <a v-if="entry.piece_jointe" :href="entry.piece_jointe" target="_blank">📎 Voir</a>
                <span v-else>—</span>
              </td>
              <td>{{ entry.commentaire }}</td>
              <td>{{ entry.utilisateur_validateur }}</td>
              <td>{{ formatComptaDate(entry.date_validation) }}</td>
              <td class="details-cell">
                <span class="details-text">{{ entry.details }}</span>
              </td>
              <td class="actions-cell">
                <button class="btn-action view" @click="openDetails(entry)" title="Voir détails"><span class="icon-view">👁️</span></button>
                <button class="btn-action edit" @click="openAddModal(entry)">✏️</button>
                <button class="btn-action delete" @click="askDeleteEntry(entry)">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="pagination" v-if="pageCount > 1">
        <button @click="page--" :disabled="page === 1" class="btn-secondary">Précédent</button>
        <span>Page {{ page }} / {{ pageCount }}</span>
        <button @click="page++" :disabled="page === pageCount" class="btn-secondary">Suivant</button>
      </div>
    </div>
    <!-- Modale ajout/édition harmonisée -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="closeAddModal">
      <div class="modal-content">
        <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;">
          <h3>{{ editingEntry === null ? 'Ajouter un mouvement' : 'Modifier le mouvement' }}</h3>
          <div style="display:flex;gap:0.5rem;align-items:center;">
            <button class="modal-close" @click="closeAddModal">×</button>
          </div>
        </div>
        <form @submit.prevent="submitAddEdit" class="user-form">
          <div class="grid-2-cols">
            <div class="form-group">
              <label>Date *</label>
              <input v-model="formEntry.date" type="datetime-local" required />
            </div>
            <div class="form-group">
              <label>Type *</label>
              <select v-model="formEntry.type" required>
                <option value="">Sélectionner</option>
                <option value="Entrée">Entrée</option>
                <option value="Sortie">Sortie</option>
              </select>
            </div>
            <div class="form-group">
              <label>Catégorie</label>
              <select v-model="formEntry.categorie">
                <option value="">Sélectionner</option>
                <option value="Vente">Vente</option>
                <option value="Achat">Achat</option>
                <option value="Salaire">Salaire</option>
                <option value="Frais">Frais</option>
                <option value="Autre">Autre</option>
              </select>
            </div>
            <div class="form-group">
              <label>Montant *</label>
              <input v-model.number="formEntry.montant" type="number" required min="0" />
            </div>
            <div class="form-group">
              <label>Utilisateur *</label>
              <input v-model="formEntry.user" type="text" required />
            </div>
            <div class="form-group">
              <label>Moyen paiement</label>
              <select v-model="formEntry.moyen_paiement">
                <option value="">Sélectionner</option>
                <option value="Espèces">Espèces</option>
                <option value="Virement">Virement</option>
                <option value="Carte">Carte</option>
                <option value="Chèque">Chèque</option>
                <option value="Autre">Autre</option>
              </select>
            </div>
            <div class="form-group">
              <label>Statut</label>
              <select v-model="formEntry.statut">
                <option value="en attente">En attente</option>
                <option value="validé">Validé</option>
                <option value="rejeté">Rejeté</option>
              </select>
            </div>
            <div class="form-group">
              <label>Référence</label>
              <input v-model="formEntry.reference" type="text" />
            </div>
            <div class="form-group">
              <label>Pièce jointe</label>
              <input type="file" @change="onFileChange" />
              <span v-if="formEntry.piece_jointe"><a :href="formEntry.piece_jointe" target="_blank">📎 Voir</a></span>
            </div>
            <div class="form-group">
              <label>Commentaire</label>
              <textarea v-model="formEntry.commentaire"></textarea>
            </div>
            <div class="form-group">
              <label>Validateur</label>
              <input v-model="formEntry.utilisateur_validateur" type="text" />
            </div>
            <div class="form-group">
              <label>Date validation</label>
              <input v-model="formEntry.date_validation" type="datetime-local" />
            </div>
            <div class="form-group" style="grid-column:1/3;">
              <label>Détails</label>
              <textarea v-model="formEntry.details"></textarea>
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeAddModal">Annuler</button>
            <button type="submit" class="btn-primary">{{ editingEntry === null ? 'Ajouter' : 'Enregistrer' }}</button>
          </div>
        </form>
      </div>
    </div>
    <!-- Modale détail -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetails">
      <div class="modal-content view-modal">
        <div class="modal-header" style="display:flex;align-items:center;gap:1rem;">
          <h3 style="margin:0;flex:1;display:flex;align-items:center;gap:0.5rem;">
            <span style="font-size:1.3rem;">📋</span>
            Détail du Mouvement
          </h3>
          <button @click="closeDetails" class="modal-close">×</button>
        </div>
        <div class="modal-body" style="width:100%;">
          <div class="modal-section">
            <div class="section-title">
              <span style="font-size:1.1rem;">🧑‍💼</span>
              <span>Informations Générales</span>
            </div>
            <div class="section-content grid-2-cols">
              <div>
                <span class="details-label">Date :</span>
                <span class="details-value">{{ formatComptaDate(selectedEntry?.date) }}</span>
              </div>
              <div>
                <span class="details-label">Utilisateur :</span>
                <span class="details-value">{{ selectedEntry?.user }}</span>
              </div>
              <div>
                <span class="details-label">Type :</span>
                <span class="details-value">
                  <span class="type-badge" :class="getTypeClass(selectedEntry?.type)">{{ selectedEntry?.type }}</span>
                </span>
              </div>
              <div>
                <span class="details-label">Montant :</span>
                <span class="details-value">{{ formatCurrency(selectedEntry?.montant) }}</span>
              </div>
            </div>
          </div>
          <div class="modal-section">
            <div class="section-title">
              <span style="font-size:1.1rem;">📝</span>
              <span>Détails</span>
            </div>
            <div class="section-content">
              <div class="details-value details-value-long" style="background:#f8fafc;border-radius:8px;padding:1rem 1.2rem;border:1px solid #e2e8f0;">
                {{ selectedEntry?.details }}
              </div>
            </div>
          </div>
          <div class="modal-section">
            <div class="section-title">
              <span style="font-size:1.1rem;">🧾</span>
              <span>Champs avancés</span>
            </div>
            <div class="section-content grid-2-cols">
              <div><span class="details-label">Catégorie :</span> <span class="details-value">{{ selectedEntry?.categorie }}</span></div>
              <div><span class="details-label">Moyen paiement :</span> <span class="details-value">{{ selectedEntry?.moyen_paiement }}</span></div>
              <div><span class="details-label">Statut :</span> <span class="details-value">{{ selectedEntry?.statut }}</span></div>
              <div><span class="details-label">Référence :</span> <span class="details-value">{{ selectedEntry?.reference }}</span></div>
              <div><span class="details-label">Pièce jointe :</span> <span class="details-value"><a v-if="selectedEntry?.piece_jointe" :href="selectedEntry.piece_jointe" target="_blank">📎 Voir</a><span v-else>—</span></span></div>
              <div><span class="details-label">Commentaire :</span> <span class="details-value">{{ selectedEntry?.commentaire }}</span></div>
              <div><span class="details-label">Validateur :</span> <span class="details-value">{{ selectedEntry?.utilisateur_validateur }}</span></div>
              <div><span class="details-label">Date validation :</span> <span class="details-value">{{ formatComptaDate(selectedEntry?.date_validation) }}</span></div>
            </div>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn-primary" @click="closeDetails">Fermer</button>
        </div>
      </div>
    </div>
    <!-- Modale de confirmation de suppression -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="cancelDeleteEntry">
      <div class="modal-content user-modal" style="max-width:350px;min-width:0;height:auto;min-height:0;" @click.stop>
        <div class="modal-header" style="display:flex;align-items:center;gap:0.7rem;">
          <span style="font-size:2rem;color:#f59e0b;">⚠️</span>
          <h3 style="margin:0;flex:1;">Confirmer la suppression</h3>
          <button class="modal-close" @click="cancelDeleteEntry">×</button>
        </div>
        <div class="modal-body">
          <p>Voulez-vous vraiment supprimer ce mouvement&nbsp;?</p>
        </div>
        <div class="modal-actions">
          <button class="btn-secondary" @click="cancelDeleteEntry">Annuler</button>
          <button class="btn-primary btn-delete" @click="confirmDeleteEntry">Supprimer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { useCurrency } from '../composables/useCurrency.js'
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { useAuthStore } from '../stores/auth.js'
import { apiService } from '../composables/Api/apiService.js'
import StatCard from '../components/StatCard.vue'

const comptaEntries = ref([]);
const authStore = useAuthStore();
const { formatPrice: formatCurrency } = useCurrency();
const hasComptaAccess = computed(() => authStore.user?.acces_comptabilite === true || authStore.user?.acces_comptabilite === 1);

const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarType = ref('success')
function triggerSnackbar(message, type = 'success') {
  snackbarMessage.value = message
  snackbarType.value = type
  showSnackbar.value = true
  setTimeout(() => { showSnackbar.value = false }, 3000)
}

onMounted(async () => {
  if (!hasComptaAccess.value) return;
  loading.value = true;
  try {
    const response = await apiService.get(`/api_comptabilite.php?action=all&id_entreprise=${authStore.user?.id_entreprise}`);
    if (response.success && Array.isArray(response.data)) {
      comptaEntries.value = response.data;
    } else {
      error.value = response.message || 'Erreur lors du chargement.';
    }
  } catch (e) {
    error.value = e.message || 'Erreur lors du chargement.';
  } finally {
    loading.value = false;
  }
});

function openDetails(entry) {
  selectedEntry.value = entry;
  showDetailsModal.value = true;
}
function closeDetails() {
  showDetailsModal.value = false;
  selectedEntry.value = null;
}
function askEditEntry(entry) {
  entryToEdit.value = entry;
  editDetails.value = entry.details;
  showEditModal.value = true;
}
function confirmEditEntry() {
  if (!entryToEdit.value) return;
  loading.value = true;
  apiService.put(`/api_comptabilite.php?action=update&id=${entryToEdit.value.id_compta}`, { ...entryToEdit.value, details: editDetails.value })
    .then(res => {
      if (res.success) {
        entryToEdit.value.details = editDetails.value;
        triggerSnackbar('Mouvement modifié !', 'success');
      } else {
        triggerSnackbar('Erreur lors de la modification', 'error');
      }
    })
    .catch(() => triggerSnackbar('Erreur lors de la modification', 'error'))
    .finally(() => {
      loading.value = false;
      showEditModal.value = false;
      entryToEdit.value = null;
      editDetails.value = '';
    });
}
function cancelEditEntry() {
  showEditModal.value = false;
  entryToEdit.value = null;
  editDetails.value = '';
}
function askDeleteEntry(entry) {
  entryToDelete.value = entry;
  showDeleteModal.value = true;
}
function confirmDeleteEntry() {
  if (!entryToDelete.value) return;
  loading.value = true;
  apiService.delete(`/api_comptabilite.php?action=delete&id=${entryToDelete.value.id_compta}`)
    .then(res => {
      if (res.success) {
        const idx = comptaEntries.value.indexOf(entryToDelete.value);
        if (idx !== -1) comptaEntries.value.splice(idx, 1);
        triggerSnackbar('Mouvement supprimé !', 'success');
      } else {
        triggerSnackbar('Erreur lors de la suppression', 'error');
      }
    })
    .catch(() => triggerSnackbar('Erreur lors de la suppression', 'error'))
    .finally(() => {
      loading.value = false;
      showDeleteModal.value = false;
      entryToDelete.value = null;
    });
}
function cancelDeleteEntry() {
  showDeleteModal.value = false;
  entryToDelete.value = null;
}
async function addEntry(entry) {
  loading.value = true;
  try {
    const response = await apiService.post('/api_comptabilite.php?action=create', entry);
    if (response.success) {
      comptaEntries.value.unshift(entry);
      triggerSnackbar('Mouvement ajouté !', 'success');
    } else {
      triggerSnackbar('Erreur lors de l\'ajout', 'error');
    }
  } catch (e) {
    triggerSnackbar('Erreur lors de l\'ajout', 'error');
  } finally {
    loading.value = false;
  }
}

const loading = ref(false);
const error = ref(null);
const search = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const dateFromInput = ref(null);
const dateToInput = ref(null);
const dateRange = ref([null, null]);

watch(dateRange, ([from, to]) => {
  dateFrom.value = from ? from.toISOString().slice(0, 10) : '';
  dateTo.value = to ? to.toISOString().slice(0, 10) : '';
});

function openDateRangePicker() {
  dateFromInput.value && dateFromInput.value.click();
}
function onDateFromChange() {
  if (dateToInput.value) {
    setTimeout(() => dateToInput.value.click(), 100);
  }
}
function onDateToChange() {
  // rien à faire, la valeur est déjà liée
}
const dateRangeLabel = computed(() => {
  if (dateFrom.value && dateTo.value) {
    return `${dateFrom.value} au ${dateTo.value}`;
  } else if (dateFrom.value) {
    return `Depuis le ${dateFrom.value}`;
  } else if (dateTo.value) {
    return `Jusqu'au ${dateTo.value}`;
  } else {
    return 'Filtrer par date';
  }
});
const typeFilter = ref('');
const categorieFilter = ref('');
const statutFilter = ref('');
const moyenPaiementFilter = ref('');
const showDetailsModal = ref(false);
const selectedEntry = ref(null);
const showAddModal = ref(false);
const editingEntry = ref(null);
const formEntry = ref({
  date: '',
  type: '',
  montant: '',
  user: '',
  categorie: '',
  moyen_paiement: '',
  statut: 'en attente',
  reference: '',
  piece_jointe: '',
  commentaire: '',
  utilisateur_validateur: '',
  date_validation: '',
  details: '',
  id_entreprise: authStore.user?.id_entreprise || ''
});
// SUPPRIMER la modale de confirmation d'édition (showEditModal)
const entryToEdit = ref(null);
const editDetails = ref('');
const entryToDelete = ref(null);
const showDeleteModal = ref(false);

function openAddModal(entry = null) {
  if (entry) {
    editingEntry.value = entry;
    formEntry.value = { ...entry };
  } else {
    editingEntry.value = null;
    formEntry.value = {
      date: '',
      type: '',
      montant: '',
      user: '',
      categorie: '',
      moyen_paiement: '',
      statut: 'en attente',
      reference: '',
      piece_jointe: '',
      commentaire: '',
      utilisateur_validateur: '',
      date_validation: '',
      details: '',
      id_entreprise: authStore.user?.id_entreprise || ''
    };
    if ('id_compta' in formEntry.value) delete formEntry.value.id_compta;
  }
  showAddModal.value = true;
}
function closeAddModal() {
  showAddModal.value = false;
  editingEntry.value = null;
}

async function onFileChange(e) {
  const file = e.target.files[0];
  if (!file) return;
  try {
    const { uploadPhoto } = await import('../config/cloudinary');
    const result = await uploadPhoto(file);
    if (result.success && (result.data.secure_url || result.data.url)) {
      formEntry.value.piece_jointe = result.data.secure_url || result.data.url;
      triggerSnackbar('Pièce jointe ajoutée !', 'success');
    } else {
      triggerSnackbar('Erreur upload pièce jointe', 'error');
    }
  } catch (err) {
    triggerSnackbar('Erreur upload pièce jointe', 'error');
  }
}

async function submitAddEdit() {
  loading.value = true;
  formEntry.value.id_entreprise = authStore.user?.id_entreprise || '';
  const requiredFields = [
    'date', 'type', 'montant', 'user', 'details', 'id_entreprise'
  ];
  for (const field of requiredFields) {
    if (!formEntry.value[field] || (typeof formEntry.value[field] === 'string' && formEntry.value[field].trim() === '')) {
      triggerSnackbar(`Champ obligatoire manquant : ${field}`, 'error');
      loading.value = false;
      return;
    }
  }
  try {
    if (editingEntry.value) {
      if (!editingEntry.value.id_compta) {
        triggerSnackbar('Identifiant du mouvement manquant.', 'error');
        loading.value = false;
        return;
      }
      const response = await apiService.put(`/api_comptabilite.php?action=update&id=${editingEntry.value.id_compta}`, formEntry.value);
      if (response.success) {
        Object.assign(editingEntry.value, formEntry.value);
        triggerSnackbar('Mouvement modifié !', 'success');
        closeAddModal();
      } else {
        triggerSnackbar('Erreur lors de la modification', 'error');
      }
    } else {
      const response = await apiService.post('/api_comptabilite.php?action=create', formEntry.value);
      if (response.success) {
        comptaEntries.value.unshift({ ...formEntry.value });
        triggerSnackbar('Mouvement ajouté !', 'success');
        closeAddModal();
      } else {
        triggerSnackbar('Erreur lors de l\'ajout', 'error');
      }
    }
  } catch (e) {
    triggerSnackbar('Erreur lors de l\'enregistrement', 'error');
  } finally {
    loading.value = false;
  }
}

const filteredComptaEntries = computed(() => {
  let entries = comptaEntries.value;
  if (search.value) {
    const s = search.value.toLowerCase();
    entries = entries.filter(entry =>
      (entry.user && entry.user.toLowerCase().includes(s)) ||
      (entry.type && entry.type.toLowerCase().includes(s)) ||
      (entry.details && entry.details.toLowerCase().includes(s)) ||
      (entry.reference && entry.reference.toLowerCase().includes(s)) ||
      (entry.commentaire && entry.commentaire.toLowerCase().includes(s))
    );
  }
  if (typeFilter.value) {
    entries = entries.filter(entry => entry.type === typeFilter.value);
  }
  if (categorieFilter.value) {
    entries = entries.filter(entry => entry.categorie === categorieFilter.value);
  }
  if (statutFilter.value) {
    entries = entries.filter(entry => entry.statut === statutFilter.value);
  }
  if (moyenPaiementFilter.value) {
    entries = entries.filter(entry => entry.moyen_paiement === moyenPaiementFilter.value);
  }
  if (dateFrom.value) {
    entries = entries.filter(entry => entry.date && entry.date >= dateFrom.value);
  }
  if (dateTo.value) {
    entries = entries.filter(entry => entry.date && entry.date <= dateTo.value);
  }
  return entries;
});

const page = ref(1);
const pageSize = 10;
const pageCount = computed(() => Math.ceil(filteredComptaEntries.value.length / pageSize));
const paginatedComptaEntries = computed(() => {
  const start = (page.value - 1) * pageSize;
  return filteredComptaEntries.value.slice(start, start + pageSize);
});

const soldeActuel = computed(() => {
  let solde = 0;
  for (const entry of comptaEntries.value) {
    solde += entry.type === 'Entrée' ? entry.montant : -entry.montant;
  }
  return solde;
});
const totalEntrees = computed(() => {
  return comptaEntries.value.filter(e => e.type === 'Entrée').reduce((acc, e) => acc + e.montant, 0);
});
const totalSorties = computed(() => {
  return comptaEntries.value.filter(e => e.type === 'Sortie').reduce((acc, e) => acc + e.montant, 0);
});

function formatComptaDate(dateString) {
  if (!dateString) return '—';
  try {
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR', {
      day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
  } catch (e) {
    return dateString;
  }
}
function getTypeClass(type) {
  if (!type) return '';
  if (type === 'Entrée') return 'type-entree';
  if (type === 'Sortie') return 'type-sortie';
  return '';
}
function exportExcel() {
  const data = filteredComptaEntries.value.map(e => ({
    Date: formatComptaDate(e.date),
    Type: e.type,
    Montant: formatCurrency(e.montant),
    Utilisateur: e.user,
    Détails: e.details
  }));
  const ws = XLSX.utils.json_to_sheet(data);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Comptabilité');
  XLSX.writeFile(wb, 'comptabilite.xlsx');
}
function exportPDF() {
  const doc = new jsPDF();
  doc.setFillColor(26, 95, 74);
  doc.roundedRect(0, 0, 210, 30, 0, 0, 'F');
  doc.setFillColor(255,255,255);
  doc.circle(22, 15, 8, 'F');
  doc.setTextColor(26,95,74);
  doc.setFontSize(13);
  doc.setFont('helvetica', 'bold');
  doc.text('PS', 18, 18);
  doc.setTextColor(255,255,255);
  doc.setFontSize(15);
  doc.text('Comptabilité', 210-14, 18, { align: 'right' });
  doc.setFontSize(16);
  doc.setTextColor(26,95,74);
  doc.setFont('helvetica', 'bold');
  doc.text(`Mouvements comptables`, 105, 38, { align: 'center' });
  doc.setFillColor(240, 253, 244);
  doc.roundedRect(40, 44, 130, 12, 4, 4, 'F');
  doc.setFontSize(11);
  doc.setTextColor(26,95,74);
  doc.text(`Total mouvements : ${filteredComptaEntries.value.length}`, 105, 52, { align: 'center' });
  const rows = filteredComptaEntries.value.map(e => [formatComptaDate(e.date), e.type, formatCurrency(e.montant), e.user, e.details]);
  autoTable(doc, {
    head: [['Date', 'Type', 'Montant', 'Utilisateur', 'Détails']],
    body: rows,
    startY: 60,
    theme: 'striped',
    styles: { fontSize: 10, cellPadding: 2 },
    headStyles: { fillColor: [26, 95, 74], textColor: 255, fontStyle: 'bold' },
    alternateRowStyles: { fillColor: [240, 253, 244] },
    margin: { left: 14, right: 14 }
  });
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
  doc.save('comptabilite.pdf');
}
</script>

<style scoped>
 .main-table.compta-main-table tbody tr:nth-child(even) td {
  background: #f8fafc;
}
.main-table.compta-main-table tbody tr:nth-child(odd) td {
  background: #fff;
}   
.page-main.compta-page {
  background: #e0e7ef;
  min-height: 100vh;
  padding: 2.5rem 0 0 0;
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.page-card.compta-card {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 4px 24px 0 rgba(26,95,74,0.08);
  padding: 2.2rem 2.5rem 1.5rem 2.5rem;
  width: 100%;
  max-width: none;
  min-height: 0;
  margin: 0 auto 2.5rem auto;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
  flex: 1 1 0%;
}
.page-header.compta-header-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.7rem;
  gap: 1.5rem;
}
.compta-header-actions {
  width: 100%;
}
.compta-add-btn {
  margin-left: auto;
}
.page-title.compta-title {
  margin: 0;
  font-size: 1.45rem;
  font-weight: 700;
  color: #1a202c;
}
.compta-statcards-row {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 0.7rem;
  justify-content: center;
}
.compta-filter-box {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.5rem;
  align-items: center;
  justify-content: flex-start;
  background: none;
  box-shadow: none;
  border-radius: 0;
  padding: 0;
  flex-wrap: wrap;
}
.filter-input {
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
  background: #fff;
}
/* Correction affichage datepicker */
.datepicker-wrapper {
  min-width: 180px;
}
.compta-add-btn {
  margin-left: auto;
  padding: 0.85rem 1.7rem;
  border: none;
  background: #1a5f4a;
  color: #fff;
  border-radius: 8px;
  font-size: 1.08rem;
  font-weight: 700;
  box-shadow: 0 2px 8px 0 rgba(26,95,74,0.08);
  cursor: pointer;
  transition: background 0.18s, box-shadow 0.18s;
  margin-top: 0.2rem;
}
.compta-add-btn:hover {
  background: #145040;
  box-shadow: 0 4px 16px 0 rgba(26,95,74,0.13);
}
.datepicker-input {
  width: 100%;
  min-width: 120px;
  max-width: 240px;
  background: #fff;
  border: 1.5px solid #10b981;
  border-radius: 8px !important;
  font-size: 1rem;
  padding: 0.75rem;
  height: 44px;
  box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s;
  margin: 0;
  outline: none;
}
.datepicker-input:focus {
  border-color: #10b981;
  box-shadow: 0 0 0 2px #bbf7d0;
  border-radius: 8px !important;
}
.datepicker-input {
  width: 200px !important;
  min-width: 160px;
  max-width: 240px;
  background: #fff;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
  padding: 0.75rem;
  transition: border-color 0.2s;
  box-sizing: border-box;
}
.datepicker-input:focus {
  border-color: #059669;
  outline: none;
}
@media (max-width: 900px) {
  .datepicker-wrapper {
    min-width: 120px;
    max-width: 100%;
  }
  .datepicker-input {
    min-width: 100px;
    font-size: 0.95rem;
    padding: 0.6rem;
  }
}
.compta-add-btn {
  padding: 0.5rem 1rem;
  border: 1.5px solid #10b981;
  background: #1a5f4a;
  color: #fff;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-primary.btn-excel {
  padding: 0.5rem 1rem;
  border: 1.5px solid #3b82f6;
  background: #3b82f6;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-primary.btn-excel:hover {
  background: #2563eb;
  border-color: #2563eb;
}
.btn-primary.btn-pdf {
  padding: 0.5rem 1rem;
  border: 1.5px solid #dc2626;
  background: #dc2626;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-primary.btn-pdf:hover {
  background: #b91c1c;
  border-color: #b91c1c;
}
.table-container.compta-table-container {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px 0 rgba(26,95,74,0.08);
  padding: 0.5rem 1.2rem 1.2rem 1.2rem;
  margin-top: 1.2rem;
}
.main-table.compta-main-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #fff;
  table-layout: auto;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: none;
}
.main-table.compta-main-table thead tr {
  background: #f8fafc;
}
.main-table.compta-main-table th {
  text-transform: uppercase;
  font-size: 0.98rem;
  font-weight: 700;
  color: #334155;
  letter-spacing: 0.02em;
  padding: 1.1rem 0.7rem 1.1rem 0.7rem;
  border-bottom: 1.5px solid #e5e7eb;
  background: #f8fafc;
  text-align: left;
}
.main-table.compta-main-table td {
  padding: 0.85rem 0.7rem;
  font-size: 1rem;
  color: #1f2937;
  background: #fff;
  border-bottom: 1px solid #f1f5f9;
}
.main-table.compta-main-table tr:last-child td {
  border-bottom: none;
}
.empty-state {
  padding: 2.5rem 1rem !important;
  text-align: center;
  color: #94a3b8;
  font-size: 0.98rem;
  white-space: normal !important;
}
.error-state {
  color: #dc2626;
}
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin: 1.5rem 0;
}
.pagination button {
  background: #1a5f4a;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s;
}
.pagination button:disabled {
  background: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
}
/* Styles spécifiques pour les modales de la page Comptabilite */
/* Les styles de base (.modal-overlay, .modal-content, etc.) sont définis dans style.css */

/* Modale de suppression harmonisée */
.modal-content.user-modal {
  max-width: 380px;
  padding: 2rem 2.5rem 1.5rem 2.5rem;
}

.modal-content.user-modal .modal-header h3 {
  font-size: 1.2rem;
  color: #dc2626;
}

.modal-body p {
  font-size: 1.05rem;
  margin: 0.5rem 0 0.2rem 0;
  color: #1a202c;
  text-align: left;
}
.btn-secondary {
  background: #e5e7eb;
  color: #1a2a2a;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  padding: 0.6em 1.3em;
  cursor: pointer;
  transition: background 0.18s;
}
.btn-secondary:hover {
  background: #f3f4f6;
}
.btn-primary.btn-delete {
  background: #dc2626;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  padding: 0.6em 1.3em;
  cursor: pointer;
  transition: background 0.18s;
}
.btn-primary.btn-delete:hover {
  background: #b91c1c;
}
.modal-content h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 1rem 0;
}
.modal-section {
  margin-bottom: 1.2rem;
  width: 100%;
}
.section-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: #1a5f4a;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.7rem;
}
.section-content {
  width: 100%;
}
.grid-2-cols {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.2rem 2.5rem;
}
.details-label {
  min-width: 110px;
  color: #64748b;
  font-weight: 600;
  font-size: 1rem;
}
.details-value {
  color: #1a202c;
  font-size: 1rem;
  word-break: break-word;
  flex: 1;
}
.details-value-long {
  white-space: pre-line;
}
.btn-primary {
  background-color: #1a5f4a;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s;
  width: 100%;
  max-width: 120px;
  align-self: flex-end;
}
.btn-excel {
  background: #2563eb !important;
  color: #fff !important;
}
.btn-pdf {
  background: #dc2626 !important;
  color: #fff !important;
}
.snackbar {
  position: fixed;
  bottom: 1rem;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(26, 95, 74, 0.9);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: opacity 0.3s;
  z-index: 4000;
}
.snackbar.success {
  background: rgba(52, 211, 153, 0.9);
}
.snackbar.error {
  background: rgba(239, 68, 68, 0.9);
}
@keyframes modalIn {
  from { opacity: 0; transform: translateY(40px) scale(0.98); }
  to { opacity: 1; transform: none; }
}
@media (max-width: 1200px) {
  .page-card.compta-card {
    padding: 1.2rem 0.5rem;
  }
}
@media (max-width: 768px) {
  .page-card.compta-card {
    padding: 1rem 0.2rem;
    border-radius: 14px;
  }
  .compta-title { font-size: 1.15rem; }
  .compta-table th, .compta-table td { padding: 0.5rem 0.625rem; font-size: 0.75rem; }
  .compta-statcards-row { flex-direction: column; gap: 0.7rem; }
}
.date-range-filter {
  min-width: 180px;
  display: flex;
  align-items: center;
}
</style>
