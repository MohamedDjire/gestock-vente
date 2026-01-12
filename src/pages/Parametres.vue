<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useAuthStore } from '../stores/auth.js'
import apiEntreprise from '../composables/api/apiEntreprise.js'
import apiUtilisateur from '../composables/api/apiUtilisateur.js'
import apiEntrepot from '../composables/api/api_entrepot.js'
import apiPointVente from '../composables/api/api_point_vente.js'
import { logJournal } from '../composables/useJournal'
import AccessSelector from '../components/AccessSelector.vue'

const section = ref('entreprise')
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
  showTaxModal.value = true
}
function closeTaxModal() {
  showTaxModal.value = false
  editingTax.value = null
}
function saveTax() {
  if (!taxForm.value.nom || !taxForm.value.taux) return
  if (editingTax.value) {
    editingTax.value.nom = taxForm.value.nom
    editingTax.value.taux = taxForm.value.taux
  } else {
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
const users = ref([])
const userError = ref('')
const showAddUser = ref(false)
const editingUser = ref(null)
const userForm = ref({
  prenom: '',
  nom: '',
  email: '',
  telephone: '',
  role: 'utilisateur',
  username: '',
  mot_de_passe: '',
  permissions_entrepots: [],
  permissions_points_vente: [],
  acces_comptabilite: false
})
const userFormError = ref('')
const showDeleteUserModal = ref(false)
const userToDelete = ref(null)

const entrepots = ref([])
const pointsVente = ref([])
const entreprise = ref({ nom: '', adresse: '', devise: '' })
const loadingEntreprise = ref(false)
const entrepriseError = ref('')
const authStore = useAuthStore()
const entrepriseId = computed(() => authStore.enterpriseId)
const currentUser = computed(() => authStore.user)

const entrepotsVisibles = computed(() => {
  if (!currentUser.value || !Array.isArray(currentUser.value.permissions_entrepots)) return entrepots.value
  return entrepots.value.filter(e => currentUser.value.permissions_entrepots.includes(e.id_entrepot))
})
const pointsVenteVisibles = computed(() => {
  if (!currentUser.value || !Array.isArray(currentUser.value.permissions_points_vente)) return pointsVente.value
  return pointsVente.value.filter(pv => currentUser.value.permissions_points_vente.includes(pv.id_point_vente))
})

watch(section, (val) => {
  if (val === 'utilisateurs') fetchUsers()
})

onMounted(() => {
  fetchEntrepots()
  fetchPointsVente()
  if (section.value === 'utilisateurs') fetchUsers()
  fetchEntreprise()
})

const loadingUsers = ref(false)

async function fetchUsers() {
  userError.value = ''
  loadingUsers.value = true
  try {
    const res = await apiUtilisateur.getAll()
    users.value = Array.isArray(res) ? res : []
  } catch (e) {
    userError.value = e.message || 'Erreur lors du chargement des utilisateurs'
    users.value = []
  }
  loadingUsers.value = false
}
function closeUserModal() {
  showAddUser.value = false
  editingUser.value = null
  userForm.value = {
    prenom: '',
    nom: '',
    email: '',
    telephone: '',
    role: 'utilisateur',
    username: '',
    mot_de_passe: '',
    permissions_entrepots: [],
    permissions_points_vente: [],
    acces_comptabilite: false
  }
  userFormError.value = ''
}
async function fetchEntrepots() {
  try {
    const res = await apiEntrepot.getAll()
    entrepots.value = Array.isArray(res.data) ? res.data : []
  } catch {}
}
async function fetchPointsVente() {
  try {
    const res = await apiPointVente.getAll()
    pointsVente.value = Array.isArray(res) ? res : []
  } catch {}
}
async function fetchEntreprise() {
  if (!entrepriseId.value) {
    entrepriseError.value = "ID d'entreprise introuvable"
    return
  }
  loadingEntreprise.value = true
  entrepriseError.value = ''
  try {
    const data = await apiEntreprise.getEntreprise(entrepriseId.value)
    if (data && data.nom_entreprise) {
      entreprise.value = {
        nom: data.nom_entreprise,
        adresse: data.adresse,
        devise: data.devise
      }
    }
  } catch (e) {
    entrepriseError.value = e.message || 'Erreur lors du chargement'
  } finally {
    loadingEntreprise.value = false
  }
}
function onLogoChange(e) {
  // Gestion du logo (à compléter)
}
async function saveEntreprise() {
  if (!entrepriseId.value) {
    entrepriseError.value = "ID d'entreprise introuvable"
    return
  }
  loadingEntreprise.value = true
  entrepriseError.value = ''
  try {
    const payload = {
      nom_entreprise: entreprise.value.nom,
      adresse: entreprise.value.adresse,
      devise: entreprise.value.devise
    }
    await apiEntreprise.updateEntreprise(entrepriseId.value, payload)
    alert('Paramètres enregistrés !')
  } catch (e) {
    entrepriseError.value = e.message || 'Erreur lors de la sauvegarde'
  } finally {
    loadingEntreprise.value = false
  }
}
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

function editUser(user) {
  editingUser.value = { ...user }
  showAddUser.value = true
  userForm.value = {
    prenom: user.prenom,
    nom: user.nom,
    email: user.email,
    telephone: user.telephone,
    role: user.role,
    username: user.username,
    mot_de_passe: '',
    permissions_entrepots: parseAccess(user.permissions_entrepots),
    permissions_points_vente: parseAccess(user.permissions_points_vente),
    acces_comptabilite: !!user.acces_comptabilite
  }
}
function openDeleteUserModal(user) {
  userToDelete.value = user
  showDeleteUserModal.value = true
}
function closeDeleteUserModal() {
  showDeleteUserModal.value = false
  userToDelete.value = null
}
async function confirmDeleteUser() {
  if (!userToDelete.value) return
  try {
    await apiUtilisateur.delete(userToDelete.value.id_utilisateur)
    logJournal({ user: getJournalUser(), action: 'Suppression utilisateur', details: `Utilisateur ${userToDelete.value.prenom} ${userToDelete.value.nom} supprimé.` })
    await fetchUsers()
    closeDeleteUserModal()
  } catch (e) {
    userError.value = e.message || 'Erreur lors de la suppression'
  }
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
async function submitUserForm() {
  userFormError.value = ''
  try {
    const payload = {
      prenom: userForm.value.prenom,
      nom: userForm.value.nom,
      email: userForm.value.email,
      telephone: userForm.value.telephone,
      role: userForm.value.role,
      username: userForm.value.username,
      permissions_entrepots: userForm.value.permissions_entrepots,
      permissions_points_vente: userForm.value.permissions_points_vente,
      acces_comptabilite: userForm.value.acces_comptabilite,
      id_entreprise: entrepriseId.value
    }
    // N'envoyer le mot de passe que s'il est renseigné lors de la modification
    if (!editingUser.value || (userForm.value.mot_de_passe && userForm.value.mot_de_passe.length > 0)) {
      payload.mot_de_passe = userForm.value.mot_de_passe
    }
    if (editingUser.value) {
      await apiUtilisateur.update(editingUser.value.id_utilisateur, payload)
    } else {
      await apiUtilisateur.create(payload)
    }
    await fetchUsers()
    closeUserModal()
  } catch (e) {
    userFormError.value = e.message || 'Erreur lors de la sauvegarde de l\'utilisateur'
  }
}
</script>
<template>
  <div class="settings-page">
    <aside class="settings-sidebar">
      <ul>
        <li :class="{active: section==='entreprise'}" @click="section='entreprise'">Entreprise</li>
        <li :class="{active: section==='utilisateurs'}" @click="section='utilisateurs'">Utilisateurs</li>
        <li :class="{active: section==='taxes'}" @click="section='taxes'">Taxes</li>
        <li :class="{active: section==='categories'}" @click="section='categories'">Catégories produits</li>
        <li :class="{active: section==='notifications'}" @click="section='notifications'">Notifications</li>
        <li :class="{active: section==='sauvegarde'}" @click="section='sauvegarde'">Sauvegarde</li>
      </ul>
    </aside>
    <main class="settings-content">
      <div class="settings-main-panel">
        <template v-if="section==='entreprise'">
          <h2>Paramètres de l'entreprise</h2>
          <form class="settings-form">
            <div class="form-group">
              <label>Nom de l'entreprise</label>
              <input type="text" v-model="entreprise.nom" placeholder="Nom de l'entreprise" />
            </div>
            <div class="form-group">
              <label>Logo</label>
              <input type="file" @change="onLogoChange" />
            </div>
            <div class="form-group">
              <label>Adresse</label>
              <input type="text" v-model="entreprise.adresse" placeholder="Adresse" />
            </div>
            <div class="form-group">
              <label>Devise</label>
              <input type="text" v-model="entreprise.devise" placeholder="Devise (ex: XOF, EUR)" />
            </div>
            <button class="btn-primary" @click.prevent="saveEntreprise">Enregistrer</button>
          </form>
        </template>
        <template v-else-if="section==='utilisateurs'">
          <h2>Gestion des utilisateurs</h2>
          <button class="btn-primary" style="margin-bottom:1rem;" @click="showAddUser = true">Ajouter un utilisateur</button>
          <div v-if="loadingUsers" class="form-error">Chargement des utilisateurs...</div>
          <div v-if="userError" class="form-error">{{ userError }}</div>
          <div v-if="!loadingUsers && users.length === 0 && !userError" class="form-error">Aucun utilisateur trouvé.</div>
          <div v-if="!loadingUsers && users.length > 0" class="table-container">
            <table class="users-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Email</th>
                  <th>Rôle</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id_utilisateur">
                  <td>{{ user.prenom }} {{ user.nom }}</td>
                  <td>{{ user.email }}</td>
                  <td>{{ user.role }}</td>
                  <td class="actions-column">
                    <button @click="editUser(user)" class="btn-icon" title="Modifier" aria-label="Modifier">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button @click="openDeleteUserModal(user)" class="btn-icon btn-danger" title="Supprimer" aria-label="Supprimer">
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
          <!-- Modal ajout/modif utilisateur -->
          <!-- Modal suppression utilisateur harmonisée -->
          <div v-if="showDeleteUserModal" class="modal-overlay" @click.self="closeDeleteUserModal">
            <div class="modal-content" @click.stop>
              <div class="modal-header" style="display:flex;align-items:center;gap:0.7rem;">
                <span style="font-size:2rem;color:#f59e0b;">⚠️</span>
                <h3 style="margin:0;flex:1;">Confirmer la suppression</h3>
                <button @click="closeDeleteUserModal" class="modal-close">×</button>
              </div>
              <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <b>{{ userToDelete?.prenom }} {{ userToDelete?.nom }}</b> ?</p>
                <p style="color:#dc2626;font-weight:600;">Cette action est irréversible.</p>
              </div>
              <div class="modal-actions">
                <button @click="closeDeleteUserModal" class="btn-cancel">Annuler</button>
                <button @click="confirmDeleteUser" class="btn-save" style="background:#dc2626;">Supprimer</button>
              </div>
            </div>
          </div>
          <!-- Modal ajout/modif utilisateur harmonisée -->
          <div v-if="showAddUser || editingUser" class="modal-overlay" @click.self="closeUserModal">
            <div class="modal-content user-modal" @click.stop>
              <div class="modal-header">
                <h3>{{ editingUser ? 'Modifier' : 'Ajouter' }} un utilisateur</h3>
                <button @click="closeUserModal" class="modal-close">×</button>
              </div>
              <div class="modal-body">
                <form @submit.prevent="submitUserForm" class="user-form">
                  <div v-if="userFormError" class="form-error">{{ userFormError }}</div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>Prénom</label>
                      <input v-model="userForm.prenom" required />
                    </div>
                    <div class="form-group">
                      <label>Nom</label>
                      <input v-model="userForm.nom" required />
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>Email</label>
                      <input v-model="userForm.email" type="email" required />
                    </div>
                    <div class="form-group">
                      <label>Téléphone</label>
                      <input v-model="userForm.telephone" type="tel" />
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>Rôle</label>
                      <select v-model="userForm.role" required>
                        <option value="admin">Administrateur</option>
                        <option value="utilisateur">Utilisateur</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Nom d'utilisateur</label>
                      <input v-model="userForm.username" required />
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>Mot de passe</label>
                      <input v-model="userForm.mot_de_passe" type="password" required />
                    </div>
                  </div>
                  <div class="form-section">
                    <h4 class="section-title">Accès & Permissions</h4>
                    <div>
                      <div class="access-block">
                        <label class="access-label">Sélectionner les entrepôts :</label>
                        <AccessSelector
                          :items="Array.isArray(entrepots) ? entrepots.map(e => ({id: e.id_entrepot, nom: e.nom_entrepot})) : []"
                          v-model="userForm.permissions_entrepots"
                          label="entrepôts"
                        />
                        <div v-if="entrepots.length === 0" class="form-error">Aucun entrepôt disponible.</div>
                      </div>
                      <div class="access-block">
                        <label class="access-label">Sélectionner les points de vente :</label>
                        <AccessSelector
                          :items="Array.isArray(pointsVente) ? pointsVente.map(pv => ({id: pv.id_point_vente, nom: pv.nom_point_vente})) : []"
                          v-model="userForm.permissions_points_vente"
                          label="points de vente"
                        />
                        <div v-if="pointsVente.length === 0" class="form-error">Aucun point de vente disponible.</div>
                      </div>
                    </div>
                    <div class="form-group">
                      <label>
                        <input type="checkbox" v-model="userForm.acces_comptabilite" />
                        Accès à la comptabilité
                      </label>
                    </div>
                  </div>
                  <div class="modal-actions">
                    <button type="button" @click="closeUserModal" class="btn-cancel">Annuler</button>
                    <button type="submit" class="btn-save" style="margin-left: 0.5rem;">Valider</button>
                  </div>
                </form>
              </div>
              <div class="modal-footer"></div>
            </div>
          </div>
        </template>
        <template v-else-if="section==='taxes'">
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
              <div class="modal-header" style="display:flex;align-items:center;gap:0.7rem;">
                <h3 style="margin:0;flex:1;text-align:center;">{{ editingTax ? 'Modifier la taxe' : 'Ajouter une taxe' }}</h3>
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
                  <div class="modal-actions" style="display:flex;justify-content:flex-end;gap:1rem;">
                    <button type="button" @click="closeTaxModal" class="btn-cancel">Annuler</button>
                    <button type="submit" class="btn-save" style="background:#10b981;">Valider</button>
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
@media (max-width: 900px) {
  .form-row {
    flex-wrap: wrap;
    gap: 0.7rem;
  }
  .form-group {
    max-width: 100%;
  }
}
</style>
