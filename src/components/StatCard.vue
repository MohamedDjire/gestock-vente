<template>
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" v-if="icon">
        <img v-if="isImage" :src="icon" alt="icon" />
        <span v-else class="stat-emoji">{{ icon }}</span>
      </span>
      <span class="stat-title">{{ title }}</span>
    </div>
    <div class="stat-value">{{ value }}</div>
    <div class="stat-footer" v-if="variation !== null && variation !== undefined" :class="variationColor">
      <span v-if="variation > 0">+{{ variation }}%</span>
      <span v-else>{{ variation }}%</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
// Props pour chaque StatCard
const props = defineProps({
  title: String,
  value: [String, Number],
  variation: Number,
  icon: String,
})

const isImage = computed(() => {
  if (!props.icon) return false
  return props.icon.startsWith('http') || props.icon.startsWith('data:') || props.icon.includes('/')
})

// Couleur de variation
const variationColor = computed(() => {
  if (props.variation > 0) return 'green';
  if (props.variation < 0) return 'red';
  return '';
});
</script>

<style scoped>
/* Fond vert, texte clair */
.stat-card {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 6px 24px 0 rgba(26,95,74,0.08);
  padding: 1.3rem 1.3rem 1rem 1.3rem;
  flex: 1 1 180px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 200px;
  max-width: 400px;
  transition: box-shadow 0.2s;
  color: #222;
  border: 1.5px solid #f0f0f0;
}
.stat-header {
  display: flex;
  align-items: center;
  gap: 1em;
  margin-bottom: 0.5rem;
}
.stat-title {
  color: #1a5f4a;
  font-weight: 700;
  font-size: 1.35rem;
  letter-spacing: 0.01em;
}
.stat-icon {
  font-size: 2.1rem;
  display: flex;
  align-items: center;
}
.stat-icon img {
  width: 36px;
  height: 36px;
  object-fit: contain;
  filter: brightness(1.1);
}
.stat-emoji {
  font-size: 1.5rem;
}
.stat-value {
  color: #222;
  font-size: 2.1rem;
  font-weight: 800;
  margin-bottom: 0.3rem;
}
.stat-footer {
  font-size: 1.1rem;
  font-weight: 600;
}
.stat-footer.green {
  color: #218c6a;
}
.stat-footer.red {
  color: #e14b4b;
}
</style>
