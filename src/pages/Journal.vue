<template>
  <div class="journal-offset">
    <div class="journal-header">
      <h1 class="journal-title">Journal des mouvements</h1>
      <p class="journal-subtitle">Historique de toutes les actions effectuées</p>
    </div>
    <div class="table-container">
      <table class="journal-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Utilisateur</th>
            <th>Action</th>
            <th>Détails</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="empty-state">
              <span>Chargement des données...</span>
            </td>
          </tr>
          <tr v-else-if="error">
            <td colspan="4" class="empty-state error-state">
              <span class="error-icon">⚠️</span>
              <span>{{ error }}</span>
            </td>
          </tr>
          <tr v-else-if="journalEntries.length === 0">
            <td colspan="4" class="empty-state">
              <span class="empty-icon">📋</span>
              <span>Aucun mouvement trouvé</span>
            </td>
          </tr>
          <tr v-else v-for="(entry, index) in journalEntries" :key="index" class="data-row">
            <td class="date-cell">{{ entry.date }}</td>
            <td class="user-cell">
              <div class="user-badge">
                <span class="user-avatar">{{ entry.user?.charAt(0)?.toUpperCase() }}</span>
                {{ entry.user }}
              </div>
            </td>
            <td class="action-cell">
              <span class="action-badge">{{ entry.action }}</span>
            </td>
            <td class="details-cell">{{ entry.details }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiJournal from '../composables/api/apiJournal';

const journalEntries = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchJournal = async () => {
  loading.value = true;
  try {
    journalEntries.value = await apiJournal.getJournal();
  } catch (e) {
    error.value = 'Erreur lors du chargement du journal';
  } finally {
    loading.value = false;
  }
};

onMounted(fetchJournal);
</script>

<style scoped>
.journal-header {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f1f3;
  flex-shrink: 0;
}

.journal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
  letter-spacing: -0.02em;
}

.journal-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
}

.table-container {
  flex: 1;
  overflow: auto;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  min-height: 0;
  
}

.journal-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  table-layout: fixed;
}

.journal-table thead {
  position: sticky;
  top: 0;
  z-index: 10;
}

.journal-table th {
  background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
  padding: 0.75rem 0.875rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.journal-table th:nth-child(1) { width: 15%; }
.journal-table th:nth-child(2) { width: 25%; }
.journal-table th:nth-child(3) { width: 20%; }
.journal-table th:nth-child(4) { width: 40%; }

.journal-table td {
  padding: 0.75rem 0.875rem;
  font-size: 0.8125rem;
  color: #334155;
  border-bottom: 1px solid #f1f5f9;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.data-row {
  transition: all 0.2s ease;
}

.data-row:hover {
  background: #f8fafc;
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

.user-cell {
  font-weight: 500;
}

.user-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  overflow: hidden;
}

.user-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  color: white;
  font-size: 0.625rem;
  font-weight: 600;
  flex-shrink: 0;
}

.action-badge {
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

.details-cell {
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.empty-state {
  padding: 2.5rem 1rem !important;
  text-align: center;
  color: #94a3b8;
  font-size: 0.875rem;
  white-space: normal !important;
}

.empty-state > * {
  display: block;
  margin: 0.5rem auto;
}

.empty-icon {
  font-size: 2rem;
  opacity: 0.5;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state {
  color: #dc2626;
}

.error-icon {
  font-size: 1.75rem;
}

@media (max-width: 768px) {
  .journal-title {
    font-size: 1.25rem;
  }
  
  .journal-subtitle {
    font-size: 0.75rem;
  }
  
  .journal-table th,
  .journal-table td {
    padding: 0.5rem 0.625rem;
    font-size: 0.75rem;
  }
  
  .user-badge {
    gap: 0.375rem;
  }
  
  .user-avatar {
    width: 22px;
    height: 22px;
    font-size: 0.5625rem;
  }
}


.journal-offset {
  margin-left: 250px;
}
</style>