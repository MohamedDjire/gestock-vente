<!-- Bloc dupliqué supprimé : il ne reste qu'un seul <template>, <script setup> et <style> -->
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

