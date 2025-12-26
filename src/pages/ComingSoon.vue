<template>
  <div class="coming-soon-page">
    <div class="coming-soon">
      <div class="coming-soon-icon">🚧</div>
      <h1>{{ title }}</h1>
      <p>Cette fonctionnalité sera bientôt disponible.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const titles = {
  '/ventes': 'Ventes',
  '/factures': 'Facturation',
  '/compta': 'Comptabilité',
  '/contacts': 'Clients & Fournisseurs',
  '/logs': 'Journal d\'activités',
  '/settings': 'Paramètres'
}

const title = computed(() => titles[route.path] || 'Page en construction')
</script>

<style scoped>
.coming-soon-page {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.coming-soon {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  text-align: center;
}

.coming-soon-icon {
  font-size: 5rem;
  margin-bottom: 2rem;
}

.coming-soon h1 {
  font-size: 2.5rem;
  color: #1a5f4a;
  margin-bottom: 1rem;
}

.coming-soon p {
  font-size: 1.2rem;
  color: #6b7280;
}
</style>

