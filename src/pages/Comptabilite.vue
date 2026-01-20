<template>
  <div class="compta-page">
    <div class="page-header">
      <h1 class="page-title">Comptabilité</h1>
      <div class="actions">
        <button class="btn-primary" @click="openAddEcritureModal">Nouvelle écriture</button>
        <button class="btn-primary" @click="exportComptaExcel" style="background:#2563eb; margin-left:8px;">Exporter Excel</button>
        <button class="btn-primary" @click="exportComptaPDF" style="background:#dc2626; margin-left:8px;">Exporter PDF</button>
      </div>
    </div>

    <div class="compta-sections">
      <PageCard title="Journal général">
        <Journal />
      </PageCard>
      <PageCard title="Trésorerie">
        <!-- Composant Trésorerie ici -->
        <div class="coming-soon">À venir</div>
      </PageCard>
      <PageCard title="Rapports & KPI">
        <!-- Composant Rapports ici -->
        <div class="coming-soon">À venir</div>
      </PageCard>
    </div>

    <!-- Modale d'ajout écriture (exemple harmonisé) -->
    <div v-if="showAddEcritureModal" class="modal-overlay" @click.self="closeAddEcritureModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h3>Nouvelle écriture comptable</h3>
          <button class="modal-close" @click="closeAddEcritureModal">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitEcriture" class="modal-form">
            <div class="form-section">
              <h4 class="section-title">📝 Détails</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Date *</label>
                  <input v-model="ecritureForm.date" type="date" required />
                </div>
                <div class="form-group">
                  <label>Libellé *</label>
                  <input v-model="ecritureForm.libelle" type="text" required />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Débit *</label>
                  <input v-model.number="ecritureForm.debit" type="number" min="0" required />
                </div>
                <div class="form-group">
                  <label>Crédit *</label>
                  <input v-model.number="ecritureForm.credit" type="number" min="0" required />
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" @click="closeAddEcritureModal">Annuler</button>
          <button type="button" class="btn-save" @click="submitEcriture">Valider</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import PageCard from '../components/PageCard.vue'
import Journal from './Journal.vue'

const showAddEcritureModal = ref(false)
const ecritureForm = ref({ date: '', libelle: '', debit: 0, credit: 0 })

function openAddEcritureModal() {
  showAddEcritureModal.value = true
}
function closeAddEcritureModal() {
  showAddEcritureModal.value = false
}
function submitEcriture() {
  // TODO: Ajouter logique d'enregistrement
  closeAddEcritureModal()
}
function exportComptaExcel() {
  // TODO: Export Excel
}
function exportComptaPDF() {
  // TODO: Export PDF
}
</script>

<style scoped>
.compta-page {
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
.compta-sections {
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}
.coming-soon {
  color: #6b7280;
  font-style: italic;
  padding: 2rem;
  text-align: center;
}
</style>


