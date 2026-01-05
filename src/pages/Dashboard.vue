<template>
  <div class="dashboard-page">
    <h2 class="dashboard-title">Overview</h2>

    <div class="stats-row">
            <StatCard 
              title="Vente total" 
              :value="'25.1k'" 
              :variation="1.1" 
              icon="💰" />
            <StatCard 
              title="Vente du jour" 
              :value="'2.3k'" 
              :variation="0.8" 
              icon="📈" />
            <StatCard 
              title="Total produit" 
              :value="'1,200'" 
              :variation="0.0" 
              icon="📦" />
            <StatCard 
              title="Stocks en rupture" 
              :value="'12'" 
              :variation="-2.1" 
              icon="⚠️" />
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
    <div class="table-row">
      <SalesTable />
    </div>
  </div>
</template>

<script setup>
import StatCard from '../components/StatCard.vue'
import SalesTable from '../components/SalesTable.vue'
import SalesChart from '../components/SalesChart.vue'
import { logJournal } from '../composables/useJournal'

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
