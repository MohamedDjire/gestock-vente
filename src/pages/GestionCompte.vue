<template>
    <!-- Snackbar notification -->
    <transition name="fade">
      <div v-if="showSnackbar" :class="['snackbar', snackbarType]">
        {{ snackbarMessage }}
      </div>
    </transition>
  <div class="gestion-compte-page">
    <div class="page-header">
      <h1>Gestion du Compte</h1>
      <p class="subtitle">Administration complète de votre entreprise</p>
    </div>

    <!-- Tabs de navigation -->
    <div class="tabs-container">
      <button 
        v-for="tab in tabs" 
        :key="tab.id"
        :class="['tab-button', { active: activeTab === tab.id }]"
        @click="activeTab = tab.id"
      >
        <span class="tab-icon">{{ tab.icon }}</span>
        {{ tab.label }}
      </button>
    </div>


    <!-- Onglet Forfaits -->
    <div v-if="activeTab === 'forfaits'" class="tab-content">
      <div class="section-card">
        <div class="card-header">
          <h2>Gestion des Forfaits</h2>
          <button @click="loadForfaitStatus" class="btn-refresh" :disabled="loadingForfait">
            🔄 Actualiser
          </button>
        </div>
        <div v-if="loadingForfait" class="loading-state"><p>Chargement...</p></div>
        <template v-else>
          <!-- Statut du forfait actuel -->
          <div class="current-forfait-section">
            <h3>Forfait Actuel</h3>
            <div v-if="currentForfait && currentForfait.actif" class="forfait-status-card active">
              <div class="status-header">
                <div class="status-badge active-badge">Actif</div>
                <div class="jours-restants" v-if="currentForfait.jours_restants !== undefined && currentForfait.jours_restants !== null">
                  {{ currentForfait.jours_restants }} jours restants
                </div>
              </div>
              <div class="forfait-details-grid">
                <div class="detail-item">
                  <span class="detail-label">Nom du forfait</span>
                  <span class="detail-value">{{ currentForfait.nom || '—' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Prix</span>
                  <span class="detail-value">{{ formatPrice(currentForfait.prix) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Durée</span>
                  <span class="detail-value">{{ currentForfait.duree_jours }} jours</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Date de début</span>
                  <span class="detail-value">{{ formatDate(currentForfait.date_debut) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Date de fin</span>
                  <span class="detail-value" :class="getDateClass(currentForfait.date_fin)">
                    {{ formatDate(currentForfait.date_fin) }}
                  </span>
                </div>
                <div class="detail-item" v-if="currentForfait.description">
                  <span class="detail-label">Description</span>
                  <span class="detail-value">{{ currentForfait.description }}</span>
                </div>
              </div>
            </div>
            <div v-else class="forfait-status-card expired">
              <div class="status-header">
                <div class="status-badge expired-badge">Aucun forfait actif</div>
              </div>
              <p class="expired-message">Votre forfait a expiré ou vous n'avez pas encore souscrit à un forfait.</p>
            </div>
          </div>

          <!-- Liste des forfaits disponibles -->
          <div class="available-forfaits-section">
            <h3>Forfaits Disponibles</h3>
            <div v-if="loadingForfaits" class="loading-state"><p>Chargement des forfaits...</p></div>
            <div v-else-if="availableForfaits.length === 0" class="empty-state"><p>Aucun forfait disponible</p></div>
            <div v-else class="forfaits-grid">
              <div
                v-for="forfait in availableForfaits"
                :key="forfait.id_forfait"
                class="forfait-card"
                :class="{ 'forfait-current': forfait.id_forfait === currentForfait?.id_forfait }"
              >
                <div class="forfait-card-header">
                  <h4>{{ forfait.nom_forfait }}</h4>
                  <div class="forfait-price">{{ formatPrice(forfait.prix) }}</div>
                </div>
                <div class="forfait-card-body">
                  <div class="forfait-info">
                    <span class="info-icon">⏱️</span>
                    <span>{{ forfait.duree_jours }} jours</span>
                  </div>
                  <p class="forfait-description" v-if="forfait.description">{{ forfait.description }}</p>
                  <p class="forfait-description" v-else>Forfait standard</p>

                  <div class="forfait-limits" v-if="forfait.max_utilisateurs != null || forfait.max_entrepots != null || forfait.max_points_vente != null || forfait.peut_nommer_admin == 1">
                    <div class="limit-item" v-if="forfait.max_utilisateurs != null">
                      <span class="limit-icon">👥</span>
                      <span class="limit-text"><strong>{{ forfait.max_utilisateurs }}</strong> utilisateur{{ forfait.max_utilisateurs > 1 ? 's' : '' }} + admin</span>
                    </div>
                    <div class="limit-item" v-if="forfait.max_entrepots != null">
                      <span class="limit-icon">🏭</span>
                      <span class="limit-text"><strong>{{ forfait.max_entrepots }}</strong> entrepôt{{ forfait.max_entrepots > 1 ? 's' : '' }}</span>
                    </div>
                    <div class="limit-item" v-if="forfait.max_points_vente != null">
                      <span class="limit-icon">🏪</span>
                      <span class="limit-text"><strong>{{ forfait.max_points_vente }}</strong> point{{ forfait.max_points_vente > 1 ? 's' : '' }} de vente</span>
                    </div>
                    <div class="limit-item" v-if="forfait.peut_nommer_admin == 1">
                      <span class="limit-icon">👑</span>
                      <span class="limit-text">Peut nommer un autre admin</span>
                    </div>
                  </div>

                  <div class="forfait-features" v-if="parseFeatures(forfait.fonctionnalites_avancees).length">
                    <div class="features-title">Fonctionnalités incluses</div>
                    <ul class="features-list">
                      <li v-for="(feature, i) in parseFeatures(forfait.fonctionnalites_avancees)" :key="i">✓ {{ feature }}</li>
                    </ul>
                  </div>
                </div>
                <div class="forfait-card-footer">
                  <button
                    @click="forfait.id_forfait === currentForfait?.id_forfait && currentForfait?.actif ? openRenouvelerForfaitModal(forfait) : goToPaiementForfait(forfait)"
                    class="btn-subscribe"
                  >
                    {{ forfait.id_forfait === currentForfait?.id_forfait && currentForfait?.actif ? 'Renouveler' : "S'abonner" }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Onglet Entreprise : Paramètres de l'entreprise -->
    <div v-if="activeTab === 'entreprise'" class="tab-content">
      <div class="section-card">
        <div class="card-header">
          <h2>Paramètres de l'entreprise</h2>
          <button @click="loadEntreprise" class="btn-refresh" :disabled="loadingEntreprise">🔄 Actualiser</button>
        </div>
        <div v-if="loadingEntreprise" class="loading-state"><p>Chargement...</p></div>
        <div v-else class="entreprise-params-block">
          <div class="entreprise-card-pro" v-if="entreprise.nom || entreprise.sigle">
            <div v-if="entreprise.logo" class="entreprise-logo-wrapper">
              <img :src="entreprise.logo" alt="Logo" class="entreprise-logo" />
            </div>
            <h3 class="entreprise-title">{{ entreprise.nom || '—' }}</h3>
            <div class="entreprise-section">
              <h4 class="entreprise-section-title">Identité</h4>
              <div class="entreprise-field"><span class="entreprise-label">Sigle</span><span class="entreprise-value">{{ entreprise.sigle || '—' }}</span></div>
              <div class="entreprise-field"><span class="entreprise-label">Numéro d'identification</span><span class="entreprise-value">{{ entreprise.num || '—' }}</span></div>
              <div class="entreprise-field"><span class="entreprise-label">NCC</span><span class="entreprise-value">{{ entreprise.ncc || '—' }}</span></div>
              <div class="entreprise-field"><span class="entreprise-label">Numéro de banque</span><span class="entreprise-value">{{ entreprise.num_banque || '—' }}</span></div>
            </div>
            <div class="entreprise-section">
              <h4 class="entreprise-section-title">Coordonnées</h4>
              <div class="entreprise-field"><span class="entreprise-label">Adresse</span><span class="entreprise-value">{{ entreprise.adresse || '—' }}</span></div>
              <div class="entreprise-field"><span class="entreprise-label">Email</span><span class="entreprise-value">{{ entreprise.email || '—' }}</span></div>
              <div class="entreprise-field"><span class="entreprise-label">Téléphone</span><span class="entreprise-value">{{ entreprise.telephone || '—' }}</span></div>
              <div class="entreprise-field"><span class="entreprise-label">Site web</span><span class="entreprise-value">{{ entreprise.site_web || '—' }}</span></div>
              <div class="entreprise-field"><span class="entreprise-label">Devise</span><span class="entreprise-value">{{ entreprise.devise || '—' }}</span></div>
            </div>
            <div style="text-align:right;margin-top:1.5rem;">
              <button class="btn-primary" @click="showEditModalEntrep = true">Modifier</button>
            </div>
          </div>
          <div v-else class="empty-state"><p>Aucune entreprise chargée. Vérifiez votre compte.</p></div>
        </div>
        <!-- Modale Modifier l'entreprise -->
        <div v-if="showEditModalEntrep" class="modal-overlay" @click.self="showEditModalEntrep = false">
          <div class="modal-content large" @click.stop>
            <div class="modal-header">
              <h3>Modifier l'entreprise</h3>
              <button class="modal-close" @click="showEditModalEntrep = false">×</button>
            </div>
            <div class="modal-body">
              <form class="settings-form" @submit.prevent="saveEntreprise">
                <div v-if="entrepriseError" class="form-error">{{ entrepriseError }}</div>
                <div class="form-group"><label>Nom</label><input type="text" v-model="entreprise.nom" placeholder="Nom de l'entreprise" /></div>
                <div class="form-group"><label>Sigle</label><input type="text" v-model="entreprise.sigle" placeholder="Sigle" /></div>
                <div class="form-group"><label>Numéro d'identification</label><input type="text" v-model="entreprise.num" placeholder="Num" /></div>
                <div class="form-group"><label>NCC</label><input type="text" v-model="entreprise.ncc" placeholder="NCC" /></div>
                <div class="form-group"><label>Numéro de banque</label><input type="text" v-model="entreprise.num_banque" placeholder="Numéro de banque" /></div>
                <div class="form-group"><label>Adresse</label><input type="text" v-model="entreprise.adresse" placeholder="Adresse" /></div>
                <div class="form-group"><label>Devise</label><input type="text" v-model="entreprise.devise" placeholder="XOF, EUR..." /></div>
                <div class="form-group"><label>Email</label><input type="email" v-model="entreprise.email" placeholder="Email" /></div>
                <div class="form-group"><label>Téléphone</label><input type="text" v-model="entreprise.telephone" placeholder="Téléphone" /></div>
                <div class="form-group"><label>Site web</label><input type="text" v-model="entreprise.site_web" placeholder="Site web" /></div>
                <div class="form-group">
                  <label>Logo</label>
                  <input type="file" accept="image/*" @change="onEntrepriseLogoChange" />
                  <div v-if="uploadingLogoEntrep" class="form-hint">Envoi en cours...</div>
                  <div v-if="entreprise.logo" style="margin-top:0.5em;"><img :src="entreprise.logo" alt="Logo" style="max-width:100px;border-radius:8px;" /></div>
                  <div v-if="logoErrorEntrep" style="color:#dc2626;font-size:0.9em;">{{ logoErrorEntrep }}</div>
                </div>
                <div class="modal-actions">
                  <button type="button" class="btn-cancel" @click="showEditModalEntrep = false">Annuler</button>
                  <button type="submit" class="btn-save" :disabled="savingEntreprise">{{ savingEntreprise ? 'Enregistrement...' : 'Enregistrer' }}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Onglet Membres Connectés -->
    <div v-if="activeTab === 'connected'" class="tab-content">
      <div class="section-card">
        <div class="card-header">
          <h2>Membres Connectés</h2>
          <button @click="loadConnectedUsers" class="btn-refresh" :disabled="loadingConnected">
            🔄 Actualiser
          </button>
        </div>

        <div v-if="loadingConnected" class="loading-state">
          <p>Chargement des membres connectés...</p>
        </div>
        <div v-else-if="connectedUsers.length === 0" class="empty-state">
          <p>Aucun membre connecté actuellement</p>
        </div>
        <div v-else class="connected-users-list">
          <div 
            v-for="user in connectedUsers" 
            :key="user.id_utilisateur"
            class="connected-user-card"
          >
            <div class="user-avatar">
              <span>{{ getUserInitials(user) }}</span>
            </div>
            <div class="user-info">
              <div class="user-name">{{ user.prenom }} {{ user.nom }}</div>
              <div class="user-details">
                <span class="user-email">{{ user.email }}</span>
                <span class="user-role" :class="`role-${user.role?.toLowerCase()}`">
                  {{ user.role }}
                </span>
              </div>
              <div class="user-last-login">
                <span class="login-label">Dernière connexion:</span>
                <span class="login-time">{{ formatLastLogin(user.dernier_login) }}</span>
              </div>
            </div>
            <div class="user-status-indicator">
              <div class="status-dot active"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Onglet Gestion Utilisateurs -->
    <div v-if="activeTab === 'users'" class="tab-content">
      <div class="section-card">
        <div class="card-header">
          <h2>Gestion des Utilisateurs</h2>
          <button @click="openAddUserModal" class="btn-primary">
            ➕ Ajouter un utilisateur
          </button>
        </div>

        <!-- Filtres et recherche -->
        <div class="filters-section">
          <input 
            v-model="userSearchQuery"
            type="text"
            placeholder="🔍 Rechercher un utilisateur..."
            class="search-input"
          />
          <select v-model="userRoleFilter" class="filter-select">
            <option value="">Tous les rôles</option>
            <option value="admin">Admin</option>
            <option value="Agent">Agent</option>
            <option value="superadmin">Super Admin</option>
          </select>
          <select v-model="userStatusFilter" class="filter-select">
            <option value="">Tous les statuts</option>
            <option value="actif">Actif</option>
            <option value="inactif">Inactif</option>
          </select>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="users-table-container">
          <div v-if="loadingUsers" class="loading-state">
            <p>Chargement des utilisateurs...</p>
          </div>
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
                <th>Dernière connexion</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id_utilisateur">
                <td>
                  <div class="user-cell">
                    <div class="user-avatar-small">{{ getUserInitials(user) }}</div>
                    <span>{{ user.prenom }} {{ user.nom }}</span>
                  </div>
                </td>
                <td>{{ user.email }}</td>
                <td>
                  <span class="role-badge" :class="`role-${user.role?.toLowerCase()}`">
                    {{ user.role }}
                  </span>
                </td>
                <td>
                  <span class="status-badge" :class="user.statut === 'actif' ? 'status-active' : 'status-inactive'">
                    {{ user.statut === 'actif' ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td>
                  <span class="last-login">{{ formatLastLogin(user.dernier_login) }}</span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button 
                      @click="editUser(user)" 
                      class="btn-action btn-edit"
                      title="Modifier"
                    >
                      ✏️
                    </button>
                    <button 
                      @click="toggleUserStatus(user)" 
                      class="btn-action"
                      :class="user.statut === 'actif' ? 'btn-block' : 'btn-unblock'"
                      :title="user.statut === 'actif' ? 'Désactiver' : 'Activer'"
                    >
                      {{ user.statut === 'actif' ? '🚫' : '✅' }}
                    </button>
                    <button 
                      @click="confirmDeleteUser(user)" 
                      class="btn-action btn-delete"
                      title="Supprimer"
                      :disabled="user.id_utilisateur === currentUserId"
                    >
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Ajouter/Modifier Utilisateur -->
    <div v-if="showUserModal" class="modal-overlay" @click.self="closeUserModal">
      <div class="modal-content user-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ editingUser ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' }}</h3>
          <button class="modal-close" @click="closeUserModal">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="onUserFormSubmit" class="user-form">
            <div class="form-section">
              <h4 class="section-title">🖼️ Photo</h4>
              <div class="form-group">
                <label>Photo</label>
                <input type="file" accept="image/*" @change="onPhotoChange" />
                <div v-if="uploadingPhoto" style="color:#218c6a;font-size:0.95em;">Envoi en cours...</div>
                <div v-if="userForm.photo" style="margin-top:0.5em;"><img :src="userForm.photo" alt="Photo utilisateur" style="max-width:80px;border-radius:8px;" /></div>
                <div v-if="photoError" style="color:#dc2626;font-size:0.95em;">{{ photoError }}</div>
              </div>
            </div>
            <div class="form-section">
              <h4 class="section-title">👤 Identité</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Nom *</label>
                  <input v-model="userForm.nom" type="text" required />
                </div>
                <div class="form-group">
                  <label>Prénom *</label>
                  <input v-model="userForm.prenom" type="text" required />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Email *</label>
                  <input v-model="userForm.email" type="email" required />
                </div>
                <div class="form-group">
                  <label>Nom d'utilisateur *</label>
                  <input v-model="userForm.username" type="text" required />
                </div>
              </div>
            </div>
            <div class="form-section">
              <h4 class="section-title">🔒 Sécurité & Rôle</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Rôle *</label>
                  <select v-model="userForm.role" required>
                    <option value="">Sélectionner un rôle</option>
                    <option value="admin">Administrateur</option>
                    <option value="Agent">Agent</option>
                  </select>
                </div>
                <div class="form-group" v-if="!editingUser">
                  <label>Mot de passe *</label>
                  <input v-model="userForm.password" type="password" required />
                </div>
                <div class="form-group">
                  <label>Téléphone</label>
                  <input v-model="userForm.telephone" type="tel" />
                </div>
              </div>
            </div>
            <div class="form-section">
              <h4 class="section-title">🔑 Accès</h4>
              <div class="form-row">
                <div class="form-group">
                  <AccessSelector
                    :items="entrepots"
                    v-model="userForm.permissions_entrepots"
                    label="entrepôts"
                  />
                </div>
                <div class="form-group">
                  <AccessSelector
                    :items="pointsVente"
                    v-model="userForm.permissions_points_vente"
                    label="points de vente"
                  />
                </div>
              </div>
              <div class="form-group">
                <label>
                  <input type="checkbox" v-model="userForm.acces_comptabilite" />
                  Accès à la comptabilité
                </label>
              </div>
              <div v-if="userForm.permissions_entrepots.length === 0 || userForm.permissions_points_vente.length === 0" class="form-warning" style="color:#c0392b;margin-bottom:1rem;">
                Veuillez sélectionner au moins un entrepôt et un point de vente.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-cancel" @click="closeUserModal">Annuler</button>
              <button type="submit" class="btn-save" :disabled="savingUser || userForm.permissions_entrepots.length === 0 || userForm.permissions_points_vente.length === 0">
                {{ savingUser ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-content confirmation-modal" @click.stop>
        <div class="modal-header modal-header-with-icon">
          <div class="modal-header-start">
            <span class="modal-header-icon">⚠️</span>
            <h3>Confirmer la suppression</h3>
          </div>
          <button class="modal-close" @click="showDeleteModal = false">×</button>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>{{ userToDelete?.prenom }} {{ userToDelete?.nom }}</strong> ?</p>
          <p class="modal-warning">Cette action est irréversible.</p>
        </div>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showDeleteModal = false">Annuler</button>
          <button class="btn-danger" @click="deleteUser" :disabled="deletingUser">
            {{ deletingUser ? 'Suppression...' : 'Supprimer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Confirmation Renouvellement forfait -->
    <div v-if="showRenouvelerForfaitModal" class="modal-overlay" @click.self="closeRenouvelerForfaitModal">
      <div class="modal-content confirmation-modal" @click.stop>
        <div class="modal-header modal-header-with-icon">
          <div class="modal-header-start">
            <span class="modal-header-icon">💳</span>
            <h3>Renouveler l'abonnement</h3>
          </div>
          <button class="modal-close" @click="closeRenouvelerForfaitModal">×</button>
        </div>
        <div class="modal-body">
          <p>Voulez-vous vraiment renouveler votre forfait ?</p>
          <p class="modal-hint">La nouvelle durée s'ajoutera au temps restant de votre abonnement. Vous serez redirigé vers la page de paiement.</p>
        </div>
        <div class="modal-actions">
          <button class="btn-cancel" @click="closeRenouvelerForfaitModal">Annuler</button>
          <button class="btn-save" @click="confirmRenouvelerForfait">Aller au paiement</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// --- Notification Snackbar ---
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarType = ref('success') // success | error
function triggerSnackbar(message, type = 'success') {
  snackbarMessage.value = message
  snackbarType.value = type
  showSnackbar.value = true
  setTimeout(() => { showSnackbar.value = false }, 3000)
}
// ...existing code...
import { apiService } from '../composables/Api/apiService.js'
import { useAuthStore } from '../stores/auth.js'
import AccessSelector from '../components/AccessSelector.vue'
import apiEntrepot from '../composables/Api/api_entrepot.js'
import apiPointVente from '../composables/Api/api_point_vente.js'
import { uploadPhoto } from '../config/cloudinary'
import { useCurrency } from '../composables/useCurrency.js'
            // Onglet Entreprise — Paramètres de l'entreprise
            const entreprise = ref({
              nom: '', adresse: '', devise: '', sigle: '', num: '', ncc: '', num_banque: '',
              email: '', telephone: '', site_web: '', logo: ''
            })
            const loadingEntreprise = ref(false)
            const savingEntreprise = ref(false)
            const entrepriseError = ref('')
            const showEditModalEntrep = ref(false)
            const uploadingLogoEntrep = ref(false)
            const logoErrorEntrep = ref('')
            const entrepriseId = computed(() => {
              const user = localStorage.getItem('prostock_user')
              return user ? JSON.parse(user).id_entreprise : null
            })

            async function loadEntreprise() {
              loadingEntreprise.value = true
              entrepriseError.value = ''
              try {
                const id = entrepriseId.value
                if (!id) return
                const data = await apiEntreprise.getEntreprise(id)
                if (data && data.id_entreprise) {
                  entreprise.value = {
                    nom: data.nom_entreprise || '',
                    adresse: data.adresse || '',
                    devise: data.devise || '',
                    sigle: data.sigle || '',
                    num: data.num || '',
                    ncc: data.ncc || '',
                    num_banque: data.num_banque || '',
                    email: data.email || '',
                    telephone: data.telephone || '',
                    site_web: data.site_web || '',
                    logo: data.logo || ''
                  }
                }
              } catch (e) {
                entrepriseError.value = e.message || 'Erreur chargement'
                triggerSnackbar('Erreur chargement entreprise', 'error')
              } finally {
                loadingEntreprise.value = false
              }
            }

            async function saveEntreprise() {
              const id = entrepriseId.value
              if (!id) { entrepriseError.value = "ID entreprise introuvable"; return }
              savingEntreprise.value = true
              entrepriseError.value = ''
              try {
                await apiEntreprise.updateEntreprise(id, {
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
                })
                triggerSnackbar('Entreprise mise à jour !', 'success')
                showEditModalEntrep.value = false
                await loadEntreprise()
              } catch (e) {
                entrepriseError.value = e.message || 'Erreur enregistrement'
                triggerSnackbar('Erreur enregistrement entreprise', 'error')
              } finally {
                savingEntreprise.value = false
              }
            }

            async function onEntrepriseLogoChange(e) {
              const file = e.target.files[0]
              if (!file) return
              uploadingLogoEntrep.value = true
              logoErrorEntrep.value = ''
              try {
                const result = await uploadPhoto(file)
                if (result.success && (result.data?.url || result.data?.secure_url)) {
                  entreprise.value.logo = result.data.secure_url || result.data.url
                } else {
                  logoErrorEntrep.value = result.message || "Erreur upload"
                }
              } catch (err) {
                logoErrorEntrep.value = err.message
              } finally {
                uploadingLogoEntrep.value = false
              }
            }

            onMounted(() => { loadEntreprise() })
            const uploadingPhoto = ref(false)
            const photoError = ref('')
            async function onPhotoChange(e) {
              const file = e.target.files[0]
              if (!file) return
              uploadingPhoto.value = true
              photoError.value = ''
              try {
                const result = await uploadPhoto(file)
                // DEBUG : Afficher tout le résultat Cloudinary
                console.log('Résultat Cloudinary complet :', result);
                if (result.success && (result.data.url || result.data.secure_url)) {
                  userForm.value.photo = result.data.secure_url || result.data.url;
                  // DEBUG : Afficher la valeur de userForm.value.photo après upload
                  console.log('Photo Cloudinary enregistrée dans userForm.value.photo :', userForm.value.photo);
                } else {
                  photoError.value = result.message || 'Erreur lors de l\'upload.'
                }
              } catch (err) {
                photoError.value = err.message
              } finally {
                uploadingPhoto.value = false
              }
            }
            
        // Permissions accès entrepôts/points de vente
        const entrepots = ref([])
        const pointsVente = ref([])
        onMounted(async () => {
          // ...chargements existants...
          try {
            const resEntrepots = await apiEntrepot.getAll()
            const data = Array.isArray(resEntrepots) ? resEntrepots : (resEntrepots?.data || [])
            entrepots.value = data.map(e => ({ id: e.id_entrepot, nom: e.nom_entrepot }))
          } catch {}
          try {
            const resPV = await apiPointVente.getAllPointsVente ? await apiPointVente.getAllPointsVente() : await apiPointVente.getAll()
            const data = Array.isArray(resPV) ? resPV : []
            pointsVente.value = data.map(pv => ({ id: pv.id_point_vente, nom: pv.nom_point_vente }))
          } catch {}
        })

const authStore = useAuthStore()
const router = useRouter()
// Empêche la soumission automatique du formulaire utilisateur sauf par clic explicite
function onUserFormSubmit(e) {
  // Log pour debug
  console.log('Tentative de soumission du formulaire utilisateur', e?.submitter?.type)
  // Vérifie si le bouton submit a été explicitement cliqué
  if (e && e.submitter && e.submitter.type === 'submit') {
    saveUser()
  }
  // Sinon, ne rien faire (empêche la soumission par Entrée ou autre)
}

// Tabs
const activeTab = ref('forfaits')
const tabs = [
  { id: 'forfaits', label: 'Forfaits', icon: '💳' },
  { id: 'entreprise', label: 'Entreprise', icon: '🏢' },
  { id: 'connected', label: 'Membres Connectés', icon: '👥' },
  { id: 'users', label: 'Gestion Utilisateurs', icon: '👤' }
]
import apiEntreprise from '../composables/Api/apiEntreprise.js'
// Onglet Entreprise



onMounted(() => {
  loadEntreprise()
})

// Forfaits
const currentForfait = ref(null)
const availableForfaits = ref([])
const loadingForfait = ref(false)
const loadingForfaits = ref(false)
const subscribing = ref(false)
const showRenouvelerForfaitModal = ref(false)
const forfaitToSubscribe = ref(null)

// Utilisateurs connectés
const connectedUsers = ref([])
const loadingConnected = ref(false)

// Gestion utilisateurs
const users = ref([])
const loadingUsers = ref(false)
const userSearchQuery = ref('')
const userRoleFilter = ref('')
const userStatusFilter = ref('')
const showUserModal = ref(false)
const editingUser = ref(null)
const savingUser = ref(false)
const showDeleteModal = ref(false)
const userToDelete = ref(null)
const deletingUser = ref(false)

const currentUserId = computed(() => authStore.user?.id || authStore.user?.id_utilisateur)

const userForm = ref({
  nom: '',
  prenom: '',
  email: '',
  username: '',
  role: '',
  password: '',
  telephone: '',
  permissions_entrepots: [],
  permissions_points_vente: [],
  acces_comptabilite: false
})

// Computed
const filteredUsers = computed(() => {
  let filtered = users.value

  if (userSearchQuery.value) {
    const query = userSearchQuery.value.toLowerCase()
    filtered = filtered.filter(u => 
      u.nom?.toLowerCase().includes(query) ||
      u.prenom?.toLowerCase().includes(query) ||
      u.email?.toLowerCase().includes(query) ||
      u.username?.toLowerCase().includes(query)
    )
  }

  if (userRoleFilter.value) {
    filtered = filtered.filter(u => u.role?.toLowerCase() === userRoleFilter.value.toLowerCase())
  }

  if (userStatusFilter.value) {
    filtered = filtered.filter(u => u.statut?.toLowerCase() === userStatusFilter.value.toLowerCase())
  }

  return filtered
})

// Méthodes utilitaires
const { formatPrice } = useCurrency()

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatLastLogin = (dateString) => {
  if (!dateString) return 'Jamais connecté'
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'À l\'instant'
  if (diffMins < 60) return `Il y a ${diffMins} min`
  if (diffHours < 24) return `Il y a ${diffHours}h`
  if (diffDays < 7) return `Il y a ${diffDays}j`
  return formatDate(dateString)
}

const getDateClass = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const diffDays = Math.floor((date - now) / (1000 * 60 * 60 * 24))
  if (diffDays < 0) return 'date-expired'
  if (diffDays < 7) return 'date-warning'
  return ''
}

const getUserInitials = (user) => {
  const first = user.prenom?.charAt(0) || ''
  const last = user.nom?.charAt(0) || ''
  return (first + last).toUpperCase() || 'U'
}

const parseFeatures = (featuresJson) => {
  if (!featuresJson) return []
  try {
    if (typeof featuresJson === 'string') {
      return JSON.parse(featuresJson)
    }
    return Array.isArray(featuresJson) ? featuresJson : []
  } catch (e) {
    console.error('Erreur lors du parsing des fonctionnalités:', e)
    return []
  }
}

// Charger les données
const loadForfaitStatus = async () => {
  loadingForfait.value = true
  try {
    const response = await apiService.get('/api_forfait.php?action=status')
    if (response.success && response.data) {
      currentForfait.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement du statut du forfait:', error)
  } finally {
    loadingForfait.value = false
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

const openRenouvelerForfaitModal = (forfait) => {
  forfaitToSubscribe.value = forfait
  showRenouvelerForfaitModal.value = true
}

const closeRenouvelerForfaitModal = () => {
  showRenouvelerForfaitModal.value = false
  forfaitToSubscribe.value = null
}

const goToPaiementForfait = (forfait, isRenewal = false) => {
  const query = {
    id_forfait: forfait.id_forfait,
    nom_forfait: forfait.nom_forfait || forfait.nom || '',
    prix: forfait.prix,
    duree_jours: forfait.duree_jours
  }
  if (isRenewal) query.renouvellement = '1'
  router.push({ name: 'PaiementForfait', query })
}

const confirmRenouvelerForfait = () => {
  const f = forfaitToSubscribe.value
  if (!f) return
  closeRenouvelerForfaitModal()
  goToPaiementForfait(f, true)
}

const loadConnectedUsers = async () => {
  loadingConnected.value = true
  try {
    const user = localStorage.getItem('prostock_user')
    const id_entreprise = user ? JSON.parse(user).id_entreprise : null
    const response = await apiService.get(`/index.php?action=all&enterprise_id=${id_entreprise}`)
    if (response.success && response.data) {
      const now = new Date()
      const oneDayAgo = new Date(now.getTime() - 24 * 60 * 60 * 1000)
      connectedUsers.value = response.data.filter(user => {
        if (!user.dernier_login) return false
        const lastLogin = new Date(user.dernier_login)
        return lastLogin >= oneDayAgo && user.statut === 'actif'
      }).sort((a, b) => {
        const dateA = new Date(a.dernier_login || 0)
        const dateB = new Date(b.dernier_login || 0)
        return dateB - dateA
      })
    }
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs connectés:', error)
  } finally {
    loadingConnected.value = false
  }
}

const loadUsers = async () => {
  loadingUsers.value = true
  try {
    const user = localStorage.getItem('prostock_user')
    const id_entreprise = user ? JSON.parse(user).id_entreprise : null
    const response = await apiService.get(`/index.php?action=all&id_entreprise=${id_entreprise}`)
    if (response.success) {
      users.value = response.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  } finally {
    loadingUsers.value = false
  }
}

// Gestion utilisateurs
const openAddUserModal = () => {
  editingUser.value = null
  userForm.value = {
    nom: '',
    prenom: '',
    email: '',
    username: '',
    role: '',
    password: '',
    telephone: '',
    photo: '',
    permissions_entrepots: [],
    permissions_points_vente: [],
    acces_comptabilite: false
  }
  showUserModal.value = true
}

const editUser = (user) => {
  editingUser.value = user
  userForm.value = {
    nom: user.nom || '',
    prenom: user.prenom || '',
    email: user.email || '',
    username: user.username || '',
    role: user.role || '',
    password: '',
    telephone: user.telephone || '',
    photo: user.photo || '',
    permissions_entrepots: user.permissions_entrepots || [],
    permissions_points_vente: user.permissions_points_vente || [],
    acces_comptabilite: user.acces_comptabilite === true || user.acces_comptabilite === 1
  }
  showUserModal.value = true
}

const closeUserModal = () => {
  showUserModal.value = false
  editingUser.value = null
}

const saveUser = async () => {
  savingUser.value = true
  try {
    if (editingUser.value) {
      // Mise à jour
      let updateData = { ...userForm.value };
      if (!updateData.photo || updateData.photo === '') {
        updateData.photo = editingUser.value.photo || '';
      }
      // DEBUG : Afficher la valeur de photo envoyée
      console.log('Photo envoyée au backend :', updateData.photo);
      if (!updateData.password) {
        delete updateData.password
      }
      const response = await apiService.put(`/index.php?action=update&id=${editingUser.value.id_utilisateur}`, updateData)
      if (response.success) {
        triggerSnackbar('Utilisateur modifié avec succès !', 'success')
        await loadUsers()
        closeUserModal()
      } else {
        triggerSnackbar('Erreur lors de la modification', 'error')
      }
    } else {
      // Création
      const payload = {
        ...userForm.value,
        mot_de_passe: userForm.value.password
      };
      const response = await apiService.post('/index.php?action=create', payload)
      if (response.success) {
        triggerSnackbar('Utilisateur créé avec succès !', 'success')
        await loadUsers()
        closeUserModal()
      } else {
        triggerSnackbar('Erreur lors de la création', 'error')
      }
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    triggerSnackbar('Erreur lors de la sauvegarde de l\'utilisateur', 'error')
  } finally {
    savingUser.value = false
  }
}

// Correction de la fonction deleteUser (qui était mal placée)
const deleteUser = async () => {
  deletingUser.value = true
  try {
    const response = await apiService.delete(`/index.php?action=delete&id=${userToDelete.value.id_utilisateur}`)
    if (response.success) {
      triggerSnackbar('Utilisateur supprimé avec succès !', 'success')
      await loadUsers()
      await loadConnectedUsers()
      showDeleteModal.value = false
      userToDelete.value = null
    } else {
      triggerSnackbar('Erreur lors de la suppression', 'error')
    }
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    triggerSnackbar('Erreur lors de la suppression de l\'utilisateur', 'error')
  } finally {
    deletingUser.value = false
  }
}

// Initialisation
onMounted(async () => {
  await loadForfaitStatus()
  await loadAvailableForfaits()
  await loadUsers()
  await loadConnectedUsers()
})
</script>

<style scoped>
.gestion-compte-page {
  padding: 2rem;
  width: 100%;
  max-width: 100%;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1a5f4a;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #6b7280;
  font-size: 1rem;
}

.tabs-container {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #e5e7eb;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  font-size: 1rem;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-button:hover {
  color: #1a5f4a;
}

.tab-button.active {
  color: #1a5f4a;
  border-bottom-color: #1a5f4a;
}

.tab-icon {
  font-size: 1.2rem;
}

.tab-content {
  animation: fadeIn 0.3s;
  width: 100%;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.section-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  width: 100%;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.card-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
}

.btn-primary {
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #145040;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-refresh {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-refresh:hover:not(:disabled) {
  background: #e5e7eb;
}
.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Forfait Status */
.current-forfait-section {
  margin-bottom: 2rem;
}

.current-forfait-section h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 1rem;
}

.forfait-status-card {
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px solid;
}

.forfait-status-card.active {
  background: #f0fdf4;
  border-color: #22c55e;
  margin-bottom: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.forfait-status-card .status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.forfait-status-card.expired {
  background: #fef2f2;
  border-color: #ef4444;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
}

.active-badge {
  background: #22c55e;
  color: white;
}

.expired-badge {
  background: #ef4444;
  color: white;
}

.jours-restants {
  font-weight: 600;
  color: #059669;
}

.forfait-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.9rem;
  color: #6b7280;
}

.detail-value {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
}

.date-expired {
  color: #ef4444;
}

.date-warning {
  color: #f59e0b;
}

.expired-message {
  color: #6b7280;
  margin-top: 0.5rem;
}

/* Available Forfaits */
.available-forfaits-section h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 1rem;
}

.forfaits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  align-items: stretch;
}

.forfait-card {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.forfait-card:hover {
  border-color: #1a5f4a;
  box-shadow: 0 4px 12px rgba(26,95,74,0.1);
}

.forfait-card.forfait-current {
  border-color: #22c55e;
  background: #f0fdf4;
}

.forfait-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.forfait-card-header h4 {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1a5f4a;
}

.forfait-price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
}

.forfait-card-body {
  margin-bottom: 1rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.forfait-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  color: #6b7280;
}

.info-icon {
  font-size: 1.1rem;
}

.forfait-description {
  color: #6b7280;
  font-size: 0.9rem;
  margin: 0 0 1rem 0;
}

.forfait-limits {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.limit-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.limit-icon {
  font-size: 1.1rem;
  flex-shrink: 0;
}

.limit-text {
  color: #374151;
}

.limit-text strong {
  color: #1a5f4a;
  font-weight: 700;
}

.forfait-features {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.features-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.features-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.features-list li {
  font-size: 0.85rem;
  color: #374151;
  margin-bottom: 0.25rem;
  padding-left: 0;
}

.forfait-card-footer {
  margin-top: auto;
  padding-top: 1rem;
}

.btn-subscribe {
  width: 100%;
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-subscribe:hover:not(:disabled) {
  background: #145040;
}

.btn-subscribe:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

/* Connected Users */
.connected-users-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  width: 100%;
}

.connected-user-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  width: 100%;
}

.user-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #1a5f4a;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
}

.user-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.25rem;
}

.user-details {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.25rem;
}

.user-email {
  color: #6b7280;
  font-size: 0.9rem;
}

.user-role {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.role-admin {
  background: #dbeafe;
  color: #1e40af;
}

.role-agent {
  background: #f3f4f6;
  color: #374151;
}

.user-last-login {
  font-size: 0.85rem;
  color: #9ca3af;
}

.login-label {
  margin-right: 0.5rem;
}

.user-status-indicator {
  flex-shrink: 0;
}

.status-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #22c55e;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Users Table */
.filters-section {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  width: 100%;
}

.search-input {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
}

.filter-select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  background: white;
}

.users-table-container {
  overflow-x: auto;
  width: 100%;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 100%;
}

.users-table thead {
  background: #f9fafb;
}

.users-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.users-table td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar-small {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #1a5f4a;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
}

.role-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-inactive {
  background: #fee2e2;
  color: #991b1b;
}

.last-login {
  color: #6b7280;
  font-size: 0.9rem;
}

.text-muted {
  color: #6b7280;
  font-style: italic;
}

.entreprise-params-block { margin-top: 0.5rem; }
.entreprise-card-pro {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
}
.entreprise-logo-wrapper { margin-bottom: 1rem; }
.entreprise-logo { max-width: 80px; height: auto; border-radius: 8px; }
.entreprise-title { margin: 0 0 1rem 0; font-size: 1.25rem; color: #1a5f4a; }
.entreprise-section { margin-bottom: 1.25rem; }
.entreprise-section-title { font-size: 0.95rem; color: #64748b; margin: 0 0 0.5rem 0; }
.entreprise-field { display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0; font-size: 0.95rem; }
.entreprise-label { color: #64748b; }
.entreprise-value { font-weight: 500; color: #1e293b; }

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.25rem;
  transition: transform 0.2s;
}

.btn-action:hover:not(:disabled) {
  transform: scale(1.2);
}

.btn-action:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

/* Styles spécifiques pour les modales de la page GestionCompte */
/* Les styles de base (.modal-overlay, .modal-content, etc.) sont définis dans style.css */

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
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #374151;
}

.form-group input,
.form-group select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
}

.modal-hint {
  color: #6b7280;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

/* Les styles .modal-actions sont définis dans style.css */

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-danger {
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

.confirm-modal {
  max-width: 400px;
}

.warning-text {
  color: #ef4444;
  font-weight: 600;
  margin-top: 0.5rem;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

/* Harmonisation couleur modale utilisateur */
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

/* Augmentation de la largeur de la modale utilisateur */
.modal-content.user-modal {
  max-width: 700px;
  width: 98vw;
}
</style>

