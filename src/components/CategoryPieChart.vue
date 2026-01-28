<template>
  <div class="pie-chart-container">
    <canvas ref="pieCanvas"></canvas>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  ecritures: {
    type: Array,
    required: true
  }
})

const pieCanvas = ref(null)
let chartInstance = null

function getCategoryData() {
  const categories = {}
  props.ecritures.forEach(e => {
    const cat = e.type || 'Autre'
    categories[cat] = (categories[cat] || 0) + Number(e.montant || 0)
  })
  return categories
}

function renderChart() {
  const data = getCategoryData()
  const labels = Object.keys(data)
  const values = Object.values(data)
  if (chartInstance) chartInstance.destroy()
  // Palette de verts harmonisés
  const greenPalette = [
    '#1a5f4a', // vert foncé principal
    '#059669', // vert moyen
    '#10b981', // vert clair
    '#6ee7b7', // vert pastel
    '#bbf7d0', // vert très clair
    '#4ade80', // vert vif
    '#22c55e', // vert accent
    '#16a34a', // vert profond
    '#166534', // vert forêt
    '#a7f3d0'  // vert doux
  ]
  chartInstance = new Chart(pieCanvas.value, {
    type: 'pie',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: labels.map((_, i) => greenPalette[i % greenPalette.length]),
        borderWidth: 1
      }]
    },
    options: {
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            color: '#1a5f4a',
            font: { size: 13 }
          }
        }
      }
    }
  })
}

onMounted(() => {
  renderChart()
})

watch(() => props.ecritures, () => {
  renderChart()
}, { deep: true })
</script>

<style scoped>
.pie-chart-container {
  width: 100%;
  min-height: 260px;
  display: flex;
  align-items: center;
  justify-content: center;
}
canvas {
  max-width: 320px;
  max-height: 320px;
}
</style>
