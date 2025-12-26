<template>
  <header class="topbar">
    <div style="flex:1"></div>
    <div class="topbar-actions">
      <div class="currency-select-topbar">
        <label>Devise:</label>
        <select v-model="currencyCode" class="currency-select-input" @change="handleCurrencyChange">
          <optgroup label="Afrique de l'Ouest">
            <option v-for="code in currenciesByRegion['Afrique de l\'Ouest']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Afrique Centrale">
            <option v-for="code in currenciesByRegion['Afrique Centrale']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Afrique de l'Est">
            <option v-for="code in currenciesByRegion['Afrique de l\'Est']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Afrique du Nord">
            <option v-for="code in currenciesByRegion['Afrique du Nord']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Afrique Australe">
            <option v-for="code in currenciesByRegion['Afrique Australe']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Internationales">
            <option v-for="code in currenciesByRegion['Internationales']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
          <optgroup label="Autres">
            <option v-for="code in currenciesByRegion['Autres']" :key="code" :value="code">
              {{ currencyNames[code] }} ({{ currencySymbols[code] }})
            </option>
          </optgroup>
        </select>
        <span v-if="currencyVariation" class="currency-variation" :class="{ positive: currencyVariation.isPositive, negative: !currencyVariation.isPositive }">
          {{ currencyVariation.isPositive ? '↑' : '↓' }} {{ Math.abs(currencyVariation.value).toFixed(2) }}%
        </span>
      </div>
      <span class="notif-icon">🔔</span>
      <div class="profile">
        <img :src="user?.avatar || 'https://randomuser.me/api/portraits/women/44.jpg'" alt="profile" />
        <span class="profile-name">{{ user?.nom || 'Danielle Campbell' }}</span>
      </div>
    </div>
  </header>
</template>

<script setup>
import { useStorage } from '../composables/storage/useStorage'
import { useCurrencyStore } from '../stores/currency'
import { ref, onMounted, provide, watch, computed } from 'vue'

const { getUser } = useStorage()
const user = ref(null)
const currencyStore = useCurrencyStore()

// Utiliser le store pour la devise
const currencyCode = ref(currencyStore.currency || 'XOF')

// Computed pour accéder aux données du store
const currencyNames = computed(() => currencyStore.currencyNames)
const currencySymbols = computed(() => currencyStore.currencySymbols)
const currenciesByRegion = computed(() => currencyStore.currenciesByRegion)

// Variation de la devise actuelle
const currencyVariation = computed(() => {
  const variation = currencyStore.getCurrencyVariation(currencyCode.value)
  return variation
})

// Fournir la devise aux composants enfants (pour compatibilité)
provide('selectedCurrency', currencyCode)

const handleCurrencyChange = () => {
  currencyStore.setCurrency(currencyCode.value)
}

onMounted(() => {
  user.value = getUser()
  // S'assurer que la devise du store est synchronisée
  currencyCode.value = currencyStore.currency || 'XOF'
})

// Synchroniser avec le store
watch(() => currencyStore.currency, (newCurrency) => {
  currencyCode.value = newCurrency
})
</script>

<style scoped>
/* Topbar moderne, fond blanc, arrondi, avatar, notification, recherche */
.topbar {
  display: flex;
  align-items: center;
  padding: 1rem 2.2rem;
  background: #fff;
  box-shadow: 0 2px 12px #0001;
  border-radius: 0 32px 0 0;
  min-height: 70px;
  position: fixed;
  top: 0;
  left: 280px;
  right: 0;
  z-index: 1002;
  width: calc(100vw - 280px);
  flex-shrink: 0;
}

@media (max-width: 1100px) {
  .topbar {
    left: 0;
    width: 100vw;
    border-radius: 0;
  }
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 1.7rem;
}

.currency-select-topbar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f5f6fa;
  border-radius: 12px;
  padding: 0.4rem 0.8rem;
}

.currency-select-topbar label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1a5f4a;
}

.currency-select-input {
  padding: 0.4rem 0.6rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
  color: #1f2937;
  cursor: pointer;
  transition: all 0.2s;
}

.currency-select-input:focus {
  outline: none;
  border-color: #1a5f4a;
  box-shadow: 0 0 0 3px rgba(26, 95, 74, 0.1);
}

.currency-variation {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  margin-left: 0.25rem;
}

.currency-variation.positive {
  background: #d1fae5;
  color: #065f46;
}

.currency-variation.negative {
  background: #fee2e2;
  color: #991b1b;
}
.notif-icon {
  font-size: 1.5rem;
  color: #1a5f4a;
  cursor: pointer;
}
.profile {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  background: #f5f6fa;
  border-radius: 20px;
  padding: 0.3rem 1rem;
}
.profile img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  box-shadow: 0 2px 8px #1a5f4a22;
}
.profile-name {
  font-weight: 600;
  color: #1a5f4a;
  font-size: 1.08rem;
}
</style>
