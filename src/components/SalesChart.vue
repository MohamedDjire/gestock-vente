<template>
  <div class="sales-chart">
    <h3>Sales Over the Years</h3>
    <div class="chart-container">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { apiService } from '../composables/Api/apiService.js'
let chartInstance = null
const chartCanvas = ref(null)

function getMonthLabel(dateStr) {
  const d = new Date(dateStr)
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0')
}

onMounted(async () => {
  const Chart = (await import('chart.js/auto')).default
  // Récupérer l'id_entreprise
  let id_entreprise = null
  const user = localStorage.getItem('prostock_user')
  if (user) {
    id_entreprise = JSON.parse(user).id_entreprise
  }
  let labels = []
  let dataVentes = []
  if (id_entreprise) {
    try {
      const res = await apiService.get('/api_vente.php?action=all')
      const list = Array.isArray(res?.data) ? res.data : (res?.data?.data && Array.isArray(res.data.data)) ? res.data.data : []
      const ventesParMois = {}
      list.forEach(e => {
        const d = e.date_vente || e.date
        if (d) {
          const m = getMonthLabel(d)
          const montant = parseFloat(e.montant ?? e.total ?? e.chiffre_affaires ?? 0) || 0
          ventesParMois[m] = (ventesParMois[m] || 0) + montant
        }
      })
      labels = Object.keys(ventesParMois).sort()
      dataVentes = labels.map(m => ventesParMois[m])
    } catch (_) {
      labels = []
      dataVentes = []
    }
  }
  if (chartInstance) chartInstance.destroy()
  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels: labels.length ? labels : ['Aucun'],
      datasets: [
        {
          label: 'Ventes',
          data: dataVentes.length ? dataVentes : [0],
          borderColor: '#1a5f4a',
          backgroundColor: 'rgba(26,95,74,0.1)',
          tension: 0.4,
          fill: true,
        }
      ],
    },
    options: {
      plugins: {
        legend: { display: true },
      },
      scales: {
        y: { beginAtZero: true }
      },
      responsive: true,
      maintainAspectRatio: false,
    },
  })
})
</script>

<style scoped>
.sales-chart {
  background: #f6faf9;
  border-radius: 24px;
  box-shadow: 0 8px 32px 0 rgba(26, 95, 74, 0.18);
  padding: 2.8rem 2.8rem 2.2rem 2.8rem;
  margin-bottom: 2.5rem;
  min-height: 260px;
  transition: box-shadow 0.2s;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  justify-content: flex-start;
  flex: 2 1 0%;
  min-width: 0;
  max-width: 100%;
  width: 100%;
  height: auto;
  border: 2px solid #1a5f4a22;
  box-sizing: border-box;
}
h3 {
  color: #1a5f4a;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}
.chart-container {
  flex: 1;
  min-height: 0;
  height: 200px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 16px 0 rgba(26, 95, 74, 0.1);
  border: 1.5px solid #1a5f4a18;
  padding: 1.2rem 1.2rem 1.2rem 1.2rem;
  box-sizing: border-box;
}
.chart-container canvas {
  width: 100% !important;
  height: 180px !important;
  max-height: 200px !important;
  min-height: 120px !important;
  background: transparent;
  z-index: 2;
}
</style>