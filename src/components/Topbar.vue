<template>
  <header class="topbar">
    <div class="search-box">
      <input type="text" placeholder="Search" />
      <span class="search-icon">🔍</span>
    </div>
    <div class="topbar-actions">
      <span class="notif-icon">🔔</span>
      <div class="profile">
        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="profile" />
        <span class="profile-name">Utilisateur</span>
      </div>
    </div>
  </header>
</template>

<script setup>
// Statique pour la topbar
</script>

<style scoped>
.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem 1.5rem 2rem;
  background: #fff;
  box-shadow: 0 2px 12px #0001;
  border-radius: 0 24px 0 0;
}
.search-box {
  display: flex;
  align-items: center;
  background: #f5f6fa;
  border-radius: 10px;
  padding: 0.5rem 1rem;
}
.search-box input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 1rem;
  margin-right: 0.5rem;
}
.search-icon {
  color: #1a5f4a;
  font-size: 1.2rem;
}
.topbar-actions {
  display: flex;
  align-items: center;
  gap: 1.5rem;
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
  padding: 0.3rem 0.8rem;
}
.profile img {
  width: 32px;
  height: 32px;
  border-radius: 50%;
}
.profile-name {
  font-weight: 500;
  color: #1a5f4a;
}
</style>
