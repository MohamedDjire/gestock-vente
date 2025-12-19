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