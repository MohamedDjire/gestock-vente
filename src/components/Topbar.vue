<template>
  <header class="topbar">
    <div style="flex:1"></div>
    <div class="topbar-actions">
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
import { ref, onMounted } from 'vue'
const { getUser } = useStorage()
const user = ref(null)
onMounted(() => {
  user.value = getUser()
})
</script>

<style scoped>
/* Topbar moderne, fond blanc, arrondi, avatar, notification, recherche */
.topbar {
  display: flex;
  align-items: center;
  padding: 1.5rem 2.2rem 1.5rem 2.2rem;
  background: #fff;
  box-shadow: 0 2px 12px #0001;
  border-radius: 0 32px 0 0;
  min-height: 70px;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1002;
  width: 100vw;
  max-width: 100vw;
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 1.7rem;
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
<style>
/* Ajoute un padding-top global pour compenser la topbar fixed */
.main-content, .sidebar, .router-view, main, .page-content {
  padding-top: 100px !important;
  box-sizing: border-box;
}
</style>
