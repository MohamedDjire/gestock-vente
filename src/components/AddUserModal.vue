
<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content user-modal" @click.stop>
      <div class="modal-header">
        <h3>Ajouter un utilisateur</h3>
        <button class="modal-close" @click="$emit('close')">×</button>
      </div>
      <div class="modal-body">
        <form @submit.prevent="handleSubmit" class="user-form" id="add-user-form">
          <div class="form-group">
            <label>Photo</label>
            <input type="file" accept="image/*" @change="onPhotoChange" />
            <div v-if="uploadingPhoto" style="color:#218c6a;font-size:0.95em;">Envoi en cours...</div>
            <div v-if="photoUrl" style="margin-top:0.5em;"><img :src="photoUrl" alt="Photo utilisateur" style="max-width:80px;border-radius:8px;" /></div>
            <div v-if="photoError" style="color:#dc2626;font-size:0.95em;">{{ photoError }}</div>
          </div>
          <div class="form-row">
            <div class="form-col">
              <div class="form-group">
                <label>Nom *</label>
                <input v-model="user.nom" placeholder="Nom *" required />
              </div>
              <div class="form-group">
                <label>Prénom *</label>
                <input v-model="user.prenom" placeholder="Prénom *" required />
              </div>
              <div class="form-group">
                <label>Email *</label>
                <input v-model="user.email" type="email" placeholder="Email *" required />
              </div>
            </div>
            <div class="form-col">
              <div class="form-group">
                <label>Mot de passe *</label>
                <input v-model="user.password" type="password" placeholder="Mot de passe *" required />
              </div>
              <div class="form-group">
                <label>Rôle *</label>
                <select v-model="user.role" required>
                  <option value="">Rôle *</option>
                  <option value="admin">Administrateur</option>
                  <option value="utilisateur">Utilisateur</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-section">
            <h4 class="section-title">Accès & Permissions</h4>
            <div>
              <AccessSelector
                :items="entrepots"
                v-model="user.permissions_entrepots"
                label="entrepôts"
              />
              <AccessSelector
                :items="pointsVente"
                v-model="user.permissions_points_vente"
                label="points de vente"
              />
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" v-model="user.acces_comptabilite" />
                Accès à la comptabilité
              </label>
            </div>
          </div>
          <div class="accordion">
            <div class="accordion-header" @click.stop="showPermissions = !showPermissions">
              <span>Permissions avancées</span>
              <span>{{ showPermissions ? '▲' : '▼' }}</span>
            </div>

            <div v-if="showPermissions" class="accordion-body">
              <label v-for="perm in permissions" :key="perm.value" class="perm-checkbox">
                <input type="checkbox" v-model="user.permissions" :value="perm.value" />
                {{ perm.label }}
              </label>
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn-cancel" @click="$emit('close')">Annuler</button>
            <button type="submit" class="btn-save">Valider</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, defineEmits } from 'vue'
import AccessSelector from './AccessSelector.vue'
import apiEntrepot from '../composables/api/api_entrepot.js'
import apiPointVente from '../composables/api/api_point_vente.js'
import { uploadPhoto } from '../config/cloudinary'

const photoUrl = ref('')
const uploadingPhoto = ref(false)
const photoError = ref('')
async function onPhotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  uploadingPhoto.value = true
  photoError.value = ''
  try {
    const result = await uploadPhoto(file)
    if (result.success && result.data.url) {
      photoUrl.value = result.data.url
    } else {
      photoError.value = result.message || 'Erreur lors de l\'upload.'
    }
  } catch (err) {
    photoError.value = err.message
  } finally {
    uploadingPhoto.value = false
  }
}

const emit = defineEmits(['close', 'save'])

const user = reactive({
  nom: '',
  prenom: '',
  email: '',
  role: '',
  password: '',
  permissions: [],
  permissions_entrepots: [],
  permissions_points_vente: [],
  acces_comptabilite: false
})

const entrepots = ref([])
const pointsVente = ref([])

onMounted(async () => {
  const resEntrepots = await apiEntrepot.getAll()
  entrepots.value = Array.isArray(resEntrepots.data) ? resEntrepots.data.map(e => ({ id: e.id_entrepot, nom: e.nom_entrepot })) : []
  const resPV = await apiPointVente.getAll()
  pointsVente.value = Array.isArray(resPV) ? resPV.map(pv => ({ id: pv.id_point_vente, nom: pv.nom_point_vente })) : []
})

const showPermissions = ref(false)

const permissions = [
  { label: 'Accès ventes', value: 'ventes' },
  { label: 'Accès produits', value: 'produits' },
  { label: 'Accès stocks', value: 'stocks' },
  { label: 'Accès fournisseurs', value: 'fournisseurs' },
  { label: 'Accès clients', value: 'clients' },
  { label: 'Accès paramètres', value: 'parametres' },
  { label: 'Gestion utilisateurs', value: 'utilisateurs' }
]

function handleSubmit() {
  emit('save', { ...user, photo: photoUrl.value })
}
</script>

<style scoped>
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
  max-width: 700px;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

.form-row {
  display: flex;
  gap: 24px;
}
.form-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

@media (max-width: 700px) {
  .modal-content.user-modal {
    max-width: 98vw;
    padding: 1rem 0.5rem;
  }
  .form-row {
    flex-direction: column;
    gap: 0;
  }
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}
.modal-header h3 {
  font-size: 1.3rem;
  font-weight: 700;
  color: #218c6a;
}
.modal-close {
  background: none;
  border: none;
  font-size: 1.7rem;
  color: #888;
  cursor: pointer;
}
.modal-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.2rem;
}
.modal-row input, .modal-row select {
  flex: 1 1 0%;
  padding: 0.6rem 0.8rem;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  font-size: 1rem;
  background: #f9fafb;
}
.accordion {
  margin: 0.7rem 0 0.2rem 0;
  border-radius: 7px;
  background: #f3f4f6;
  box-shadow: 0 1px 2px 0 rgba(26,95,74,0.04);
}
.accordion-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.7rem 1rem;
  font-weight: 600;
  color: #218c6a;
  cursor: pointer;
  user-select: none;
}
.accordion-body {
  padding: 0.7rem 1.2rem 0.7rem 1.2rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.7rem 1.2rem;
}
.perm-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5em;
  font-size: 0.98rem;
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
.btn-save:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
