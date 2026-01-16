<template>
  <div class="compta-page compta-bg">
    <section class="compta-hero">
      <div class="hero-content">
        <h1><span class="hero-icon">📊</span> Comptabilité</h1>
        <p class="hero-desc">Suivi en temps réel de la santé financière de votre entreprise</p>
      </div>
      <div class="hero-actions">
        <ComptaHeader
          @add="openAddModal"
          @export="exportData"
          @refresh="refreshData"
          :period="period"
          @period-change="setPeriod"
        />
      </div>
    </section>
    <ComptaModals :show="showModal" :entry="selectedEntry" @close="closeModal" @add="addEntry" @update="updateEntry" />
    
    <DeleteConfirmModal :show="showDeleteModal" @close="closeDeleteModal" @confirm="confirmDelete" />
          
          <div class="compta-tabs">
            <button v-for="tab in tabs" :key="tab.key" :class="['compta-tab', {active: activeTab === tab.key}]" @click="activeTab = tab.key">
              {{ tab.label }}
            </button>
          </div>
          <section class="compta-section" v-if="activeTab === 'kpi'">
            <ComptaKpi :kpi="kpiData" />
          </section>
          <section class="compta-section" v-if="activeTab === 'journal'">
            <ComptaJournal :entries="journalEntries" :filters="filters" @edit="editEntry" @delete="deleteEntry" />
          </section>
          <section class="compta-section" v-if="activeTab === 'ventes'">
            <ComptaVentes :factures="facturesClients" />
          </section>
          <section class="compta-section" v-if="activeTab === 'achats'">
            <ComptaAchats :factures="facturesFournisseurs" />
          </section>
          <section class="compta-section" v-if="activeTab === 'tresorerie'">
            <ComptaTresorerie :tresorerie="tresorerieData" />
          </section>
          <section class="compta-section" v-if="activeTab === 'rapports'">
            <ComptaRapports :stats="statsData" />
          </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ComptaHeader from '../components/comptabilite/ComptaHeader.vue'
import ComptaKpi from '../components/comptabilite/ComptaKpi.vue'
import ComptaJournal from '../components/comptabilite/ComptaJournal.vue'
import ComptaVentes from '../components/comptabilite/ComptaVentes.vue'
import ComptaAchats from '../components/comptabilite/ComptaAchats.vue'
import ComptaTresorerie from '../components/comptabilite/ComptaTresorerie.vue'
import ComptaRapports from '../components/comptabilite/ComptaRapports.vue'
import ComptaModals from '../components/comptabilite/ComptaModals.vue'
import DeleteConfirmModal from '../components/comptabilite/DeleteConfirmModal.vue'
import {
  getEcritures,
  getFacturesClients,
  getFacturesFournisseurs,
  getTresorerie,
  getRapports,
  getAuditTrail
} from '../composables/api/apiCompta'
import { updateEcriture } from '../composables/api/apiCompta'
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { useCurrency } from '../composables/useCurrency.js'
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { useAuthStore } from '../stores/auth.js'
import { apiService } from '../composables/Api/apiService.js'
import StatCard from '../components/StatCard.vue'

function closeDeleteModal() {
            showDeleteModal.value = false
            entryToDelete.value = null
          }

    async function updateEntry(entry) {
      if (!id_entreprise) {
        if (toast) toast.error('Entreprise non définie')
        return
      }
      try {
        const payload = { ...entry, id_entreprise }
        const res = await updateEcriture(entry.id_compta, payload)
        if (res && res.success) {
          await loadComptaData()
          if (toast) toast.success('Écriture modifiée !')
        } else {
          if (toast) toast.error(res?.message || 'Erreur lors de la modification')
        }
      } catch (e) {
        if (toast) toast.error(e.message || 'Erreur lors de la modification')
      }
    }

const period = ref('mois')
const kpiData = ref({})
const journalEntries = ref([])
const allJournalEntries = ref([])
const filters = ref({})
const facturesClients = ref([])
const facturesFournisseurs = ref([])
const tresorerieData = ref([])
const statsData = ref([])
const user = ref({})
const auditTrail = ref([])
const showModal = ref(false)
const showDeleteModal = ref(false)
const selectedEntry = ref(null)
const entryToDelete = ref(null)
const loading = ref(false)
const error = ref(null)

const tabs = [
  { key: 'kpi', label: 'Tableau de bord' },
  { key: 'journal', label: 'Journal comptable' },
  { key: 'ventes', label: 'Factures clients' },
  { key: 'achats', label: 'Factures fournisseurs' },
  { key: 'tresorerie', label: 'Trésorerie' },
  { key: 'rapports', label: 'Rapports & Statistiques' },
]
const activeTab = ref('journal') // Journal comptable affiché par défaut

// À adapter selon ton store utilisateur
const id_entreprise = localStorage.getItem('prostock_user') ? JSON.parse(localStorage.getItem('prostock_user')).id_entreprise : null

async function loadComptaData() {
  if (!id_entreprise) return;
  loading.value = true
  error.value = null
  try {
    const [ecritures, factClients, factFournisseurs, treso, rapports, audit] = await Promise.all([
      getEcritures(id_entreprise),
      getFacturesClients(id_entreprise),
      getFacturesFournisseurs(id_entreprise),
      getTresorerie(id_entreprise),
      getRapports(id_entreprise),
      getAuditTrail(id_entreprise)
    ])
    allJournalEntries.value = Array.isArray(ecritures.data) ? ecritures.data : []
    applyPeriodFilter()
    facturesClients.value = Array.isArray(factClients.data) ? factClients.data : []
    facturesFournisseurs.value = Array.isArray(factFournisseurs.data) ? factFournisseurs.data : []
    tresorerieData.value = Array.isArray(treso.data) ? treso.data : []
    statsData.value = Array.isArray(rapports.data) ? rapports.data : []
    auditTrail.value = Array.isArray(audit.data) ? audit.data : []
    // Calculs KPI (exemple simple)
    // Calculs KPI à partir des écritures comptables (source unique)
    const ventes = allJournalEntries.value.filter(e => e.categorie === 'Vente')
    const achats = allJournalEntries.value.filter(e => e.categorie === 'Achat')
    const depenses = allJournalEntries.value.filter(e => e.type_ecriture === 'Sortie')
    const chiffreAffaires = ventes.reduce((acc, e) => acc + (parseFloat(e.montant) || 0), 0)
    const totalVentes = ventes.length
    const totalAchats = achats.length
    const totalDepenses = depenses.reduce((acc, e) => acc + (parseFloat(e.montant) || 0), 0)
    const benefice = chiffreAffaires - totalDepenses
    kpiData.value = {
      chiffreAffaires,
      totalVentes,
      totalAchats,
      totalDepenses,
      benefice
    }
  } catch (e) {
    error.value = e.message || 'Erreur lors du chargement des données'
  } finally {
    loading.value = false
  }
}

onMounted(loadComptaData)

function applyPeriodFilter() {
  let entries = allJournalEntries.value
  if (typeof period.value === 'object' && period.value.type === 'personnalisee') {
    // Période personnalisée : filtrer entre deux dates
    const debut = period.value.debut
    const fin = period.value.fin
    entries = entries.filter(e => e.date_ecriture >= debut && e.date_ecriture <= fin)
  } else if (period.value === 'jour') {
    const today = new Date().toISOString().slice(0, 10)
    entries = entries.filter(e => e.date_ecriture === today)
  } else if (period.value === 'semaine') {
    const now = new Date()
    const first = now.getDate() - now.getDay() + 1
    const last = first + 6
    const monday = new Date(now.setDate(first)).toISOString().slice(0, 10)
    const sunday = new Date(now.setDate(last)).toISOString().slice(0, 10)
    entries = entries.filter(e => e.date_ecriture >= monday && e.date_ecriture <= sunday)
  } else if (period.value === 'mois') {
    const now = new Date()
    const month = (now.getMonth() + 1).toString().padStart(2, '0')
    const year = now.getFullYear()
    entries = entries.filter(e => e.date_ecriture && e.date_ecriture.startsWith(`${year}-${month}`))
  }
  journalEntries.value = entries
}

import { useToast } from 'vue-toastification'
const toast = useToast ? useToast() : null

function openAddModal() {
  if (!id_entreprise) {
    if (toast) toast.error('Impossible d’ajouter : entreprise non définie. Veuillez vous reconnecter.')
    return
  }
  selectedEntry.value = null
  showModal.value = true
  if (toast) toast.info('Formulaire d’ajout ouvert')
}
import { createEcriture } from '../composables/api/apiCompta'
async function addEntry(entry) {
  if (!id_entreprise) {
    if (toast) toast.error('Entreprise non définie')
    return
  }
  try {
    const payload = { ...entry, id_entreprise }
    const res = await createEcriture(payload)
    if (res && res.success) {
      await loadComptaData()
      if (toast) toast.success('Écriture ajoutée !')
    } else {
      if (toast) toast.error(res?.message || 'Erreur lors de l’ajout')
    }
  } catch (e) {
    if (toast) toast.error(e.message || 'Erreur lors de l’ajout')
  }
}
function closeModal() {
  showModal.value = false
  if (toast) toast.success('Modale fermée')
}
function exportData() {
  // Export CSV simple des écritures
  if (!journalEntries.value.length) {
    if (toast) toast.warning('Aucune donnée à exporter')
    return
  }
  const csv = [
    Object.keys(journalEntries.value[0]).join(','),
    ...journalEntries.value.map(e => Object.values(e).join(','))
  ].join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'journal_comptable.csv'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
  if (toast) toast.success('Export CSV généré !')
}
function refreshData() {
  loadComptaData()
  if (toast) toast.info('Données rafraîchies')
}
function setPeriod(p) {
  period.value = p
  applyPeriodFilter()
  if (toast) {
    if (typeof p === 'object' && p.type === 'personnalisee') {
      toast.info(`Période personnalisée : du ${p.debut} au ${p.fin}`)
    } else {
      toast.info('Période changée : ' + (typeof p === 'string' ? p : ''))
    }
  }
}
function editEntry(entry) {
  selectedEntry.value = { ...entry }
  showModal.value = true
  if (toast) toast.info('Écriture à éditer')
}
import { deleteEcriture } from '../composables/api/apiCompta'
function deleteEntry(entry) {
  entryToDelete.value = entry
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!entryToDelete.value) return
  try {
    const res = await deleteEcriture(entryToDelete.value.id_compta, id_entreprise)
    if (res && res.success) {
      await loadComptaData()
      if (toast) toast.success('Écriture supprimée !')
    } else {
      if (toast) toast.error(res?.message || 'Erreur lors de la suppression')
    }
  } catch (e) {
    if (toast) toast.error(e.message || 'Erreur lors de la suppression')
  } finally {
    showDeleteModal.value = false
    entryToDelete.value = null
  }
}
</script>

<<<<<<< HEAD
=======
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
>>>>>>> fc8e382d3fe4531c524fb054efee767a2da18f2b
