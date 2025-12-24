<template>
  <div class="page-card-wrapper">
    <div class="page-card">
      <slot />
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.page-card-wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  flex: 1;
  min-height: 0;
}

.page-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  box-shadow: 
    0 1px 3px rgba(0, 0, 0, 0.05),
    0 10px 40px rgba(0, 0, 0, 0.06),
    0 0 0 1px rgba(0, 0, 0, 0.02);
  padding: 2rem 2.5rem;
  width: 100%;
  height: 100%;
  box-sizing: border-box;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(255, 255, 255, 0.8);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 0;
}

.page-card:hover {
  box-shadow: 
    0 1px 3px rgba(0, 0, 0, 0.05),
    0 20px 60px rgba(0, 0, 0, 0.08),
    0 0 0 1px rgba(0, 0, 0, 0.02);
}

@media (max-width: 768px) {
  .page-card {
    padding: 2rem 1.5rem;
    border-radius: 16px;
  }
}
</style>