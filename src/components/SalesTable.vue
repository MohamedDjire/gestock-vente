<template>
  <div class="sales-table">
    <h3 class="table-title">Top commerciaux</h3>
    <div v-if="loading" class="loading-state">Chargement...</div>
    <div v-else-if="topPointsVente.length === 0" class="empty-state">Aucun point de vente trouvé</div>
    <table v-else>
      <thead>
        <tr>
          <th>Point de Vente</th>
          <th>Ventes</th>
          <th>CA Total</th>
          <th>Ventes Aujourd'hui</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(pv, index) in topPointsVente" :key="pv.id_point_vente">
          <td class="user-cell">
            <div class="avatar-placeholder">{{ pv.nom_point_vente?.charAt(0)?.toUpperCase() || 'P' }}</div>
            {{ pv.nom_point_vente || 'Point de Vente' }}
          </td>
          <td>{{ pv.nombre_ventes || 0 }}</td>
          <td>{{ formatPrice(pv.chiffre_affaires_total || 0) }}</td>
          <td>{{ formatPrice(pv.chiffre_affaires_journalier || 0) }}</td>
          <td>
            <span :class="['badge', pv.actif ? 'badge-success' : 'badge-warning']">
              {{ pv.actif ? 'Actif' : 'Inactif' }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiService } from '../composables/Api/apiService.js'
import { useCurrency } from '../composables/useCurrency.js'

const { formatPrice } = useCurrency()
const topPointsVente = ref([])
const loading = ref(false)

const loadTopPointsVente = async () => {
  loading.value = true
  try {
    const response = await apiService.get('/api_point_vente.php?action=all')
    if (response.success && Array.isArray(response.data)) {
      // Trier par chiffre d'affaires total décroissant et prendre le top 5
      const sorted = response.data
        .map(pv => ({
          ...pv,
          chiffre_affaires_total: parseFloat(pv.chiffre_affaires_total || 0),
          nombre_ventes: parseInt(pv.nombre_ventes || 0),
          chiffre_affaires_journalier: parseFloat(pv.chiffre_affaires_journalier || 0)
        }))
        .sort((a, b) => b.chiffre_affaires_total - a.chiffre_affaires_total)
        .slice(0, 5)
      
      topPointsVente.value = sorted
    }
  } catch (error) {
    console.error('Erreur lors du chargement des top points de vente:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadTopPointsVente()
})
</script>

<style scoped>
/* Tableau moderne, fond blanc, bordures douces, largeur responsive */
.sales-table {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 6px 24px 0 rgba(26,95,74,0.10);
  padding: 2.2rem 2.2rem 2rem 2.2rem;
  flex: 2;
  font-weight: 600;
  font-size: 0.95rem;
}
.user-cell {
  display: flex;
  align-items: center;
  gap: 0.7em;
}
.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #1a5f4a22;
}
.badge.gold {
  background: linear-gradient(90deg, #ffd700 60%, #fffbe6 100%);
  color: #1a5f4a;
  border: 1px solid #ffe066;
}
.badge.silver {
  background: linear-gradient(90deg, #c0c0c0 60%, #f8f8f8 100%);
  color: #1a5f4a;
  border: 1px solid #e0e0e0;
}
.table-title {
  color: #1a5f4a;
  font-weight: 600;
  margin-bottom: 1.2rem;
  font-size: 1.2rem;
}
.sales-table table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: transparent;
}
.sales-table th, .sales-table td {
  padding: 0.8rem 1rem;
  text-align: left;
  font-size: 1rem;
  background: transparent;
}
.sales-table th {
  color: #1a5f4a;
  font-weight: 600;
  background: #f6faf9;
  border-bottom: 2px solid #e3e3e3;
}
.sales-table tr {
  border-bottom: 1px solid #e3e3e3;
  transition: background 0.2s;
}
.sales-table tr:hover {
  background: #f6faf9;
}
.badge {
  padding: 0.3em 0.8em;
  border-radius: 12px;
  font-size: 0.95em;
  font-weight: 500;
  color: #fff;
  box-shadow: 0 2px 8px 0 rgba(26,95,74,0.10);
}
.badge-success {
  background: #1a5f4a;
}
.badge-warning {
  background: #f7b731;
  color: #1a5f4a;
}
.badge-danger {
  background: #eb3b5a;
}
.avatar-cell {
  display: flex;
  align-items: center;
  gap: 0.7em;
}
.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #1a5f4a22;
}
.avatar-placeholder {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 600;
  flex-shrink: 0;
}
.loading-state, .empty-state {
  padding: 2rem;
  text-align: center;
  color: #64748b;
  font-size: 0.95rem;
}
</style>
