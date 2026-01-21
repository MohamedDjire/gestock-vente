<template>
  <div class="dashboard-page">
    <h2 class="dashboard-title">
      {{ currentPointVente ? `Dashboard - ${currentPointVente.nom_point_vente}` : 'Overview' }}
    </h2>

    <div class="stats-row">
            <StatCard 
              title="Vente total" 
              :value="formatNumber(stats.venteTotal)" 
              :variation="stats.variationVenteTotal" 
              icon="💰" />
            <StatCard 
              title="Vente du jour" 
              :value="formatCurrency(stats.venteJour)" 
              :variation="stats.variationVenteJour" 
              icon="📈" />
            <StatCard 
              title="Total produit" 
              :value="formatNumber(stats.totalProduit)" 
              :variation="stats.variationProduit" 
              icon="📦" />
            <StatCard 
              title="Stocks en rupture" 
              :value="formatNumber(stats.stocksRupture)" 
              :variation="stats.variationRupture" 
              icon="⚠️" />
          </div>
          <div class="dashboard-bottom-row">
            <div class="chart-row">
              <SalesChart />
            </div>
            <div class="team-block">
              <div class="team-card">
                <div class="team-title">Objectif de l'équipe</div>
                <div class="team-progress">
                  <input 
                    v-model.number="teamTarget" 
                    type="number" 
                    class="target-input"
                    min="0"
                    max="100"
                    @change="updateTeamTarget"
                  />% 
                  <span class="team-achieved">Atteint</span>
                </div>
                <div class="team-avatars">
                  <img src="https://randomuser.me/api/portraits/women/44.jpg" class="team-avatar" />
                  <img src="https://randomuser.me/api/portraits/men/32.jpg" class="team-avatar" />
                  <img src="https://randomuser.me/api/portraits/women/68.jpg" class="team-avatar" />
                  <span class="team-more">+4</span>
                </div>
                <div class="team-queue">
                  File traitée 
                  <input 
                    v-model.number="clearedQueue" 
                    type="number" 
                    class="queue-input"
                    min="0"
                    @change="updateClearedQueue"
                  />
                  <span class="team-queue-variation">+15%</span>
                </div>
              </div>
            </div>
    </div>
    <div class="table-row">
      <SalesTable />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import StatCard from '../components/StatCard.vue'
import SalesTable from '../components/SalesTable.vue'
import SalesChart from '../components/SalesChart.vue'
import { logJournal } from '../composables/useJournal'
import { apiService } from '../composables/Api/apiService.js'


import { useCurrency } from '../composables/useCurrency.js'
import { useAuthStore } from '../stores/auth.js'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { formatPrice: formatCurrency } = useCurrency()

const currentPointVente = ref(null)
const stats = ref({
  venteTotal: 0,
  venteJour: 0,
  achatTotal: 0,
  benefice: 0,
  totalProduit: 0,
  stocksRupture: 0,
  variationVenteTotal: 0,
  variationVenteJour: 0,
  variationProduit: 0,
  variationRupture: 0
})
const loading = ref(false)
const teamTarget = ref(0)
const clearedQueue = ref(0)

const formatNumber = (value) => {
  if (value >= 1000) {
    return (value / 1000).toFixed(1) + 'k'
  }
  return value.toString()
}

const loadDashboardData = async () => {
  loading.value = true
  try {
    // Récupérer l'id_entreprise
    let id_entreprise = null
    const userStr = localStorage.getItem('prostock_user')
    if (userStr) {
      id_entreprise = JSON.parse(userStr).id_entreprise
    }
    // Charger les produits pour les stats produits/stocks (fallback Overview)
    const [productsResponse, ecrituresResponse] = await Promise.all([
      apiService.get('/api_produit.php?action=all'),
      id_entreprise ? apiService.get(`/api_compta_ecritures.php?id_entreprise=${id_entreprise}`) : Promise.resolve({ data: [] })
    ])
    let venteTotal = 0, venteJour = 0, achatTotal = 0, benefice = 0
    if (ecrituresResponse && Array.isArray(ecrituresResponse.data)) {
      const today = new Date().toISOString().slice(0, 10)
      venteTotal = ecrituresResponse.data.filter(e => e.categorie === 'Vente').reduce((acc, e) => acc + (parseFloat(e.montant) || 0), 0)
      venteJour = ecrituresResponse.data.filter(e => e.categorie === 'Vente' && e.date_ecriture === today).reduce((acc, e) => acc + (parseFloat(e.montant) || 0), 0)
      achatTotal = ecrituresResponse.data.filter(e => e.categorie === 'Achat').reduce((acc, e) => acc + (parseFloat(e.montant) || 0), 0)
      benefice = venteTotal - achatTotal
    }
    let totalProduit = 0, stocksRupture = 0
    if (productsResponse.success) {
      const products = productsResponse.data || []
      totalProduit = products.length
      stocksRupture = products.filter(p => p.statut_stock === 'rupture').length
    }
    stats.value = {
      venteTotal,
      venteJour,
      achatTotal,
      benefice,
      totalProduit,
      stocksRupture,
      variationVenteTotal: 1.1,
      variationVenteJour: 0.8,
      variationProduit: 0.0,
      variationRupture: -2.1
    }
    const userAuth = authStore.user
    let pointVenteId = route.query.point_vente
    // Si pas de point de vente dans l'URL, utiliser celui de l'utilisateur par défaut
    if (!pointVenteId && userAuth) {
      const isAdmin = userAuth.role && ['admin', 'superadmin'].includes(String(userAuth.role).toLowerCase())
      // Pour les non-admins, utiliser leur premier point de vente (obligatoire)
      if (!isAdmin && userAuth.permissions_points_vente && Array.isArray(userAuth.permissions_points_vente) && userAuth.permissions_points_vente.length > 0) {
        pointVenteId = userAuth.permissions_points_vente[0]
        // Mettre à jour l'URL sans recharger la page
        if (route.query.point_vente !== pointVenteId) {
          router.replace({ query: { ...route.query, point_vente: pointVenteId } })
        }
      }
    }
    
    if (pointVenteId) {
      // Charger les données du point de vente spécifique
      const [pointVenteResponse, statsResponse, productsResponse] = await Promise.all([
        apiService.get(`/api_point_vente.php?id_point_vente=${pointVenteId}`),
        apiService.get(`/api_point_vente.php?action=stats&id_point_vente=${pointVenteId}`),
        apiService.get('/api_produit.php?action=all')
      ])
      
      if (pointVenteResponse.success) {
        currentPointVente.value = pointVenteResponse.data
      }
      
      if (statsResponse.success) {
        const data = statsResponse.data
        // Calculer les statistiques
        const ventesJournalieres = data.ventes_journalieres || []
        const totalVentes = ventesJournalieres.reduce((sum, v) => sum + (parseFloat(v.chiffre_affaires) || 0), 0)
        const venteAujourdhui = ventesJournalieres.find(v => {
          const date = new Date(v.date)
          const today = new Date()
          return date.toDateString() === today.toDateString()
        })
        
        // Calculer les variations (comparaison avec la veille)
        const venteHier = ventesJournalieres.find(v => {
          const date = new Date(v.date)
          const yesterday = new Date()
          yesterday.setDate(yesterday.getDate() - 1)
          return date.toDateString() === yesterday.toDateString()
        })
        
        const venteJourValue = venteAujourdhui ? parseFloat(venteAujourdhui.chiffre_affaires) : 0
        const venteHierValue = venteHier ? parseFloat(venteHier.chiffre_affaires) : 0
        const variationVenteJour = venteHierValue > 0 ? ((venteJourValue - venteHierValue) / venteHierValue) * 100 : 0
        
        // Calculer les produits et stocks en rupture
        let totalProduit = 0
        let stocksRupture = 0
        if (productsResponse.success) {
          const products = productsResponse.data || []
          totalProduit = products.length
          stocksRupture = products.filter(p => (p.quantite_stock || 0) <= 0).length
        }
        
        stats.value = {
          venteTotal: totalVentes,
          venteJour: venteJourValue,
          totalProduit: totalProduit,
          stocksRupture: stocksRupture,
          variationVenteTotal: 0, // À calculer si nécessaire
          variationVenteJour: variationVenteJour,
          variationProduit: 0, // À calculer si nécessaire
          variationRupture: 0 // À calculer si nécessaire
        }
      }
    } else {
      // Pas de point de vente disponible
      currentPointVente.value = null
      let id_entreprise = userAuth?.id_entreprise || null
      if (!id_entreprise && typeof localStorage !== 'undefined') {
        try {
          const u = localStorage.getItem('prostock_user')
          if (u) id_entreprise = JSON.parse(u).id_entreprise
        } catch (_) {}
      }
      const [productsResponse, ventesResponse] = await Promise.all([
        apiService.get('/api_produit.php?action=all'),
        id_entreprise ? apiService.get('/api_vente.php?action=all') : Promise.resolve({ data: [] })
      ])
      let venteTotal = 0, venteJour = 0, achatTotal = 0
      const list = Array.isArray(ventesResponse?.data) ? ventesResponse.data : (ventesResponse?.data?.data && Array.isArray(ventesResponse.data.data)) ? ventesResponse.data.data : []
      if (list.length) {
        const today = new Date().toISOString().slice(0, 10)
        venteTotal = list.reduce((acc, e) => acc + (parseFloat(e.montant) || parseFloat(e.total) || parseFloat(e.chiffre_affaires) || 0), 0)
        venteJour = list.filter(e => (e.date_vente || e.date || '').toString().slice(0, 10) === today).reduce((acc, e) => acc + (parseFloat(e.montant) || parseFloat(e.total) || parseFloat(e.chiffre_affaires) || 0), 0)
      }
      let totalProduit = 0, stocksRupture = 0
      if (productsResponse && productsResponse.success) {
        const products = productsResponse.data || []
        totalProduit = products.length
        stocksRupture = products.filter(p => p.statut_stock === 'rupture').length
      }
      stats.value = {
        venteTotal,
        venteJour,
        totalProduit,
        stocksRupture,
        variationVenteTotal: 0,
        variationVenteJour: 0,
        variationProduit: 0,
        variationRupture: 0
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  } finally {
    loading.value = false
  }
}

// Surveiller les changements de route et de l'utilisateur
watch(() => route.query.point_vente, () => {
  loadDashboardData()
}, { immediate: false })

watch(() => authStore.user, () => {
  loadDashboardData()
}, { immediate: false, deep: true })

const updateTeamTarget = () => {
  // Sauvegarder la valeur dans localStorage
  localStorage.setItem('team_target', teamTarget.value.toString())
}

const updateClearedQueue = () => {
  // Sauvegarder la valeur dans localStorage
  localStorage.setItem('cleared_queue', clearedQueue.value.toString())
}

onMounted(() => {
  // Charger les données immédiatement au montage
  loadDashboardData()
})

// Recharger les données quand l'utilisateur change
watch(() => authStore.user, (newUser) => {
  if (newUser) {
    loadDashboardData()
  }
}, { immediate: false, deep: true })

function getJournalUser() {
  const userStr = localStorage.getItem('prostock_user');
  if (userStr) {
    try {
      const user = JSON.parse(userStr);
      return user.nom || user.email || 'inconnu';
    } catch {
      return 'inconnu';
    }
  }
  return 'inconnu';
}

// Utiliser logJournal({ user: getJournalUser(), action: 'Action', details: '...' }) pour chaque action CRUD
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  width: 100%;
  font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
  padding-bottom: 2.5rem;
}
.dashboard-title {
  font-size: 2rem;
  font-weight: 800;
  color: #1a5f4a;
  margin-bottom: 1.5rem;
  letter-spacing: 0.01em;
}
.stats-row {
  display: flex;
  gap: 2.5rem;
  margin-bottom: 0;
  flex-wrap: wrap;
}
.dashboard-bottom-row {
  display: flex;
  gap: 2.5rem;
  margin-top: 2.5rem;
  flex-wrap: wrap;
}
.chart-row {
  flex: 2 1 0%;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  display: flex;
  align-items: stretch;
}
.team-block {
  flex: 2 1 0%;
  display: flex;
  align-items: stretch;
  justify-content: flex-end;
  min-width: 0;
  max-width: 100%;
  height: 90%;
}
.team-card {
  background: #e9ecef;
  border-radius: 24px;
  box-shadow: 0 6px 24px 0 rgba(26,95,74,0.08);
  padding: 2rem 2rem 1.5rem 2rem;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  color: #1a2a2a;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  align-items: flex-start;
  box-sizing: border-box;
}
.team-title {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 0.2rem;
  color: #218c6a;
}
.team-progress {
  font-size: 2.1rem;
  font-weight: 800;
  margin-bottom: 0.2rem;
  color: #218c6a;
}
.team-achieved {
  font-size: 1rem;
  font-weight: 600;
  margin-left: 0.5rem;
  color: #1a5f4a;
}
.team-avatars {
  display: flex;
  align-items: center;
  gap: 0.5em;
  margin-bottom: 0.2rem;
}
.team-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #fff;
}
.team-more {
  background: #e0e0e0;
  color: #1a5f4a;
  font-weight: 700;
  border-radius: 50%;
  padding: 0.3em 0.7em;
  font-size: 1rem;
}
.team-queue {
  font-size: 1rem;
  font-weight: 600;
  color: #1a2a2a;
}
.team-queue-value {
  font-size: 1.1rem;
  font-weight: 700;
  margin-left: 0.5em;
  color: #218c6a;
}
.team-queue-variation {
  font-size: 1rem;
  font-weight: 600;
  margin-left: 0.5em;
  color: #b6f7d6;
}

.target-input, .queue-input {
  background: transparent;
  border: 1px solid #218c6a;
  border-radius: 6px;
  padding: 0.25rem 0.5rem;
  font-size: 1.5rem;
  font-weight: 800;
  color: #218c6a;
  width: 80px;
  text-align: center;
  margin-right: 0.5rem;
}

.target-input {
  font-size: 2.1rem;
  width: 100px;
}

.target-input:focus, .queue-input:focus {
  outline: none;
  border-color: #1a5f4a;
  box-shadow: 0 0 0 2px rgba(26, 95, 74, 0.2);
}

.queue-input {
  font-size: 1.1rem;
  width: 100px;
  margin-left: 0.5em;
}
.table-row {
  width: 100%;
  display: flex;
}
.table-row > * {
  flex: 1 1 100%;
  width: 100%;
}
@media (max-width: 1100px) {
  .main-content {
    margin-left: 0;
  }
  .dashboard-wrapper {
    border-radius: 0;
  }
  .dashboard-content {
    padding: 1.2rem 0.5rem 0 0.5rem;
    gap: 1.2rem;
  }
  .stats-row, .dashboard-bottom-row {
    gap: 1rem;
  }
}
@media (max-width: 800px) {
  .dashboard-layout {
    flex-direction: column;
  }
  .main-content {
    margin-left: 0;
    width: 100vw;
  }
  .dashboard-content {
    padding: 0.5rem 0.2rem 0 0.2rem;
  }
  .stats-row, .dashboard-bottom-row {
    flex-direction: column;
    gap: 0.7rem;
  }
  .chart-row, .team-block {
    min-width: 0;
    width: 100%;
  }
}
</style>
