<template>
  <div class="compta-offset">
    <!-- Résumé -->
    <div class="compta-header">
      <h1 class="compta-title">Comptabilité</h1>
      <p class="compta-subtitle">Suivi complet des mouvements financiers</p>
      <div class="compta-summary">
        <div class="summary-card">
          <span class="summary-icon">💰</span>
          <div>
            <div class="summary-label">Solde actuel</div>
            <div class="summary-value">{{ formatCurrency(soldeActuel) }}</div>
          </div>
        </div>
        <div class="summary-card">
          <span class="summary-icon">📈</span>
          <div>
            <div class="summary-label">Total entrées</div>
            <div class="summary-value">{{ formatCurrency(totalEntrees) }}</div>
          </div>
        </div>
        <div class="summary-card">
          <span class="summary-icon">📉</span>
          <div>
            <div class="summary-label">Total sorties</div>
            <div class="summary-value">{{ formatCurrency(totalSorties) }}</div>
          </div>
        </div>
      </div>
      <!-- Filtres -->
      <div class="compta-filters">
        <input v-model="search" type="text" placeholder="Rechercher (utilisateur, type, détails)" class="compta-search-input" />
        <input v-model="dateFrom" type="date" class="compta-date-input" />
        <input v-model="dateTo" type="date" class="compta-date-input" />
        <select v-model="typeFilter" class="compta-type-input">
          <option value="">Tous types</option>
          <option value="Entrée">Entrée</option>
          <option value="Sortie">Sortie</option>
        </select>
        <button @click="exportExcel" class="btn-primary btn-excel">Exporter Excel</button>
        <button @click="exportPDF" class="btn-primary btn-pdf">Exporter PDF</button>
      </div>
    </div>
    <!-- Tableau -->
    <div class="table-container">
      <table class="compta-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Montant</th>
            <th>Utilisateur</th>
            <th>Détails</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="empty-state">Chargement...</td>
          </tr>
          <tr v-else-if="error">
            <td colspan="6" class="empty-state error-state">{{ error }}</td>
          </tr>
          <tr v-else-if="filteredComptaEntries.length === 0">
            <td colspan="6" class="empty-state">Aucun mouvement trouvé</td>
          </tr>
          <tr v-else v-for="(entry, index) in paginatedComptaEntries" :key="index" class="data-row">
            <td class="date-cell">{{ formatComptaDate(entry.date) }}</td>
            <td>
              <span class="type-badge" :class="getTypeClass(entry.type)">{{ entry.type }}</span>
            </td>
            <td class="montant-cell">{{ formatCurrency(entry.montant) }}</td>
            <td class="user-cell">{{ entry.user }}</td>
            <td class="details-cell">
              <span class="details-text">{{ entry.details }}</span>
              <button class="btn-view" @click="openDetails(entry)" title="Voir détails"><span class="icon-view">👁️</span></button>
            </td>
            <td class="actions-cell">
              <button class="btn-action edit" @click="editEntry(entry)">✏️</button>
              <button class="btn-action delete" @click="deleteEntry(entry)">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
    <div class="pagination" v-if="pageCount > 1">
      <button @click="page--" :disabled="page === 1">Précédent</button>
      <span>Page {{ page }} / {{ pageCount }}</span>
      <button @click="page++" :disabled="page === pageCount">Suivant</button>
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
        </div>
        <div class="modal-actions">
          <button class="btn-primary" @click="closeDetails">Fermer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

// Données fictives pour démo
const comptaEntries = ref([
  { date: '2026-01-13T09:00:00', type: 'Entrée', montant: 50000, user: 'Mariam', details: 'Vente de produits' },
  { date: '2026-01-13T10:00:00', type: 'Sortie', montant: 12000, user: 'Mariam', details: 'Achat de fournitures' },
  { date: '2026-01-12T15:30:00', type: 'Entrée', montant: 30000, user: 'Admin', details: 'Paiement client' },
  { date: '2026-01-11T11:00:00', type: 'Sortie', montant: 8000, user: 'Admin', details: 'Frais de transport' },
]);
const loading = ref(false);
const error = ref(null);
const search = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const typeFilter = ref('');
const showDetailsModal = ref(false);
const selectedEntry = ref(null);

function openDetails(entry) {
  selectedEntry.value = entry;
  showDetailsModal.value = true;
}
function closeDetails() {
  showDetailsModal.value = false;
  selectedEntry.value = null;
}
function editEntry(entry) {
  alert('Fonctionnalité édition à implémenter');
}
function deleteEntry(entry) {
  if (confirm('Supprimer ce mouvement ?')) {
    const idx = comptaEntries.value.indexOf(entry);
    if (idx !== -1) comptaEntries.value.splice(idx, 1);
  }
}

const filteredComptaEntries = computed(() => {
  let entries = comptaEntries.value;
  if (search.value) {
    const s = search.value.toLowerCase();
    entries = entries.filter(entry =>
      (entry.user && entry.user.toLowerCase().includes(s)) ||
      (entry.type && entry.type.toLowerCase().includes(s)) ||
      (entry.details && entry.details.toLowerCase().includes(s))
    );
  }
  if (typeFilter.value) {
    entries = entries.filter(entry => entry.type === typeFilter.value);
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

function formatCurrency(val) {
  if (val == null) return '—';
  return val.toLocaleString('fr-FR') + ' F CFA';
}
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
  doc.text('Mouvements comptables', 105, 38, { align: 'center' });
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
.compta-offset {
  margin-left: 0;
}
.compta-header {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f1f3;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.compta-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
  letter-spacing: -0.02em;
}
.compta-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
}
.compta-summary {
  display: flex;
  gap: 1.2rem;
  margin: 1rem 0 0.5rem 0;
}
.summary-card {
  background: #f8fafc;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(26,95,74,0.07);
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 180px;
}
.summary-icon {
  font-size: 1.5rem;
}
.summary-label {
  font-size: 0.85rem;
  color: #64748b;
}
.summary-value {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1a202c;
}
.compta-filters {
  margin-top: 0.5rem;
  display: flex;
  gap: 0.5rem;
  width: 100%;
  max-width: 900px;
}
.compta-search-input {
  flex: 3;
  min-width: 220px;
  padding: 0.5rem 1.25rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  background: #f8fafc;
  transition: border-color 0.2s;
}
.compta-date-input, .compta-type-input {
  flex: 1;
  min-width: 120px;
  padding: 0.5rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  background: #f8fafc;
  transition: border-color 0.2s;
}
.compta-search-input:focus, .compta-date-input:focus, .compta-type-input:focus {
  outline: none;
  border-color: #1a5f4a;
}
.table-container {
  flex: 1;
  overflow: auto;
  border-radius: 18px;
  border: none;
  background: #fff;
  min-height: 0;
  box-shadow: 0 4px 24px 0 rgba(26, 95, 74, 0.08);
  margin-bottom: 2rem;
}
.compta-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #fff;
  table-layout: fixed;
  border-radius: 18px;
  overflow: hidden;
}
.compta-table thead tr {
  background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
  border-radius: 18px 18px 0 0;
}
.compta-table th {
  padding: 1rem 0.875rem;
  text-align: left;
  font-size: 0.85rem;
  font-weight: 700;
  color: #1a202c;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 2px solid #e2e8f0;
  background: none;
}
.compta-table th:first-child {
  border-top-left-radius: 18px;
}
.compta-table th:last-child {
  border-top-right-radius: 18px;
}
.compta-table td {
  padding: 1rem 0.875rem;
  font-size: 0.95rem;
  color: #334155;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  transition: background 0.18s;
}
.data-row {
  transition: background 0.18s;
}
.data-row:nth-child(even) td {
  background: #f8fafc;
}
.data-row:hover td {
  background: #e0e7ef;
}
.data-row:last-child td {
  border-bottom: none;
}
.date-cell {
  font-weight: 500;
  color: #64748b;
  font-family: 'SF Mono', 'Consolas', monospace;
  font-size: 0.75rem;
}
.montant-cell {
  font-weight: 700;
  color: #1a202c;
}
.type-badge {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  white-space: nowrap;
}
.type-badge.type-entree {
  background: #d1fae5;
  color: #065f46;
  border-color: #86efac;
}
.type-badge.type-sortie {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fca5a5;
}
.user-cell {
  font-weight: 500;
}
.details-cell {
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  max-width: 100%;
}
.details-cell span.details-text {
  display: inline-block;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: middle;
}
.actions-cell {
  display: flex;
  gap: 0.5rem;
}
.btn-action {
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.18s;
  box-shadow: 0 1px 2px #0001;
  font-size: 1.1rem;
}
.btn-action.edit:hover {
  background: #e0e7ef;
}
.btn-action.delete:hover {
  background: #fee2e2;
}
.icon-view {
  font-size: 1.15rem;
  color: #2563eb;
  pointer-events: none;
}
.btn-view {
  margin-left: 0.5rem;
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.18s;
  box-shadow: 0 1px 2px #0001;
}
.btn-view:hover {
  background: #e0e7ef;
}
.empty-state {
  padding: 2.5rem 1rem !important;
  text-align: center;
  color: #94a3b8;
  font-size: 0.875rem;
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
/* Modale */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 3000;
}
.modal-content {
  background: #fff;
  border-radius: 16px;
  padding: 2.2rem 2.5rem;
  min-width: 340px;
  max-width: 98vw;
  box-shadow: 0 8px 32px rgba(0,0,0,0.22);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  animation: modalIn 0.18s cubic-bezier(.4,2,.6,1) both;
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
@keyframes modalIn {
  from { opacity: 0; transform: translateY(40px) scale(0.98); }
  to { opacity: 1; transform: none; }
}
@media (max-width: 768px) {
  .compta-title { font-size: 1.25rem; }
  .compta-subtitle { font-size: 0.75rem; }
  .compta-table th, .compta-table td { padding: 0.5rem 0.625rem; font-size: 0.75rem; }
  .summary-card { min-width: 120px; padding: 0.7rem 0.7rem; }
  .compta-filters { max-width: 100vw; }
  .grid-2-cols { grid-template-columns: 1fr; gap: 1rem; }
}
</style>
