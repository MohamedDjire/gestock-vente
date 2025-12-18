<template>
  <div class="sales-chart">
    <h3>Sales Over the Years</h3>
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const chartData = {
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
}

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: true },
  },
  scales: {
    y: { beginAtZero: true, ticks: { stepSize: 10 } },
  },
}
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
</style>
