<template>
  <div class="main-layout">
    <Sidebar />
    <div class="main-content">
      <div class="dashboard-wrapper">
        <Topbar />
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
  transition: box-shadow 0.2s;
}

.page-content {
  padding: 1.5rem 2.5rem 2.5rem 2.5rem;
  padding-top: calc(1.5rem + 70px);
  width: 100%;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  background: #f6f7fa;
  margin-top: 0;
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