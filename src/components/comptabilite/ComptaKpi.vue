<template>
  <div class="kpi-row">
    <div v-for="(value, key, idx) in kpi" :key="key" class="kpi-card kpi-animated">
      <div class="kpi-icon" :class="'kpi-icon-' + key">
        <span v-if="key==='chiffreAffaires'">💰</span>
        <span v-else-if="key==='totalVentes'">🧾</span>
        <span v-else-if="key==='totalAchats'">📦</span>
        <span v-else-if="key==='totalDepenses'">💸</span>
        <span v-else-if="key==='benefice'">📈</span>
        <span v-else>🔢</span>
      </div>
      <div class="kpi-label">{{ key }}</div>
      <div class="kpi-value">{{ value }}</div>
    </div>
  </div>
</template>
<script setup>
defineProps({ kpi: Object })
</script>
<style scoped>
.kpi-row {
  display: flex;
  gap: 2rem;
  margin-bottom: 2rem;
  justify-content: center;
}
.kpi-card {
  background: linear-gradient(120deg,#e0f2fe 0%,#f8fafc 100%);
  border-radius: 16px;
  box-shadow: 0 4px 16px #0001;
  padding: 1.5rem 2.5rem;
  min-width: 160px;
  text-align: center;
  transition: transform 0.18s, box-shadow 0.18s;
  position: relative;
}
.kpi-card:hover {
  transform: translateY(-6px) scale(1.04);
  box-shadow: 0 8px 32px #bae6fd99;
}
.kpi-icon {
  font-size: 2.2rem;
  margin-bottom: 0.4rem;
  display: block;
}
.kpi-label {
  font-size: 1.05rem;
  color: #1a5f4a;
  font-weight: 600;
  margin-bottom: 0.5rem;
  text-transform: capitalize;
}
.kpi-value {
  font-size: 1.35rem;
  font-weight: 800;
  color: #2563eb;
}
.kpi-animated {
  animation: fadeInUp 0.7s cubic-bezier(.4,2,.6,1) both;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: none; }
}
</style>
