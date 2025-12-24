<template>
  <header class="topbar">
   
    <div class="topbar-actions">
      <div class="currency-select-topbar">
        <label>Devise:</label>
        <select v-model="selectedCurrency" class="currency-select-input">
          <option value="F CFA">F CFA</option>
          <option value="EUR">EUR</option>
          <option value="USD">USD</option>
        </select>
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
import { ref, onMounted, provide, watch } from 'vue'
const { getUser } = useStorage()
const user = ref(null)
const selectedCurrency = ref('F CFA')

// Fournir la devise aux composants enfants
provide('selectedCurrency', selectedCurrency)

onMounted(() => {
  user.value = getUser()
  // Récupérer la devise depuis localStorage si disponible
  const savedCurrency = localStorage.getItem('selectedCurrency')
  if (savedCurrency) {
    selectedCurrency.value = savedCurrency
  }
})

// Sauvegarder la devise dans localStorage quand elle change
watch(selectedCurrency, (newValue) => {
  localStorage.setItem('selectedCurrency', newValue)
})
</script>

<style scoped>
/* Topbar moderne, fond blanc, arrondi, avatar, notification, recherche */
.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2.2rem 1.5rem 2.2rem;
  background: #fff;
  box-shadow: 0 2px 12px #0001;
  border-radius: 0 32px 0 0;
  min-height: 70px;
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
