<template>
  <div class="settings-page">
   
    <aside class="settings-sidebar">
      <ul>
        <li :class="{active: section==='entreprise'}" @click="section='entreprise'">Entreprise</li>
        <li :class="{active: section==='utilisateurs'}" @click="section='utilisateurs'">Utilisateurs</li>
        <li :class="{active: section==='taxes'}" @click="section='taxes'">Taxes</li>
        <li :class="{active: section==='categories'}" @click="section='categories'">Catégories produits</li>
        <li :class="{active: section==='facturation'}" @click="section='facturation'">Facturation</li>
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
              <div v-if="userError" class="form-error">{{ userError }}</div>
              <div class="table-container">
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
              <!-- Modal suppression utilisateur -->
              <!-- Modale de suppression harmonisée -->
              <div v-if="showDeleteUserModal" class="confirmation-overlay" @click.self="closeDeleteUserModal">
                <div class="confirmation-modal">
                  <div class="confirmation-header">
                    <span class="confirmation-icon">⚠️</span>
                    <h3>Confirmer la suppression</h3>
                  </div>
                  <div class="confirmation-body">
                    <p>Êtes-vous sûr de vouloir supprimer cet utilisateur&nbsp;?
                      <span><b>{{ userToDelete?.prenom }} {{ userToDelete?.nom }}</b></span>
                    </p>
                    <p style="color:#dc2626;font-weight:600;">Cette action est irréversible.</p>
                  </div>
                  <div class="confirmation-actions">
                    <button @click="closeDeleteUserModal" class="btn-cancel">Annuler</button>
                    <button @click="confirmDeleteUser" class="btn-danger">Supprimer</button>
                  </div>
                </div>
              </div>
              <!-- Bloc style déplacé à la fin du fichier -->
              <div v-if="showAddUser || editingUser" class="modal" @click.self="closeUserModal">
                <div class="modal-content">
                  <div class="modal-header">
                    <h2 class="modal-title">{{ editingUser ? 'Modifier' : 'Ajouter' }} un utilisateur</h2>
                    <button @click="closeUserModal" class="btn-close">×</button>
                  </div>
                  <form @submit.prevent="submitUserForm" class="modal-form">
                    <div v-if="userFormError" class="form-error">{{ userFormError }}</div>
                    <div class="form-group">
                      <label>Prénom</label>
                      <input v-model="userForm.prenom" required class="form-input" />
                    </div>
                    <div class="form-group">
                      <label>Nom</label>
                      <input v-model="userForm.nom" required class="form-input" />
                    </div>
                    <div class="form-group">
                      <label>Email</label>
                      <input v-model="userForm.email" type="email" required class="form-input" />
                    </div>
                    <div class="form-group">
                      <label>Téléphone</label>
                      <input v-model="userForm.telephone" type="text" required class="form-input" />
                    </div>
                    <div class="form-group">
                      <label>Rôle</label>
                      <select v-model="userForm.role" required class="form-input">
                        <option value="admin">Administrateur</option>
                        <option value="utilisateur">Utilisateur</option>
                      </select>
                      <small class="form-hint">Le rôle détermine les droits d'accès de l'utilisateur.</small>
                    </div>
                    <div class="form-group">
                      <label>Nom d'utilisateur</label>
                      <input v-model="userForm.username" required class="form-input" />
                    </div>
                    <div class="form-group">
                      <label>Mot de passe</label>
                      <input v-model="userForm.mot_de_passe" type="password" required class="form-input" />
                    </div>
                    <div class="modal-footer">
                      <button type="button" @click="closeUserModal" class="btn-secondary">Annuler</button>
                      <button type="submit" class="btn-primary">Valider</button>
                    </div>
                  </form>
                </div>
              </div>
            </template>
            <template v-else-if="section==='taxes'">
              <h2>Gestion des taxes</h2>
              <button class="btn-primary" style="margin-bottom:1rem;">Ajouter une taxe</button>
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
                    <tr>
                      <td>TVA</td>
                      <td>18</td>
                      <td class="actions-column">
                        <button class="btn-icon" title="Modifier">✏️</button>
                        <button class="btn-icon btn-danger" title="Supprimer">🗑️</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
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
            <template v-else>
              <h2>Section à venir</h2>
              <p>Cette section sera bientôt disponible.</p>
            </template>
      </div>
    </main>
  </div>
</template>
            
<script setup>

import { ref, onMounted, watch } from 'vue'
import apiEntreprise from '../composables/api/apiEntreprise.js'
import apiUtilisateur from '../composables/api/apiUtilisateur.js'
import { useAuthStore } from '../stores/auth.js'
import { logJournal } from '../composables/useJournal'

const section = ref('entreprise')


// Utilisateurs
const users = ref([])
const userError = ref('')
const showAddUser = ref(false)
const editingUser = ref(null)
const userForm = ref({ prenom: '', nom: '', email: '', telephone: '', role: 'utilisateur', username: '', mot_de_passe: '' })
const userFormError = ref('')
const showDeleteUserModal = ref(false)
const userToDelete = ref(null)

watch(section, (val) => {
  if (val === 'utilisateurs') fetchUsers()
})

onMounted(() => {
  if (section.value === 'utilisateurs') fetchUsers()
})

async function fetchUsers() {
  userError.value = ''
  try {
    users.value = await apiUtilisateur.getAll()
  } catch (e) {
    userError.value = e.message || 'Erreur lors du chargement'
  }
}

function editUser(user) {
  editingUser.value = user
  userForm.value = { prenom: user.prenom, nom: user.nom, email: user.email, telephone: user.telephone || '', role: user.role, username: user.username || '', mot_de_passe: '' }
  showAddUser.value = false
}
function closeUserModal() {
  showAddUser.value = false
  editingUser.value = null
  userForm.value = { prenom: '', nom: '', email: '', telephone: '', role: 'utilisateur', username: '', mot_de_passe: '' }
  userFormError.value = ''
}
async function submitUserForm() {
  userFormError.value = ''
  try {
    if (editingUser.value) {
      await apiUtilisateur.update(editingUser.value.id_utilisateur, userForm.value)
      logJournal({ user: getJournalUser(), action: 'Modification utilisateur', details: `Utilisateur ${userForm.value.prenom} ${userForm.value.nom} modifié.` })
    } else {
      await apiUtilisateur.create({ ...userForm.value, id_entreprise: entrepriseId })
      logJournal({ user: getJournalUser(), action: 'Ajout utilisateur', details: `Utilisateur ${userForm.value.prenom} ${userForm.value.nom} ajouté.` })
    }
    closeUserModal()
    await fetchUsers()
  } catch (e) {
    userFormError.value = e.message || 'Erreur lors de l\'enregistrement'
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

const entreprise = ref({ nom: '', adresse: '', devise: '' })
const loadingEntreprise = ref(false)
const entrepriseError = ref('')
const authStore = useAuthStore()
const entrepriseId = authStore.enterpriseId

onMounted(fetchEntreprise)

async function fetchEntreprise() {
  if (!entrepriseId) {
    entrepriseError.value = "ID d'entreprise introuvable";
    return;
  }
  loadingEntreprise.value = true
  entrepriseError.value = ''
  try {
    const data = await apiEntreprise.getEntreprise(entrepriseId)
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
  if (!entrepriseId) {
    entrepriseError.value = "ID d'entreprise introuvable";
    return;
  }
  loadingEntreprise.value = true
  entrepriseError.value = ''
  try {
    // Adapter le payload pour correspondre aux clés backend
    const payload = {
      nom_entreprise: entreprise.value.nom,
      adresse: entreprise.value.adresse,
      devise: entreprise.value.devise
    }
    await apiEntreprise.updateEntreprise(entrepriseId, payload)
    alert('Paramètres enregistrés !')
  } catch (e) {
    entrepriseError.value = e.message || 'Erreur lors de la sauvegarde'
  } finally {
    loadingEntreprise.value = false
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
</script>

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
</style>
