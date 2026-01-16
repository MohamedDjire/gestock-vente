<template>
  <header class="compta-header">
    <div class="header-actions">
      <select v-model="period" @change="$emit('period-change', period)" class="btn-compta" style="min-width:150px;">
        <option value="jour">Jour</option>
        <option value="semaine">Semaine</option>
        <option value="mois">Mois</option>
        <option value="personnalisee">Période personnalisée</option>
      </select>
      <template v-if="period === 'personnalisee'">
        <input type="date" v-model="dateDebut" @change="emitCustomPeriod" class="btn-compta" style="width:140px;" />
        <span>au</span>
        <input type="date" v-model="dateFin" @change="emitCustomPeriod" class="btn-compta" style="width:140px;" />
      </template>
      <button class="btn-compta" @click="$emit('add')"><span class="btn-icon">➕</span> Ajouter</button>
      <button class="btn-compta" @click="$emit('export')"><span class="btn-icon">⬇️</span> Exporter</button>
      <button class="btn-compta" @click="$emit('refresh')"><span class="btn-icon">🔄</span> Actualiser</button>
    </div>
  </header>
</template>
<script setup>
import { computed, ref, watch } from 'vue'
const props = defineProps({ period: String })
const emit = defineEmits(['period-change', 'add', 'export', 'refresh'])
const period = computed({
  get: () => props.period,
  set: v => emit('period-change', v)
})
const dateDebut = ref('')
const dateFin = ref('')

function emitCustomPeriod() {
  if (period.value === 'personnalisee' && dateDebut.value && dateFin.value) {
    emit('period-change', { type: 'personnalisee', debut: dateDebut.value, fin: dateFin.value })
  }
}

// Réinitialise les dates si on change de période
watch(period, v => {
  if (v !== 'personnalisee') {
    dateDebut.value = ''
    dateFin.value = ''
  }
})
</script>
<style scoped>
.compta-header { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 2rem; }
.header-actions { display: flex; gap: 1.2rem; }
</style>
