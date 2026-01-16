<template>
  <div>
    <h2>Rapports & Statistiques</h2>
    <canvas id="chart-rapports" style="max-width:100%;height:320px;"></canvas>
    <slot />
  </div>
</template>
<script setup>
import { onMounted, watch, toRefs } from 'vue'
import Chart from 'chart.js/auto'
const props = defineProps({ stats: Array })
let chartInstance = null
function renderChart() {
  const ctx = document.getElementById('chart-rapports')
  if (!ctx) return
  if (chartInstance) chartInstance.destroy()
  const labels = props.stats?.map(s => s.label || s.mois || s.date) || []
  const data = props.stats?.map(s => s.valeur || s.chiffreAffaires || s.total || 0) || []
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Chiffre d\'Affaires',
        data,
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37,99,235,0.1)',
        tension: 0.3,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#1a5f4a',
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { x: { grid: { display: false } }, y: { beginAtZero: true } }
    }
  })
}
onMounted(renderChart)
watch(() => props.stats, renderChart)
</script>
<style scoped>
h2 { color: #1a5f4a; margin-bottom: 1rem; }
</style>
