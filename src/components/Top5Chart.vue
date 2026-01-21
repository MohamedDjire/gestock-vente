<template>
  <div class="top5-chart">
    <h3>{{ title }}</h3>
    <div class="chart-container">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
let chartInstance = null
const chartCanvas = ref(null)
const props = defineProps({
  data: { type: Array, required: true }, // [{ label, value }]
  title: { type: String, default: '' },
  type: { type: String, default: 'pie' } // 'pie' ou 'bar'
})

function renderChart() {
  if (!chartCanvas.value) return
  import('chart.js/auto').then(({ default: Chart }) => {
    if (chartInstance) chartInstance.destroy()
    chartInstance = new Chart(chartCanvas.value, {
      type: props.type,
      data: {
        labels: props.data.map(d => d.label),
        datasets: [{
          data: props.data.map(d => d.value),
          backgroundColor: [
            '#1a5f4a', '#2563eb', '#dc2626', '#f59e42', '#10b981', '#6366f1', '#f43f5e', '#fbbf24', '#0ea5e9', '#a21caf'
          ],
        }]
      },
      options: {
        plugins: { legend: { display: true } },
        responsive: true,
        maintainAspectRatio: false,
      }
    })
  })
}

onMounted(renderChart)
watch(() => props.data, renderChart, { deep: true })
</script>

<style scoped>
.top5-chart {
  background: #f6faf9;
  border-radius: 24px;
  box-shadow: 0 8px 32px 0 rgba(26, 95, 74, 0.10);
  padding: 2rem 2rem 1.5rem 2rem;
  margin-bottom: 2rem;
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  max-width: 100%;
  width: 100%;
}
.chart-container {
  flex: 1;
  min-height: 0;
  height: 180px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 16px 0 rgba(26, 95, 74, 0.08);
  border: 1.5px solid #1a5f4a18;
  padding: 1.2rem;
  box-sizing: border-box;
}
.chart-container canvas {
  width: 100% !important;
  height: 160px !important;
  max-height: 180px !important;
  min-height: 100px !important;
  background: transparent;
  z-index: 2;
}
</style>
