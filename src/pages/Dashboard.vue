<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <div class="dashboard-wrapper">
        <Topbar />
        <div class="dashboard-content">
          <h2 class="dashboard-title">Overview</h2>
          <div class="table-row">
            <SalesTable />
          </div>
          <div class="stats-row">
            <StatCard 
              title="Product sold" 
              :value="'25.1k'" 
              :variation="1.1" 
              icon="https://cdn-icons-png.flaticon.com/512/1040/1040230.png" />
            <StatCard 
              title="Total Profit" 
              :value="'$2,435k'" 
              :variation="-3.5" 
              icon="https://cdn-icons-png.flaticon.com/512/1828/1828884.png" />
            <StatCard 
              title="Total No. of Claim" 
              :value="'3.5M'" 
              :variation="1.1" 
              icon="https://cdn-icons-png.flaticon.com/512/747/747376.png" />
            <StatCard 
              title="New Customer" 
              :value="'43.5k'" 
              :variation="1.1" 
              icon="https://cdn-icons-png.flaticon.com/512/747/747376.png" />
          </div>
          <div class="dashboard-bottom-row">
            <div class="chart-row">
              <SalesChart />
            </div>
            <div class="team-block">
              <div class="team-card">
                <div class="team-title">Sales team target</div>
                <div class="team-progress">82% <span class="team-achieved">Achieved</span></div>
                <div class="team-avatars">
                  <img src="https://randomuser.me/api/portraits/women/44.jpg" class="team-avatar" />
                  <img src="https://randomuser.me/api/portraits/men/32.jpg" class="team-avatar" />
                  <img src="https://randomuser.me/api/portraits/women/68.jpg" class="team-avatar" />
                  <span class="team-more">+4</span>
                </div>
                <div class="team-queue">Cleared Queue <span class="team-queue-value">1.4k</span> <span class="team-queue-variation">+15%</span></div>
              </div>
            </div>
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
  width: 100vw;
  background: #f3f4f8;
  overflow-x: auto;
  font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
}
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
  overflow: auto;
  margin-left: 250px;
  max-width: 100vw;
}
.dashboard-wrapper {
  background: #fff;
  border-radius: 0 32px 32px 0;
  box-shadow: 0 8px 32px 0 rgba(26,95,74,0.10);
  min-height: 100vh;
  width: 100%;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow-x: auto;
  transition: box-shadow 0.2s;
}
.dashboard-content {
  flex: 1;
  padding: 2.5rem 2.5rem 0 2.5rem;
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
  width: 100%;
  box-sizing: border-box;
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
  flex: 2 1 340px;
  width: 100%;
  min-width: 320px;
  display: flex;
  align-items: stretch;
}
.team-block {
  flex: 1 1 320px;
  display: flex;
  align-items: stretch;
  justify-content: flex-end;
  min-width: 320px;
}
.team-card {
  background: linear-gradient(135deg, #1a5f4a 80%, #218c6a 100%);
  border-radius: 24px;
  box-shadow: 0 6px 24px 0 rgba(26,95,74,0.10);
  padding: 2rem 2rem 1.5rem 2rem;
  min-width: 260px;
  max-width: 400px;
  color: #fff;
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
}
.team-progress {
  font-size: 2.1rem;
  font-weight: 800;
  margin-bottom: 0.2rem;
  color: #fff;
}
.team-achieved {
  font-size: 1rem;
  font-weight: 600;
  margin-left: 0.5rem;
  color: #b6f7d6;
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
  background: #fff2;
  color: #fff;
  font-weight: 700;
  border-radius: 50%;
  padding: 0.3em 0.7em;
  font-size: 1rem;
}
.team-queue {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
}
.team-queue-value {
  font-size: 1.1rem;
  font-weight: 700;
  margin-left: 0.5em;
  color: #ffe082;
}
.team-queue-variation {
  font-size: 1rem;
  font-weight: 600;
  margin-left: 0.5em;
  color: #b6f7d6;
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
