<template>
  <nav class="sidebar">
    <div class="logo">
      <span class="logo-icon">☁️</span>
      <span class="logo-text">VENTES</span>
    </div>
    <ul class="menu">
      <li class="active"><span class="icon">🏠</span> Dashboard</li>
    
    </ul>
    <div class="upgrade-box">
      <div class="upgrade-illustration">🚀</div>
      <div class="upgrade-text">Want to upgrade</div>
      <button class="upgrade-btn">Upgrade now</button>
    </div>
  </nav>
</template>

<script setup>
// Statique pour le menu
</script>

<style scoped>
.sidebar {
  width: 250px;
  background: #1a5f4a;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem 1rem 1rem 1rem;
  height: 100vh;
  min-height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  border-radius: 0;
  z-index: 10;
  overflow-y: auto;
}
.logo {
  display: flex;
  align-items: center;
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 2rem;
}
.logo-icon {
  margin-right: 0.5rem;
}
.menu {
  list-style: none;
  padding: 0;
  width: 100%;
}
.menu li {
  display: flex;
  align-items: center;
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 0.5rem;
  cursor: pointer;
  font-size: 1.1rem;
  transition: background 0.2s;
}
.menu li.active, .menu li:hover {
  background: #fff;
  color: #1a5f4a;
}
.icon {
  margin-right: 1rem;
  font-size: 1.3rem;
}
.upgrade-box {
  background: #fff;
  color: #1a5f4a;
  border-radius: 18px;
  padding: 1.2rem 1rem;
  margin-top: auto;
  text-align: center;
  width: 100%;
  box-shadow: 0 2px 12px #0001;
}
.upgrade-illustration {
  font-size: 2.2rem;
  margin-bottom: 0.5rem;
}
.upgrade-text {
  font-weight: 500;
  margin-bottom: 0.5rem;
}
.upgrade-btn {
  background: #1a5f4a;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.2rem;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.2s;
}
.upgrade-btn:hover {
  background: #144a39;
}
</style>
