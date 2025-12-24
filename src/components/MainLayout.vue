<template>
  <div class="main-layout">
    <Sidebar />
    <div class="main-content">
      <Topbar />
      <div class="page-content">
        <slot />
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
  min-height: 100vh;
  width: 100vw;
  background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
  overflow: hidden;
  position: relative;
}

.main-layout::before {
  content: '';
  position: absolute;
  top: 0;
  left: 250px; /* Commence après la sidebar */
  right: 0;
  height: 300px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
  pointer-events: none;
  z-index: 0;
}

.main-content {
  flex: 1;
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
  width: 100%;
  height: 100%;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow-x: auto;
}

@media (max-width: 768px) {
  .main-layout::before {
    left: 0;
  }
  .page-content {
    padding: 0.5rem 0.5rem 1rem 0.5rem;
  }
}
</style>