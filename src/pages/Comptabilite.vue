<template>
      <!-- Modale de confirmation de suppression -->
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
        <div class="modal-content" style="max-width:400px;" @click.stop>
          <div class="modal-header modal-header-white">
            <span class="modal-header-icon" style="color:#dc2626;">🗑️</span>
            <h3 class="modal-title-green" style="color:#dc2626;">Confirmer la suppression</h3>
            <button class="modal-close" @click="closeDeleteModal">×</button>
          </div>
          <div class="modal-body" style="padding:24px;">
            <p style="font-size:1.1rem; color:#222; margin-bottom:18px;">Voulez-vous vraiment supprimer cette écriture ?<br><b>{{ ecritureToDelete?.reference || 'Écriture' }}</b></p>
            <div class="form-actions" style="justify-content:flex-end;">
              <button class="btn-cancel" @click="closeDeleteModal">Annuler</button>
              <button class="btn-primary" style="background:#dc2626; margin-left:12px;" @click="confirmDeleteEcriture">Supprimer</button>
            </div>
          </div>
        </div>
      </div>
  <div class="compta-page compta-modern">
    <div class="compta-header-modern">
      <div class="compta-title-block">
        <h1 class="compta-title-modern">Comptabilité</h1>
        <div class="compta-actions-modern">
          <button class="btn-kpi-action" @click="openAddEcritureModal">Nouvelle écriture</button>
          <button class="btn-kpi-action export-excel" @click="exportExcel">Exporter Excel</button>
          <button class="btn-kpi-action export-pdf" @click="exportPDF">Exporter PDF</button>
        </div>
      </div>
      <div class="kpi-cards-row">
        <div class="kpi-card kpi-income">
          <div class="kpi-label">Chiffre d'affaires</div>
          <div class="kpi-value">{{ kpi.ca }}</div>
        </div>
        <div class="kpi-card kpi-expense">
          <div class="kpi-label">Dépenses</div>
          <div class="kpi-value">{{ totalDepenses.toLocaleString() }} FCFA</div>
        </div>
        <div class="kpi-card kpi-savings">
          <div class="kpi-label">Taux d'épargne</div>
          <div class="kpi-value">{{ savingsRate }}%</div>
        </div>
        <div class="kpi-card kpi-networth">
          <div class="kpi-label">Trésorerie</div>
          <div class="kpi-value">{{ kpi.tresorerie }}</div>
        </div>
      </div>
    </div>

    <div class="tabs-container">
      <button :class="['tab-btn', { active: activeTab === 'tout' }]" @click="activeTab = 'tout'">Tout</button>
      <button :class="['tab-btn', { active: activeTab === 'revenus' }]" @click="activeTab = 'revenus'">Revenus</button>
      <button :class="['tab-btn', { active: activeTab === 'depenses' }]" @click="activeTab = 'depenses'">Dépenses</button>
      <button :class="['tab-btn', { active: activeTab === 'jour' }]" @click="activeTab = 'jour'">Jour</button>
      <button :class="['tab-btn', { active: activeTab === 'semaine' }]" @click="activeTab = 'semaine'">Semaine</button>
      <button :class="['tab-btn', { active: activeTab === 'mois' }]" @click="activeTab = 'mois'">Mois</button>
      <button :class="['tab-btn', { active: activeTab === 'trimestre' }]" @click="activeTab = 'trimestre'">Trimestre</button>
      <button :class="['tab-btn', { active: activeTab === 'annee' }]" @click="activeTab = 'annee'">Année</button>
      <button :class="['tab-btn', { active: activeTab === 'rapport' }]" @click="activeTab = 'rapport'">Rapport & Graphique</button>
    </div>

    <div v-if="activeTab === 'rapport'">
      <div class="compta-graphs-row">
        <div class="compta-graph-card">
          <h3 class="graph-title">Dépenses mensuelles</h3>
          <SalesChart :ecritures="filteredEcritures" :activeTab="activeTab" />
        </div>
        <div class="compta-graph-card">
          <h3 class="graph-title">Répartition par catégorie</h3>
          <CategoryPieChart :ecritures="filteredEcritures" />
        </div>
      </div>
      <div class="rapport-summary-modern">
        <div class="summary-row">
          <div class="summary-item">
            <span class="summary-label">Total revenus</span>
            <span class="summary-value">{{ totalRevenus.toLocaleString() }} FCFA</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Total dépenses</span>
            <span class="summary-value">{{ totalDepenses.toLocaleString() }} FCFA</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Nombre d'écritures</span>
            <span class="summary-value">{{ filteredEcritures.length }}</span>
          </div>
        </div>
      </div>
    </div>
    <PageCard>
      <template #default>
        <div class="journal-section">
          <h2 class="section-title">Journal financier</h2>
          <div class="totaux-row">
            <span v-if="activeTab === 'revenus' || activeTab === 'tout'">Total revenus : <b>{{ totalRevenus.toLocaleString() }} FCFA</b></span>
            <span v-if="activeTab === 'depenses' || activeTab === 'tout'">Total dépenses : <b>{{ totalDepenses.toLocaleString() }} FCFA</b></span>
          </div>
          <div class="table-responsive">
            <table class="table journal-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Référence</th>
                  <th>Type</th>
                  <th>Montant</th>
                  <th>Moyen</th>
                  <th>Commentaire</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ecriture in paginatedEcritures" :key="ecriture.id_ecriture">
                  <td>{{ ecriture.date }}</td>
                  <td>{{ ecriture.reference }}</td>
                  <td>{{ ecriture.type }}</td>
                  <td>{{ ecriture.montant }}</td>
                  <td>{{ ecriture.moyen_paiement || '-' }}</td>
                  <td>
                    <span v-if="ecriture.commentaire">{{ ecriture.commentaire }}</span>
                    <span v-else>-</span>
                  </td>
                  <td>
                    <button class="action-btn" title="Voir" @click="viewEcriture(ecriture)">👁️</button>
                    <button class="action-btn" title="Imprimer" @click="printEcriture(ecriture)">🖨️</button>
                    <button class="action-btn" title="Dupliquer" @click="duplicateEcriture(ecriture)">📄</button>
                    <button class="action-btn" title="Supprimer" @click="deleteEcriture(ecriture)">🗑️</button>
                  </td>
                  <!-- Modale de visualisation d'écriture -->
                  <div v-if="showViewModal" class="modal-overlay modal-overlay-view" @click.self="closeViewModal">
                    <div class="modal-content large" @click.stop>
                      <div class="modal-header modal-header-white">
                        <span class="modal-header-icon" style="color:#1a5f4a; font-size:1.7rem;">👁️</span>
                        <h3 class="modal-title-green">Détail de l'écriture</h3>
                        <button class="modal-close" @click="closeViewModal">×</button>
                      </div>
                      <div class="modal-body">
                        <table class="table" style="width:100%; background:#f9fafb;">
                          <tr><th style="text-align:left; width:180px;">Date</th><td>{{ ecritureToView?.date || '-' }}</td></tr>
                          <tr><th style="text-align:left;">Référence</th><td>{{ ecritureToView?.reference || '-' }}</td></tr>
                          <tr><th style="text-align:left;">Type</th><td>{{ ecritureToView?.type || '-' }}</td></tr>
                          <tr><th style="text-align:left;">Montant</th><td>{{ ecritureToView?.montant || '-' }} FCFA</td></tr>
                          <tr><th style="text-align:left;">Moyen de paiement</th><td>{{ ecritureToView?.moyen_paiement || '-' }}</td></tr>
                          <tr><th style="text-align:left;">Commentaire</th><td>{{ ecritureToView?.commentaire || '-' }}</td></tr>
                        </table>
                        <div class="form-actions" style="justify-content:flex-end; margin-top:18px;">
                          <button class="btn-cancel" @click="closeViewModal">Fermer</button>
                        </div>
                      </div>
                    </div>
                  </div>
               
                
                </tr>
                <tr v-if="!filteredEcritures.length">
                  <td colspan="7" style="text-align:center; color:#888;">Aucune écriture pour l’instant</td>
                </tr>
              </tbody>
            </table>
            <div class="pagination-row" v-if="totalPages > 1">
              <button class="pagination-btn" :disabled="currentPage === 1" @click="currentPage--">&lt;</button>
              <span class="pagination-info">Page {{ currentPage }} / {{ totalPages }}</span>
              <button class="pagination-btn" :disabled="currentPage === totalPages" @click="currentPage++">&gt;</button>
            </div>
          </div>
        </div>
      </template>
    </PageCard>

    <!-- Modale d'ajout d'écriture -->
    <div v-if="showAddEcritureModal" class="modal-overlay" @click.self="closeAddEcritureModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header modal-header-with-icon modal-header-white">
          <div class="modal-header-start">
            <span class="modal-header-icon" style="color:#1a5f4a;">🧾</span>
            <h3 class="modal-title-green">Nouvelle écriture</h3>
          </div>
          <button class="modal-close" @click="closeAddEcritureModal">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitEcriture" class="modal-form">
            <div class="form-section">
              <h4 class="section-title">📝 Détails</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Date *</label>
                  <input v-model="form.date" type="date" required />
                  <small class="form-hint">Date de l’opération</small>
                </div>
                <div class="form-group">
                  <label>Reference *</label>
                  <input v-model="form.reference" type="text" required placeholder="Ex : Vente produit X, Achat fournisseur Y..." />
                  <small class="form-hint">Décrivez simplement l’opération (ex : Vente, Achat, Réappro...)</small>
                </div>
                <div class="form-group">
                  <label>Type *</label>
                  <select v-model="form.type" required>
                    <option value="">Sélectionner</option>
                    <option value="vente">Vente</option>
                    <option value="achat">Achat</option>
                    <option value="reapprovisionnement">Réapprovisionnement</option>
                    <option value="tresorerie">Trésorerie</option>
                    <option value="autre">Autre</option>
                  </select>
                  <small class="form-hint">Choisissez le type d’opération</small>
                </div>
              </div>
            </div>
            <div class="form-section">
              <h4 class="section-title">💸 {{ currentAide.label }}</h4>
              <div class="form-row">
                <div class="form-group" v-if="form.type === 'vente'">
                  <label>Montant encaissé *</label>
                  <input v-model.number="form.montant" type="number" required />
                  <small class="form-hint">{{ currentAide.aide }}</small>
                </div>
                <div class="form-group" v-else-if="form.type === 'achat' || form.type === 'reapprovisionnement'">
                  <label>Montant payé *</label>
                  <input v-model.number="form.montant" type="number" required />
                  <small class="form-hint">{{ currentAide.aide }}</small>
                </div>
                <div class="form-group" v-else-if="form.type === 'tresorerie'">
                  <label>Montant du mouvement *</label>
                  <input v-model.number="form.montant" type="number" required />
                  <small class="form-hint">{{ currentAide.aide }}</small>
                </div>
                <div class="form-group" v-else>
                  <label>Montant *</label>
                  <input v-model.number="form.montant" type="number" required />
                  <small class="form-hint">{{ currentAide.aide }}</small>
                </div>
              </div>
            </div>
            <div class="form-section">
              <h4 class="section-title">Informations complémentaires</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Moyen de paiement</label>
                  <select v-model="form.moyen_paiement">
                    <option value="">Sélectionner</option>
                    <option value="especes">Espèces</option>
                    <option value="cheque">Chèque</option>
                    <option value="virement">Virement</option>
                    <option value="carte">Carte bancaire</option>
                    <option value="mobile">Mobile Money</option>
                    <option value="autre">Autre</option>
                  </select>
                  <small class="form-hint">Facultatif. Précise le mode de règlement.</small>
                </div>
                <div class="form-group">
                  <label>Commentaire</label>
                  <textarea v-model="form.commentaire" rows="2" placeholder="Ajouter une note ou un commentaire..."></textarea>
                  <small class="form-hint">Facultatif. Toute information utile.</small>
                </div>
                <div class="form-group">
                  <label>Pièce jointe</label>
                  <input type="file" @change="handleFileUpload" />
                  <small class="form-hint">Facultatif. Joindre une facture ou autre document.</small>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-primary" :disabled="isSubmitting">{{ isSubmitting ? 'Enregistrement...' : 'Enregistrer' }}</button>
              <button type="button" class="btn-cancel" @click="closeAddEcritureModal">Annuler</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import CategoryPieChart from '../components/CategoryPieChart.vue'
const logoUrl = '/logo.png'; // Placez votre logo dans public/logo.png ou adaptez le chemin

// Filtres et pagination
const currentPage = ref(1);
const pageSize = 10;

const ecritures = ref([])
const activeTab = ref('tout')

const today = new Date();

const filteredEcritures = computed(() => {
  let list = ecritures.value;
  // Filtres temporels
  if (["jour","semaine","mois","trimestre","annee"].includes(activeTab.value)) {
    list = list.filter(e => {
      if (!e.date) return false;
      const d = new Date(e.date);
      if (activeTab.value === 'jour') return isSameDay(d, today);
      if (activeTab.value === 'semaine') return isSameWeek(d, today);
      if (activeTab.value === 'mois') return isSameMonth(d, today);
      if (activeTab.value === 'trimestre') return isSameQuarter(d, today);
      if (activeTab.value === 'annee') return isSameYear(d, today);
      return true;
    });
  }
  // Filtres revenus/dépenses
  if (activeTab.value === 'revenus') {
    return list.filter(e => e.type === 'vente' || (e.type === 'tresorerie' && Number(e.montant) > 0));
  }
  if (activeTab.value === 'depenses') {
    return list.filter(e => e.type === 'achat' || e.type === 'reapprovisionnement' || (e.type === 'tresorerie' && Number(e.montant) < 0));
  }
  return list;
});
// --- View écriture ---
const showViewModal = ref(false)
const ecritureToView = ref(null)
function viewEcriture(ecriture) {
  ecritureToView.value = ecriture
  showViewModal.value = true
}
function closeViewModal() {
  showViewModal.value = false
  ecritureToView.value = null
}

const totalPages = computed(() => Math.ceil(filteredEcritures.value.length / pageSize));
const paginatedEcritures = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredEcritures.value.slice(start, start + pageSize);
});

watch(filteredEcritures, () => { currentPage.value = 1; });
// Actions rapides
function printEcriture(ecriture) {
  const win = window.open('', '', 'width=800,height=700');
  const total = ecritures.value.length;
  const revenus = totalRevenus.value;
  const depenses = totalDepenses.value;
  win.document.write(`
    <html><head><title>Impression écriture</title>
    <style>
      body { font-family: Arial, sans-serif; margin: 32px; color: #1a5f4a; }
      .header { background: #1a5f4a; color: #fff; border-radius: 18px 18px 0 0; padding: 18px 32px; margin-bottom: 18px; display: flex; align-items: center; }
      .logo { width: 40px; height: 40px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; margin-right: 18px; }
      .logo-text { color: #1a5f4a; font-weight: bold; font-size: 1.2rem; }
      .header-title { font-size: 1.5rem; font-weight: bold; letter-spacing: 2px; flex: 1; }
      .entreprise-nom { font-size: 1.1rem; font-weight: 600; color: #fff; margin-left: 18px; }
      .stats { margin-top: 8px; font-size: 1.1rem; color: #fff; letter-spacing: 2px; }
      .table-section { background: #f6faf9; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px #1a5f4a11; }
      table { border-collapse: collapse; width: 100%; margin-bottom: 24px; background: #fff; border-radius: 12px; overflow: hidden; }
      th, td { border: 1px solid #e5e7eb; padding: 10px 16px; text-align: left; }
      th { background: #f9fafb; color: #134e3a; font-weight: 600; }
      .label { color: #134e3a; font-weight: 600; width: 200px; }
      .val { color: #222; }
    </style>
    </head><body>
    <div class="header">
      <div class="logo"><span class="logo-text">PS</span></div>
      <div class="header-title">Écriture comptable</div>
      <div class="entreprise-nom">${entrepriseNom}</div>
    </div>
    <div class="stats">
      Total écritures : ${total}   |   Revenus : ${revenus.toLocaleString()} FCFA   |   Dépenses : ${depenses.toLocaleString()} FCFA
    </div>
    <div class="table-section">
      <table>
        <tr><th class="label">Date</th><td class="val">${ecriture.date || '-'}</td></tr>
        <tr><th class="label">Référence</th><td class="val">${ecriture.reference || '-'}</td></tr>
        <tr><th class="label">Type</th><td class="val">${ecriture.type || '-'}</td></tr>
        <tr><th class="label">Montant</th><td class="val">${ecriture.montant || '-'} FCFA</td></tr>
        <tr><th class="label">Moyen de paiement</th><td class="val">${ecriture.moyen_paiement || '-'}</td></tr>
        <tr><th class="label">Commentaire</th><td class="val">${ecriture.commentaire || '-'}</td></tr>
        ${ecriture.piece_jointe ? `<tr><th class="label">Pièce jointe</th><td class="val"><a href="${ecriture.piece_jointe}" target="_blank">Voir le document</a></td></tr>` : ''}
      </table>
      <div style="margin-top:32px; color:#888; font-size:0.95rem;">Imprimé le ${new Date().toLocaleString()}</div>
    </div>
    </body></html>
  `);
  win.document.close();
  win.focus();
  setTimeout(() => { win.print(); win.close(); }, 300);
}

function duplicateEcriture(ecriture) {
  // Pré-remplit le formulaire d'ajout avec les valeurs de l'écriture (sauf id, date)
  form.value = {
    ...ecriture,
    id_ecriture: undefined,
    date: '',
    piece_jointe: null
  };
  showAddEcritureModal.value = true;
}

const showDeleteModal = ref(false)
const ecritureToDelete = ref(null)

function deleteEcriture(ecriture) {
  ecritureToDelete.value = ecriture;
  showDeleteModal.value = true;
}
function closeDeleteModal() {
  showDeleteModal.value = false;
  ecritureToDelete.value = null;
}
async function confirmDeleteEcriture() {
  if (!ecritureToDelete.value) return;
  try {
    await apiService.post('/api_compta_ecritures.php', { action: 'delete', id_compta: ecritureToDelete.value.id_compta });
    await loadEcritures();
    closeDeleteModal();
  } catch (e) {
    alert("Erreur lors de la suppression de l'écriture.");
  }
}
// Permettre le rechargement depuis d'autres composants (ex: PointVente)
import { ref, onMounted, computed, watch } from 'vue'
if (typeof window !== 'undefined') {
  window.reloadComptabilite = async () => {
    await loadEcritures();
  }
}
import PageCard from '../components/PageCard.vue'
import StatCard from '../components/StatCard.vue'
import SalesChart from '../components/SalesChart.vue'
import { apiService } from '../composables/Api/apiService.js'
import { uploadToCloudinary } from '../config/cloudinary.js'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { useAuthStore } from '../stores/auth.js'
import * as XLSX from 'xlsx';
const authStore = useAuthStore ? useAuthStore() : { user: { nom_entreprise: 'Nom de l’entreprise' } };
const entrepriseNom = authStore.user?.nom_entreprise || 'Nom de l’entreprise';

function exportPDF() {
  const doc = new jsPDF();
  // Header chic amélioré : logo rond + nom entreprise + fond dégradé
  doc.setFillColor(26, 95, 74);
  doc.roundedRect(0, 0, 210, 32, 0, 0, 'F');
  doc.setFillColor(255,255,255);
  doc.circle(22, 16, 8, 'F');
  doc.setTextColor(26,95,74);
  doc.setFontSize(14);
  doc.setFont('helvetica', 'bold');
  doc.text('PS', 18, 20);
  doc.setTextColor(255,255,255);
  doc.setFontSize(16);
  doc.text(entrepriseNom, 210-14, 20, { align: 'right' });

  // Titre centré, police plus grande
  doc.setFontSize(18);
  doc.setTextColor(30,30,30);
  doc.setFont('helvetica', 'bold');
  doc.text('Journal comptable', 105, 36, { align: 'center' });

  // Bloc statistiques stylé (affichage infaillible)
  const total = Number.isFinite(ecritures.value?.length) ? ecritures.value.length : 0;
  const revenus = Number.isFinite(Number(totalRevenus?.value)) ? Number(totalRevenus.value) : 0;
  const depenses = Number.isFinite(Number(totalDepenses?.value)) ? Number(totalDepenses.value) : 0;
  // Diagnostic ultime : chaque variable sur une ligne séparée
  doc.setFontSize(11);
  doc.setTextColor(60,60,60);
  doc.setFont('helvetica', 'normal');
  doc.text('Total écritures : ' + String(total), 105, 48, { align: 'center' });
  doc.text('Revenus : ' + String(revenus), 105, 54, { align: 'center' });
  doc.text('Dépenses : ' + String(depenses), 105, 60, { align: 'center' });

  // Tableau harmonisé (déclaré une seule fois)
  const rows = filteredEcritures.value.map(e => [e.date, e.reference, e.type, e.montant, e.moyen_paiement || '-', e.commentaire || '-']);
  autoTable(doc, {
    head: [['Date', 'Référence', 'Type', 'Montant', 'Moyen', 'Commentaire']],
    body: rows,
    startY: 60,
    theme: 'grid',
    styles: { fontSize: 10, cellPadding: 2, lineColor: [26, 95, 74], lineWidth: 0.2 },
    headStyles: { fillColor: [26, 95, 74], textColor: 255, fontStyle: 'bold' },
    alternateRowStyles: { fillColor: [240, 248, 245] },
    margin: { left: 14, right: 14 }
  });

  // Pied de page stylé
  const pageCount = doc.internal.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(9);
    doc.setTextColor(120,120,120);
    doc.text('ProStock - Export PDF', 14, 290);
    doc.text(`Page ${i} / ${pageCount}`, 200, 290, { align: 'right' });
    doc.text(new Date().toLocaleDateString(), 105, 290, { align: 'center' });
  }

  doc.save('journal_comptable.pdf');
}

function exportExcel() {
  const rows = filteredEcritures.value.map(e => ({
    Date: e.date,
    Référence: e.reference,
    Type: e.type,
    Montant: e.montant,
    Moyen: e.moyen_paiement || '-',
    Commentaire: e.commentaire || '-'
  }));
  const ws = XLSX.utils.json_to_sheet(rows);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Journal');
  XLSX.writeFile(wb, 'journal_comptable.xlsx');
}

const kpi = ref({ ca: '0 FCFA', benefice: '0 FCFA', tresorerie: '0 FCFA' })

// Calcul du taux d'épargne (savings rate)
const savingsRate = computed(() => {
  const revenus = totalRevenus.value
  const depenses = totalDepenses.value
  if (!revenus) return 0
  const rate = ((revenus - depenses) / revenus) * 100
  return Math.round(rate)
})
const showAddEcritureModal = ref(false)
const form = ref({ date: '', reference: '', type: '', debit: 0, credit: 0, montant: 0, moyen_paiement: '', commentaire: '', piece_jointe: null })
const isSubmitting = ref(false)

const typeAide = {
  vente: {
    label: 'Montant encaissé',
    aide: 'Saisir le montant encaissé lors de la vente.'
  },
  achat: {
    label: 'Montant payé',
    aide: 'Saisir le montant payé pour l’achat.'
  },
  reapprovisionnement: {
    label: 'Montant du réapprovisionnement',
    aide: 'Saisir le montant total du réapprovisionnement.'
  },
  tresorerie: {
    label: 'Montant du mouvement',
    aide: 'Saisir le montant du dépôt ou retrait.'
  },
  autre: {
    label: 'Montant',
    aide: 'Saisir le montant de l’opération.'
  }
}

const currentAide = computed(() => typeAide[form.value.type] || typeAide.autre)

function isSameDay(date1, date2) {
  return date1.getFullYear() === date2.getFullYear() && date1.getMonth() === date2.getMonth() && date1.getDate() === date2.getDate();
}
function isSameWeek(date1, date2) {
  const d1 = new Date(date1);
  const d2 = new Date(date2);
  const firstDayOfWeek = d => {
    const day = d.getDay();
    return new Date(d.getFullYear(), d.getMonth(), d.getDate() - day);
  };
  return firstDayOfWeek(d1).getTime() === firstDayOfWeek(d2).getTime() && d1.getFullYear() === d2.getFullYear();
}
function isSameMonth(date1, date2) {
  return date1.getFullYear() === date2.getFullYear() && date1.getMonth() === date2.getMonth();
}
function isSameQuarter(date1, date2) {
  return date1.getFullYear() === date2.getFullYear() && Math.floor(date1.getMonth() / 3) === Math.floor(date2.getMonth() / 3);
}
function isSameYear(date1, date2) {
  return date1.getFullYear() === date2.getFullYear();
}

// ...existing code...

// ...existing code...

const totalRevenus = computed(() =>
  ecritures.value.filter(e => e.type === 'vente' || (e.type === 'tresorerie' && Number(e.montant) > 0))
    .reduce((sum, e) => sum + Number(e.montant), 0)
)
const totalDepenses = computed(() =>
  ecritures.value.filter(e => e.type === 'achat' || e.type === 'reapprovisionnement' || (e.type === 'tresorerie' && Number(e.montant) < 0))
    .reduce((sum, e) => sum + Math.abs(Number(e.montant)), 0)
)

function openAddEcritureModal() {
  showAddEcritureModal.value = true
}
function closeAddEcritureModal() {
  showAddEcritureModal.value = false
}

async function loadEcritures() {
  try {
    const id_entreprise = authStore.user?.id_entreprise ?? authStore.enterpriseId ?? 1
    if (!id_entreprise) {
      ecritures.value = []
      return
    }
    const res = await apiService.get(`/api_compta_ecritures.php?id_entreprise=${id_entreprise}`)
    console.log('[DEBUG API] Réponse brute écritures:', res)
    // Mapping backend -> frontend pour chaque écriture
    ecritures.value = (res || []).map(e => ({
      ...e,
      id_compta: e.id_compta, // <-- mapping explicite
      date: e.date_ecriture,
      type: e.type_ecriture,
      piece_jointe: e.piece_jointe,
      reference: e.reference,
      montant: e.montant,
      moyen_paiement: e.moyen_paiement,
      commentaire: e.commentaire,
      debit: e.debit,
      credit: e.credit
    }))
    console.log('[DEBUG FRONT] Tableau écritures.value:', ecritures.value)
    // Calcul KPI (exemple simplifié)
    let ca = 0, benefice = 0, tresorerie = 0
    ecritures.value.forEach(e => {
      ca += Number(e.type === 'vente' ? e.montant : 0)
      benefice += Number(e.type === 'vente' ? e.montant - (e.cout || 0) : 0)
      tresorerie += Number(e.credit || 0) - Number(e.debit || 0)
    })
    kpi.value = {
      ca: ca.toLocaleString() + ' FCFA',
      benefice: benefice.toLocaleString() + ' FCFA',
      tresorerie: tresorerie.toLocaleString() + ' FCFA'
    }
  } catch (e) {
    // Gérer l'erreur
  }
}

onMounted(loadEcritures)

function handleFileUpload(e) {
  const file = e.target.files[0]
  form.value.piece_jointe = file || null
}

async function submitEcriture() {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  try {
    // Détermination automatique du crédit/débit selon le type
    let credit = 0, debit = 0;
    const montant = Number(form.value.montant) || 0;
    if (form.value.type === 'vente' || (form.value.type === 'tresorerie' && montant > 0)) {
      credit = montant;
      debit = 0;
    } else if (form.value.type === 'achat' || form.value.type === 'reapprovisionnement' || (form.value.type === 'tresorerie' && montant < 0)) {
      credit = 0;
      debit = Math.abs(montant);
    }
    // Upload Cloudinary si pièce jointe
    let urlPiece = ''
    if (form.value.piece_jointe) {
      urlPiece = await uploadToCloudinary(form.value.piece_jointe)
    }
    // Préparer la donnée à envoyer
    const id_entreprise = authStore.user?.id_entreprise ?? authStore.enterpriseId ?? null;
    if (!id_entreprise) {
      isSubmitting.value = false;
      alert('Entreprise non identifiée. Veuillez vous reconnecter.');
      return;
    }
    const data = {
      ...form.value,
      url_piece_jointe: urlPiece,
      id_entreprise,
      credit,
      debit
    }
    await apiService.post('/api_compta_ecritures.php', data)
    closeAddEcritureModal()
    await loadEcritures()
  } catch (e) {
    alert("Erreur lors de l'enregistrement de l'écriture.")
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<style scoped>
  .compta-graphs-row {
    display: flex;
    gap: 32px;
    margin: 0 32px 32px 32px;
    flex-wrap: wrap;
  }
  .compta-graph-card {
    flex: 1 1 320px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 2px 12px #1a5f4a11;
    padding: 1.5rem 1.2rem 1.2rem 1.2rem;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 0;
  }
  .graph-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #5b21b6;
    margin-bottom: 1rem;
  }
  .rapport-summary-modern {
    margin: 0 32px 32px 32px;
    background: linear-gradient(90deg, #e6f4f1 0%, #f6eaff 100%);
    border-radius: 18px;
    padding: 2rem 1.5rem;
    box-shadow: 0 2px 8px #1a5f4a11;
  }
  .summary-row {
    display: flex;
    gap: 32px;
    justify-content: space-between;
    flex-wrap: wrap;
  }
  .summary-item {
    flex: 1 1 180px;
    min-width: 180px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px #1a5f4a11;
    padding: 1.2rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 0;
  }
  .summary-label {
    font-size: 1rem;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 0.3rem;
  }
  .summary-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1a5f4a;
  }
  .table.journal-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    margin: 0;
    table-layout: fixed;
    box-shadow: 0 2px 12px #1a5f4a11;
  }
  .table.journal-table th, .table.journal-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #ede9fe;
    vertical-align: middle;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .table.journal-table th {
    background: #f6eaff;
    font-weight: 700;
    text-align: left;
    color: #5b21b6;
    font-size: 1.05rem;
  }
  .table.journal-table tr:last-child td {
    border-bottom: none;
  }
  .table.journal-table tbody tr:hover {
    background: #f3e8ff44;
    transition: background 0.18s;
  }
   /* Modale view harmonisée */
.modal-overlay-view {
  background: rgba(0,0,0,0.08) !important;
  align-items: flex-start !important;
  padding-top: 60px !important;
}
/* Pagination */
.pagination-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  margin: 12px 0 0 0;
}
.pagination-btn {
  background: #f6faf9;
  color: #1a5f4a;
  border: 1.5px solid #1a5f4a22;
  border-radius: 6px;
  padding: 4px 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.pagination-info {
  font-size: 1rem;
  color: #2563eb;
  font-weight: 600;
}
/* Actions rapides */
.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  margin: 0 2px;
  padding: 2px 6px;
  transition: background 0.15s;
  border-radius: 4px;
}
.action-btn:hover {
  background: #e5e7eb;
}

.compta-modern {
  min-height: 100vh;
  background: #f7fafc;
  max-width: 100vw;
  padding: 0 0 32px 0;
}
.compta-header-modern {
  background: linear-gradient(90deg, #e6f4f1 0%, #f6eaff 100%);
  border-radius: 0 0 32px 32px;
  box-shadow: 0 4px 24px #1a5f4a11;
  padding: 32px 32px 0 32px;
  margin-bottom: 32px;
}
.compta-title-block {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
}
.compta-title-modern {
  font-size: 2.2rem;
  font-weight: 800;
  color: #1a5f4a;
  margin-bottom: 0.5rem;
}
.compta-actions-modern {
  display: flex;
  gap: 12px;
}
.btn-kpi-action {
  background: #fff;
  color: #1a5f4a;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  padding: 0.7rem 1.5rem;
  box-shadow: 0 2px 8px #1a5f4a11;
  transition: background 0.18s, color 0.18s;
  cursor: pointer;
}
.btn-kpi-action:hover {
  background: #e6f4f1;
  color: #134e3a;
}
.btn-kpi-action.export-excel {
  background: #ede9fe;
  color: #5b21b6;
}
.btn-kpi-action.export-excel:hover {
  background: #d1c4e9;
}
.btn-kpi-action.export-pdf {
  background: #ffe4ef;
  color: #c026d3;
}
.btn-kpi-action.export-pdf:hover {
  background: #fbcfe8;
}
.kpi-cards-row {
  display: flex;
  gap: 24px;
  margin-top: 32px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}
.kpi-card {
  flex: 1 1 180px;
  min-width: 180px;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 12px #1a5f4a11;
  padding: 1.5rem 1.2rem 1.2rem 1.2rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  transition: box-shadow 0.18s;
  position: relative;
}
.kpi-card .kpi-label {
  font-size: 1.05rem;
  color: #6b7280;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.kpi-card .kpi-value {
  font-size: 2.1rem;
  font-weight: 800;
  color: #1a5f4a;
  letter-spacing: 1px;
}
.kpi-card.kpi-income {
  background: linear-gradient(120deg, #e0f2fe 60%, #e6f4f1 100%);
  color: #2563eb;
}
.kpi-card.kpi-expense {
  background: linear-gradient(120deg, #f3e8ff 60%, #f6eaff 100%);
  color: #a21caf;
}
.kpi-card.kpi-savings {
  background: linear-gradient(120deg, #fce7f3 60%, #f6eaff 100%);
  color: #c026d3;
}
.kpi-card.kpi-networth {
  background: linear-gradient(120deg, #e0f2fe 60%, #e6f4f1 100%);
  color: #059669;
}
.section-title {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #1a5f4a;
}
.table-responsive {
  overflow-x: auto;
}
.table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  margin: 0;
  table-layout: fixed;
}
.table th, .table td {
  padding: 10px 12px;
  border-bottom: 1px solid #eee;
  vertical-align: middle;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.table th {
  background: #f9fafb;
  font-weight: 600;
  text-align: left;
}
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.2);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  z-index: 1000;
  padding-top: 60px;
}
.modal-header.modal-header-with-icon {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 32px 18px 24px;
}
.modal-header-white {
  background: #fff;
  color: #1a5f4a;
  border-radius: 18px 18px 0 0;
  padding: 18px 32px 18px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(26,95,74,0.08);
  border-bottom: 2px solid #1a5f4a22;
}
.modal-title-green {
  color: #1a5f4a;
  font-weight: 700;
  font-size: 1.25rem;
  margin: 0;
}
.modal-content.large {
  max-width: 700px;
  width: 98vw;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 8px 32px rgba(26,95,74,0.10);
  padding: 0 0 24px 0;
}
.modal-body {
  padding: 24px 32px 0 32px;
}
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.form-section {
  background: #f6faf9;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  padding: 1.5rem;
  border: 2px solid rgba(26, 95, 74, 0.13);
}
.form-row {
  display: flex;
  gap: 18px;
  flex-wrap: wrap;
}
.form-group {
  display: flex;
  flex-direction: column;
  flex: 1 1 180px;
  min-width: 0;
  max-width: 100%;
}
.form-group label {
  font-weight: 500;
  margin-bottom: 6px;
  color: #1a5f4a;
}
.form-hint {
  font-size: 0.875rem;
  color: #666;
  margin-top: 4px;
}
.form-actions {
  display: flex;
  gap: 16px;
  margin-top: 18px;
}
.btn-primary {
  background: #1a5f4a;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.25rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-primary:hover {
  background: #134e3a;
}
.btn-cancel {
  background: #e5e7eb;
  color: #222;
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
.tabs-container {
  display: flex;
  gap: 12px;
  margin: 0 0 18px 0;
  padding: 0 32px;
}
.tab-btn {
  background: #f6faf9;
  color: #1a5f4a;
  border: 1.5px solid #1a5f4a22;
  border-radius: 8px 8px 0 0;
  padding: 8px 22px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.tab-btn.active {
  background: #fff;
  color: #2563eb;
  border-bottom: 2.5px solid #2563eb;
}
.totaux-row {
  display: flex;
  gap: 32px;
  margin-bottom: 12px;
  font-size: 1.1rem;
  color: #1a5f4a;
}
@media (max-width: 700px) {
  .modal-content.large {
    padding: 0 0 16px 0;
  }
  .modal-header-white {
    padding: 14px 12px 14px 12px;
  }
  .modal-body {
    padding: 18px 12px 0 12px;
  }
  .form-row {
    flex-direction: column;
    gap: 10px;
  }
}
</style>

