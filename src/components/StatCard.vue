<template>
  <div class="stat-card">
    <div class="stat-title">Statistique</div>
    <div class="stat-value">123</div>
    <div class="stat-footer green">+1%</div>
  </div>
</template>

<script setup>
// Statique pour l'instant
</script>

<style scoped>
.stat-card {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 12px #0001;
  padding: 1.5rem 2rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.stat-title {
  color: #1a5f4a;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.stat-value {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}
.stat-footer {
  font-size: 1rem;
  font-weight: 500;
}
.green {
  color: #1a5f4a;
}
.red {
  color: #e14b4b;
}
</style>
