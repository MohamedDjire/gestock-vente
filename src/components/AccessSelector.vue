<template>
  <div
    class="access-section"
    @click.stop
    @submit.prevent
    @keydown.stop.prevent="blockEnter"
  >
    <div class="access-summary">
      Accès à {{ selected.length }} {{ label }}
      <button class="btn-toggle" type="button" @click.stop="showList = !showList">
        {{ showList ? 'Masquer' : 'Gérer les accès' }}
      </button>
    </div>
    <div v-if="showList" class="access-list">
      <div v-if="!items || items.length === 0" style="padding:1rem;color:#888;">Chargement...</div>
      <template v-else>
        <div class="access-actions" @click.stop>
          <button type="button" @click.stop="selectAll">Tout sélectionner</button>
          <button type="button" @click.stop="deselectAll">Tout désélectionner</button>
        </div>
        <div class="access-checkboxes" @click.stop>
          <label v-for="item in items" :key="item.id" class="access-checkbox">
            <input type="checkbox" v-model="selected" :value="item.id" />
            {{ item.nom }}
          </label>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue'

const props = defineProps({
  items: { type: Array, required: true }, // [{id, nom}]
  modelValue: { type: Array, default: () => [] },
  label: { type: String, default: 'éléments' }
})
const emit = defineEmits(['update:modelValue'])

const selected = ref([...props.modelValue])
const showList = ref(false)

watch(selected, (val) => {
  emit('update:modelValue', val)
})
watch(() => props.modelValue, (val) => {
  if (JSON.stringify(val) !== JSON.stringify(selected.value)) {
    selected.value = [...val]
  }
})

function selectAll() {
  selected.value = props.items.map(i => i.id)
}
function deselectAll() {
  selected.value = []
}

function blockEnter(e) {
  if (e.key === 'Enter') {
    e.preventDefault()
    e.stopPropagation()
    return false
  }
}
</script>

<style scoped>
.access-section {
  margin: 1.2rem 0;
  background: #f3f4f6;
  border-radius: 10px;
  padding: 1rem 1.2rem;
}
.access-summary {
  font-weight: 600;
  color: #218c6a;
  display: flex;
  align-items: center;
  gap: 1.2rem;
}
.btn-toggle {
  background: #e5e7eb;
  border: none;
  border-radius: 6px;
  padding: 0.3rem 0.8rem;
  font-size: 0.98rem;
  cursor: pointer;
}
.access-list {
  margin-top: 1rem;
}
.access-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.7rem;
}
.access-checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: 0.7rem 1.2rem;
  max-height: 220px;
  overflow-y: auto;
}
.access-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5em;
  font-size: 0.98rem;
  background: #fff;
  border-radius: 6px;
  padding: 0.2rem 0.7rem;
}
</style>
