<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content large" @click.stop>
      <div class="modal-header">
        <h3>{{ entry && Object.keys(entry).length ? 'Modifier une écriture' : 'Ajouter une écriture' }}</h3>
        <button class="modal-close" @click="$emit('close')">×</button>
      </div>
      <div class="modal-body">
        <form @submit.prevent="submit" class="modal-form">
          <div class="form-section">
            <h4 class="section-title">💰 Montant et type</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Date *</label>
                <input v-model="form.date_ecriture" type="date" required />
              </div>
              <div class="form-group">
                <label>Type *</label>
                <select v-model="form.type_ecriture" required>
                  <option value="">Sélectionner</option>
                  <option value="Entrée">Entrée</option>
                  <option value="Sortie">Sortie</option>
                  <option value="Virement">Virement</option>
                  <option value="Ajustement">Ajustement</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Montant *</label>
                <input v-model.number="form.montant" type="number" step="0.01" min="0" required placeholder="0.00" />
              </div>
              <div class="form-group">
                <label>Catégorie</label>
                <select v-model="form.categorie">
                  <option value="">Sélectionner</option>
                  <option value="Vente">Vente</option>
                  <option value="Achat">Achat</option>
                  <option value="Frais">Frais</option>
                  <option value="Salaire">Salaire</option>
                  <option value="Autre">Autre</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Moyen de paiement</label>
                <select v-model="form.moyen_paiement">
                  <option value="">Sélectionner</option>
                  <option value="Espèces">Espèces</option>
                  <option value="Chèque">Chèque</option>
                  <option value="Virement">Virement</option>
                  <option value="Carte">Carte</option>
                  <option value="Autre">Autre</option>
                </select>
              </div>
              <div class="form-group">
                <label>Statut</label>
                <select v-model="form.statut">
                  <option value="">Sélectionner</option>
                  <option value="en attente">En attente</option>
                  <option value="validé">Validé</option>
                  <option value="rejeté">Rejeté</option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4 class="section-title">📎 Référence et pièce jointe</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Référence</label>
                <input v-model="form.reference" placeholder="Numéro ou référence" />
              </div>
              <div class="form-group">
                <label>Pièce jointe</label>
                <input type="file" accept="image/*" @change="onFileChange" />
                <div v-if="form.piece_jointe" class="preview-img">
                  <img :src="form.piece_jointe" alt="Aperçu" />
                </div>
                <small v-if="uploading" class="form-hint">Envoi en cours...</small>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4 class="section-title">📝 Commentaires</h4>
            <div class="form-group">
              <label>Commentaire</label>
              <textarea v-model="form.commentaire" placeholder="Commentaire libre" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>Détails</label>
              <textarea v-model="form.details" placeholder="Détails supplémentaires" rows="2"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" @click="$emit('close')">Annuler</button>
        <button type="button" class="btn-save" @click="submit">{{ entry && Object.keys(entry).length ? 'Modifier' : 'Ajouter' }}</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue'
import { uploadToCloudinary } from '../../config/cloudinary'
import { updateEcriture, createEcriture } from '../../composables/api/apiCompta'
const props = defineProps({ show: Boolean, entry: Object })
const emit = defineEmits(['close', 'add', 'update'])
const getCurrentUser = () => {
  try {
    const user = localStorage.getItem('prostock_user')
    if (user) return JSON.parse(user).nom || ''
  } catch {}
  return ''
}
const form = ref({
  date_ecriture: '',
  type_ecriture: '',
  montant: 0,
  user: getCurrentUser(),
  categorie: '',
  moyen_paiement: '',
  statut: '',
  reference: '',
  piece_jointe: '',
  commentaire: '',
  details: ''
})
watch(
  [() => props.show, () => props.entry],
  ([show, entry]) => {
    if (show) {
      if (entry && Object.keys(entry).length) {
        Object.assign(form.value, {
          date_ecriture: entry.date_ecriture || '',
          type_ecriture: entry.type_ecriture || '',
          montant: entry.montant || 0,
          user: entry.user || getCurrentUser(),
          categorie: entry.categorie || '',
          moyen_paiement: entry.moyen_paiement || '',
          statut: entry.statut || '',
          reference: entry.reference || '',
          piece_jointe: entry.piece_jointe || '',
          commentaire: entry.commentaire || '',
          details: entry.details || ''
        })
      } else {
        Object.assign(form.value, {
          date_ecriture: '',
          type_ecriture: '',
          montant: 0,
          user: getCurrentUser(),
          categorie: '',
          moyen_paiement: '',
          statut: '',
          reference: '',
          piece_jointe: '',
          commentaire: '',
          details: ''
        })
      }
    }
  },
  { immediate: true }
)
const uploading = ref(false)
async function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  uploading.value = true
  try {
    const url = await uploadToCloudinary(file)
    form.value.piece_jointe = url
  } catch (err) {
    alert('Erreur upload image')
  } finally {
    uploading.value = false
  }
}

async function submit() {
  if (uploading.value) return alert('Veuillez attendre la fin de l’upload de l’image.')
  if (props.entry && props.entry.id_compta) {
    // Modification : update
    try {
      const updated = { ...form.value };
      await updateEcriture(props.entry.id_compta, updated);
      emit('update', { ...updated, id_compta: props.entry.id_compta });
    } catch (e) {
      alert('Erreur lors de la modification');
    }
  } else {
    // Ajout : create
    emit('add', { ...form.value });
  }
  emit('close');
}
</script>

<style scoped>
/* .modal-form, .form-section, .form-row, .form-group : style.css (disposition type Produit) */
.preview-img { margin-top: 0.5rem; }
.preview-img img { max-width: 100px; max-height: 100px; border-radius: 8px; border: 1px solid #e5e7eb; }
</style>

