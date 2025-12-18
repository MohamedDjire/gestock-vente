<template>
  <div class="sales-chart">
    <h3>Sales Over the Years</h3>
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
let chartInstance = null
const chartCanvas = ref(null)

onMounted(async () => {
  const Chart = (await import('chart.js/auto')).default
  if (chartInstance) chartInstance.destroy()
  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels: ['2015', '2016', '2017', '2018', '2019', '2020'],
      datasets: [
        {
          label: 'Approved',
          data: [20, 25, 30, 35, 32, 40],
          borderColor: '#1a5f4a',
          backgroundColor: 'rgba(26,95,74,0.1)',
          tension: 0.4,
          fill: true,
        },
        {
          label: 'Submitted',
          data: [15, 18, 28, 30, 28, 35],
          borderColor: '#e14b4b',
          backgroundColor: 'rgba(225,75,75,0.1)',
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      plugins: {
        legend: { display: true },
      },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 10 } },
      },
      responsive: true,
      maintainAspectRatio: false,
    },
  })
})
</script>

<style scoped>
.sales-chart {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 12px #0001;
  padding: 1.5rem 1.5rem 2rem 1.5rem;
  margin-bottom: 2rem;
  height: 270px;
}
h3 {
  color: #1a5f4a;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}
canvas {
  width: 100% !important;
  height: 180px !important;
}
</style>
