<template>
  <div class="main-layout">
    <Sidebar v-if="isAuthenticated" />
    <div class="main-content">
      <div class="dashboard-wrapper">
        <div v-if="isAuthenticated" class="topbar-sticky">
          <Topbar />
        </div>
        <div class="page-content">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from './Sidebar.vue'
import Topbar from './Topbar.vue'
import { useAuthStore } from '../stores/auth.js'
import { computed } from 'vue'

const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)
</script>

<style scoped>
.main-layout {
  display: flex;
  width: 100vw;
  background: #f6f7fa;
}


.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100vw - 280px);
  display: flex;
  flex-direction: column;
}

.dashboard-wrapper {
  background: #fff;
  border-radius: 0 32px 32px 0;
  box-shadow: 0 8px 32px 0 rgba(26, 95, 74, 0.10);
  width: 100%;
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
  overflow: hidden;
  position: relative;
  z-index: 1;
}

.page-content {
  padding: 0 2rem 2rem 2rem;
  padding-top: 90px;
  width: 100%;
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow-x: auto;
}

.topbar-sticky {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #fff;
}

@media (max-width: 1100px) {
  .main-content {
    margin-left: 0;
  }
  .dashboard-wrapper {
    border-radius: 0;
  }
  .page-content {
    padding: 1rem 1rem 1rem 1rem;
    padding-top: calc(1rem + 70px);
  }
}
</style>