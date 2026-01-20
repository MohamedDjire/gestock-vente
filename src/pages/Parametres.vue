<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth.js'
import apiEntrepot from '../composables/Api/api_entrepot.js'
import apiPointVente from '../composables/Api/api_point_vente.js'

// --- Entrepôts et points de vente ---
const entrepots = ref([])
const pointsVente = ref([])
onMounted(async () => {
  try {
    const entrepotList = await apiEntrepot.getAll()
    entrepots.value = Array.isArray(entrepotList) ? entrepotList : []
  } catch {}
  try {
    const pvList = await apiPointVente.getAll()
    pointsVente.value = Array.isArray(pvList) ? pvList : []
  } catch {}
})

const section = ref('taxes')
// --- Taxes demo ---
const taxes = ref([
  { id: 1, nom: 'TVA', taux: 18 },
])
const showTaxModal = ref(false)
const editingTax = ref(null)
const taxForm = ref({ nom: '', taux: '' })
function openAddTaxModal() {
  editingTax.value = null
  taxForm.value = { nom: '', taux: '' }
  showTaxModal.value = true
}
function openEditTaxModal(tax) {
  editingTax.value = tax
  taxForm.value = { nom: tax.nom, taux: tax.taux }
}
function closeTaxModal() {
  showTaxModal.value = false
}
function saveTax() {
  if (editingTax.value) {
    // Edition
    editingTax.value.nom = taxForm.value.nom
    editingTax.value.taux = taxForm.value.taux
  } else {
    // Ajout
    taxes.value.push({
      id: Date.now(),
      nom: taxForm.value.nom,
      taux: taxForm.value.taux
    })
  }
  closeTaxModal()
}
function deleteTax(tax) {
  taxes.value = taxes.value.filter(t => t.id !== tax.id)
}
const uploadingLogo = ref(false)
const logoError = ref('')
async function onLogoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  uploadingLogo.value = true
  logoError.value = ''
  try {
    const result = await uploadPhoto(file)
    if (result.success && (result.data.url || result.data.secure_url)) {
      entreprise.value.logo = result.data.secure_url || result.data.url
    } else {
      logoError.value = result.message || "Erreur lors de l'upload."
    }
  } catch (err) {
    logoError.value = err.message
  } finally {
    uploadingLogo.value = false
  }
}
async function saveEntreprise() {
  if (!entrepriseId.value) {
    entrepriseError.value = "ID d'entreprise introuvable (initialisez l'entreprise avant de sauvegarder)"
    return
  }
  loadingEntreprise.value = true
  entrepriseError.value = ''
  try {
    const payload = {
      nom_entreprise: entreprise.value.nom,
      adresse: entreprise.value.adresse,
      devise: entreprise.value.devise,
      sigle: entreprise.value.sigle,
      num: entreprise.value.num,
      ncc: entreprise.value.ncc,
      num_banque: entreprise.value.num_banque,
      email: entreprise.value.email,
      telephone: entreprise.value.telephone,
      site_web: entreprise.value.site_web,
      logo: entreprise.value.logo
    }
    await apiEntreprise.updateEntreprise(entrepriseId.value, payload)
    alert('Paramètres enregistrés !')
  } catch (e) {
    entrepriseError.value = e.message || 'Erreur lors de la sauvegarde'
  } finally {
    loadingEntreprise.value = false
  }
}

// TODO: Charger les infos de l'entreprise au montage si besoin
function parseAccess(val) {
  // Toujours retourner un tableau d'ID purs (number ou string)
  if (Array.isArray(val)) {
    return val.map(item => {
      if (typeof item === 'object' && item !== null) {
        return item.id ?? item.value ?? null;
      }
      return item;
    }).filter(id => (typeof id === 'number' || typeof id === 'string') && id !== null && id !== undefined);
  }
  if (typeof val === 'string') {
    try {
      const arr = JSON.parse(val);
      if (Array.isArray(arr)) {
        return arr.filter(id => typeof id === 'number' || typeof id === 'string');
      }
    } catch {}
  }
  return [];
}

const authStore = useAuthStore()
// DEBUG : afficher le rôle utilisateur
console.log('Rôle utilisateur courant :', authStore.userRole, authStore.user?.role)
const isAdmin = computed(() => {
  const role = (authStore.userRole || authStore.user?.role || '').toLowerCase();
  return role === 'admin' || role === 'superadmin';
})
</script>
<template>
  <div v-if="isAdmin" class="settings-page">
    <aside class="settings-sidebar">
      <ul>
        <!-- <li :class="{active: section==='utilisateurs'}" @click="section='utilisateurs'">Utilisateurs</li> -->
        <li :class="{active: section==='taxes'}" @click="section='taxes'">Taxes</li>
        <li :class="{active: section==='categories'}" @click="section='categories'">Catégories produits</li>
        <li :class="{active: section==='securite'}" @click="section='securite'">Sécurité & Audit</li>
        <li :class="{active: section==='notifications'}" @click="section='notifications'">Notifications</li>
        
      </ul>
    </aside>
    <main class="settings-content">
      <div class="settings-main-panel">
        <!-- Section Entreprise déplacée dans Gestion du compte > Onglet Entreprise -->
        <template v-if="section==='taxes'">
          <h2>Gestion des taxes</h2>
          <button class="btn-primary" style="margin-bottom:1rem;" @click="openAddTaxModal">Ajouter une taxe</button>
          <div class="table-container">
            <table class="users-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Taux (%)</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tax in taxes" :key="tax.id">
                  <td>{{ tax.nom }}</td>
                  <td>{{ tax.taux }}</td>
                  <td class="actions-column">
                    <button class="btn-icon" title="Modifier" @click="openEditTaxModal(tax)">✏️</button>
                    <button class="btn-icon btn-danger" title="Supprimer" @click="deleteTax(tax)">🗑️</button>
                  </td>
                </tr>
                <tr v-if="taxes.length === 0">
                  <td colspan="3">Aucune taxe enregistrée</td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Modale harmonisée pour ajout/édition taxe -->
          <div v-if="showTaxModal" class="modal-overlay" @click.self="closeTaxModal">
            <div class="modal-content user-modal" @click.stop>
              <div class="modal-header">
                <h3>{{ editingTax ? 'Modifier la taxe' : 'Ajouter une taxe' }}</h3>
                <button @click="closeTaxModal" class="modal-close">×</button>
              </div>
              <div class="modal-body">
                <form @submit.prevent="saveTax">
                  <div class="form-group">
                    <label>Nom de la taxe</label>
                    <input v-model="taxForm.nom" required placeholder="Ex: TVA" />
                  </div>
                  <div class="form-group">
                    <label>Taux (%)</label>
                    <input v-model="taxForm.taux" type="number" min="0" max="100" required placeholder="Ex: 18" />
                  </div>
                  <div class="modal-actions">
                    <button type="button" @click="closeTaxModal" class="btn-cancel">Annuler</button>
                    <button type="submit" class="btn-save">Valider</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </template>
        <template v-else-if="section==='categories'">
          <h2>Gestion des catégories de produits</h2>
          <button class="btn-primary" style="margin-bottom:1rem;">Ajouter une catégorie</button>
          <div class="table-container">
            <table class="users-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Alimentaire</td>
                  <td class="actions-column">
                    <button class="btn-icon" title="Modifier">✏️</button>
                    <button class="btn-icon btn-danger" title="Supprimer">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
        <template v-else-if="section==='facturation'">
          <h2>Paramètres de facturation</h2>
          <form class="settings-form">
            <div class="form-group">
              <label>Numéro de départ des factures</label>
              <input type="number" placeholder="Ex: 1000" />
            </div>
            <div class="form-group">
              <label>Préfixe facture</label>
              <input type="text" placeholder="Ex: FAC-" />
            </div>
            <button class="btn-primary">Enregistrer</button>
          </form>
        </template>
        <template v-else-if="section==='notifications'">
          <h2>Notifications</h2>
          <p>Configurer les notifications par email, SMS, etc.</p>
          <button class="btn-primary">Configurer</button>
        </template>
        <template v-else-if="section==='sauvegarde'">
          <h2>Sauvegarde & restauration</h2>
          <button class="btn-primary">Sauvegarder maintenant</button>
          <button class="btn-secondary">Restaurer une sauvegarde</button>
        </template>
        <template v-else-if="section==='entrepots'">
          <h2>Liste des entrepôts</h2>
          <div class="table-container">
            <table class="users-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nom</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entrepot in entrepots" :key="entrepot.id_entrepot">
                  <td>{{ entrepot.id_entrepot }}</td>
                  <td>{{ entrepot.nom_entrepot }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
        <template v-else-if="section==='pointsVente'">
          <h2>Liste des points de vente</h2>
          <div class="table-container">
            <table class="users-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nom</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pv in pointsVente" :key="pv.id_point_vente">
                  <td>{{ pv.id_point_vente }}</td>
                  <td>{{ pv.nom_point_vente }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
        <template v-else>
          <h2>Section à venir</h2>
          <p>Cette section sera bientôt disponible.</p>
        </template>
      </div>
    </main>
  </div>
  <div v-else class="parametres-acces-refuse">
    <div class="acces-refuse-card">
      <span class="acces-refuse-icon">🔒</span>
      <h2>Accès réservé à l'administrateur</h2>
      <p>Les paramètres (entreprise, taxes, catégories, etc.) ne sont accessibles qu'aux comptes administrateur. Contactez votre administrateur pour toute modification.</p>
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
/* Tableau utilisateurs pro */
      .table-container {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: auto;
        margin-bottom: 2rem;
      }
      .users-table {
        width: 100%;
        border-collapse: collapse;
      }
      .users-table th, .users-table td {
        padding: 0.75rem 0.875rem;
        border-bottom: 1px solid #f1f5f9;
        text-align: left;
      }
      .users-table th {
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
      /* Modale harmonisée */
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
        min-width: 380px;
        width: 420px;
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
.settings-page {
  display: flex;
  min-height: 80vh;
  background: #f8fafc;
}
.settings-sidebar {
  width: 220px;
  background: #fff;
  border-right: 1px solid #e5e7eb;
  padding: 2rem 0.5rem;
}
.settings-sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.settings-sidebar li {
  padding: 0.75rem 1.25rem;
  cursor: pointer;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #334155;
  transition: background 0.2s, color 0.2s;
}
.settings-sidebar li.active, .settings-sidebar li:hover {
  background: #1a5f4a;
  color: #fff;
}
.settings-content {
  flex: 1;
  padding: 2.5rem 2rem;
}
.settings-form {
  max-width: 420px;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.form-group label {
  font-weight: 600;
  color: #1a5f4a;
}
.form-group input[type="text"], .form-group input[type="file"] {
  padding: 0.6rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  background: #fff;
}
.btn-primary {
  background: #1a5f4a;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.6rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: 1rem;
  transition: background 0.2s;
}
.btn-primary:hover {
  background: #134e3a;
}
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content.user-modal {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 6px 24px 0 rgba(26,95,74,0.12);
  padding: 2rem 2.5rem 1.5rem 2.5rem;
  min-width: 350px;
  max-width: 600px;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}
.modal-close {
  background: none;
  border: none;
  font-size: 1.7rem;
  color: #888;
  cursor: pointer;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.2rem;
}
.btn-cancel, .btn-save {
  padding: 0.6rem 1.2rem;
  border-radius: 7px;
  border: none;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-cancel {
  background: #e5e7eb;
  color: #1a2a2a;
}
.btn-save {
  background: #218c6a;
  color: #fff;
}

/* Harmonisation des champs utilisateur avec ceux de la modale produit */
input, select {
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
  font-size: 1.1rem;
  padding: 0.7rem 1rem;
  border: 1.5px solid #d1d5db;
  border-radius: 10px;
  background: #f9fafb;
  transition: border-color 0.2s;
}
input:focus, select:focus {
  border-color: #218c6a;
  outline: none;
}
.form-group {
  max-width: 360px;
}

/* Harmonisation de la disposition des champs comme dans la modale produit */
.user-form {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}
.form-row {
  display: flex;
  gap: 1.2rem;
  flex-wrap: nowrap;
  justify-content: space-between;
}
.form-group {
  flex: 1 1 0%;
  min-width: 0;
  max-width: 360px;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.entreprise-card {
  max-width: 480px;
  margin: 2rem auto 0 auto;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  padding: 2rem 2.5rem 1.5rem 2.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}
.entreprise-card-pro {
  max-width: 520px;
  margin: 2.5rem auto 0 auto;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 6px 32px rgba(26,95,74,0.10);
  padding: 2.5rem 2.5rem 2rem 2.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.7rem;
  border: 1.5px solid #e5e7eb;
}
.entreprise-logo-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  margin-bottom: 1.2rem;
}
.entreprise-logo {
  max-width: 120px;
  max-height: 80px;
  border-radius: 10px;
  box-shadow: 0 2px 12px #e5e7eb;
  background: #f8fafc;
  object-fit: contain;
}
.entreprise-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
  margin-bottom: 1.2rem;
  text-align: center;
  letter-spacing: 0.01em;
}
.entreprise-section {
  width: 100%;
  margin-bottom: 1.2rem;
  background: #f9fafb;
  border-radius: 10px;
  padding: 1.1rem 1.2rem 0.7rem 1.2rem;
  box-shadow: 0 1px 6px #f3f4f6;
}
.entreprise-section-title {
  font-size: 1.08rem;
  font-weight: 700;
  color: #218c6a;
  margin-bottom: 0.7rem;
  margin-top: 0;
  letter-spacing: 0.01em;
}
.entreprise-field {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 0.35rem 0;
  border-bottom: 1px solid #f2f2f2;
}
.entreprise-field:last-child {
  border-bottom: none;
}
.entreprise-label {
  font-weight: 600;
  color: #1a5f4a;
  min-width: 160px;
  font-size: 1.04rem;
}
.entreprise-value {
  color: #222;
  word-break: break-word;
  text-align: right;
  flex: 1;
  font-size: 1.04rem;
}
@media (max-width: 600px) {
  .entreprise-card-pro {
    padding: 1.2rem 0.5rem 1rem 0.5rem;
  }
  .entreprise-label, .entreprise-value {
    font-size: 0.98rem;
  }
}
</style>
