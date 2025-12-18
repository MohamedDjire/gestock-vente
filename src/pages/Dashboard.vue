<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <div class="dashboard-wrapper">
        <Topbar />
        <div class="dashboard-content">
          <div class="stats-row">
            <StatCard />
            <StatCard />
            <StatCard />
            <StatCard />
          </div>
          <div class="dashboard-row">
            <SalesTable />
            <SalesChart />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from '../components/Sidebar.vue'
import Topbar from '../components/Topbar.vue'
import StatCard from '../components/StatCard.vue'
import SalesTable from '../components/SalesTable.vue'
import SalesChart from '../components/SalesChart.vue'
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  min-width: 100vw;
  width: 100vw;
  height: 100vh;
  background: #f5f6fa;
  overflow: hidden;
}
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
  overflow: auto;
  margin-left: 250px;
}
.dashboard-wrapper {
  background: #fff;
  border-radius: 24px;
  box-shadow: 0 4px 32px #0002;
  margin: 1.5rem 1.5rem 1.5rem 2.5rem;
  padding-bottom: 2rem;
  min-height: calc(100vh - 3rem);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-width: 1500px;
}
.dashboard-content {
  padding: 2rem;
  min-height: calc(100vh - 80px);
}
.stats-row {
  display: flex;
  gap: 2rem;
  margin-bottom: 2rem;
}
.dashboard-row {
  display: flex;
  gap: 2rem;
}
</style>
