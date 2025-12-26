<template>
  <nav class="sidebar">
    <div class="logo">
      <span class="logo-icon">🌥️</span>
      <span class="logo-text">PROSTOCK</span>
    </div>
    <ul class="menu">
      <li v-for="item in menuItems" :key="item.name" :class="{ active: item.route === $route.path }">
        <router-link :to="item.route" class="menu-link">
          <span class="icon" v-if="item.icon">{{ item.icon }}</span>
          {{ item.name }}
        </router-link>
      </li>
    </ul>
    <div class="upgrade-box">
      <button class="upgrade-btn" @click="showLogoutModal = true">Déconnexion</button>
    </div>
    <div v-if="showLogoutModal" class="modal-overlay" @click.self="showLogoutModal = false">
      <div class="modal-logout">
        <div class="modal-title">Déconnexion</div>
        <div class="modal-message">Êtes-vous sûr de vouloir vous déconnecter&nbsp;?</div>
        <div class="modal-actions">
          <button class="btn-secondary" @click="showLogoutModal = false">Annuler</button>
          <button class="btn-primary" @click="logout">Se déconnecter</button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const $route = useRoute()
const router = useRouter()
const showLogoutModal = ref(false)

const menuItems = [
  { name: 'Tableau de bord', route: '/dashboard', icon: '📊' },
  { name: 'Produits & Stocks', route: '/products', icon: '📦' },
  { name: 'Entrepôts', route: '/entrepot', icon: '🏭' },
  { name: 'Ventes', route: '/ventes', icon: '🛒' },
  { name: 'Comptabilité', route: '/compta', icon: '💰' },
  { name: 'Clients', route: '/clients', icon: '🧑‍💼' },
  { name: 'Fournisseurs', route: '/fournisseurs', icon: '🏢' },
  { name: 'Journal', route: '/journal', icon: '📝' },
  { name: 'Paramètres', route: '/settings', icon: '⚙️' }
]

function logout() {
  localStorage.clear();
  showLogoutModal.value = false;
  router.push('/login').then(() => {
    window.location.reload();
  });
}
</script>

<style scoped>
/* Modale de déconnexion */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-logout {
  background: #fff;
  color: #1a5f4a;
  border-radius: 16px;
  padding: 2.5rem 2rem 2rem 2rem;
  min-width: 320px;
  max-width: 90vw;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
  display: flex;
  flex-direction: column;
  align-items: center;
}
.modal-title {
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 1rem;
}
.modal-message {
  font-size: 1.05rem;
  margin-bottom: 2rem;
  text-align: center;
}
.modal-actions {
  display: flex;
  gap: 1.2rem;
}
.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  padding: 0.6em 1.3em;
  cursor: pointer;
  transition: background 0.18s;
}
.btn-secondary:hover {
  background: #e5e7eb;
}
.btn-primary {
  background: #1a5f4a;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  padding: 0.6em 1.3em;
  cursor: pointer;
  transition: background 0.18s;
}
.btn-primary:hover {
  background: #145040;
}
/* Sidebar moderne, fond vert foncé, logo jaune, icônes */
.sidebar {
  width: 280px;
  background: #1a5f4a;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.2rem 0.8rem 0.8rem 0.8rem;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  border-radius: 0 32px 32px 0;
  z-index: 10;
  flex-shrink: 0;
  justify-content: space-between;
}
.logo {
  display: flex;
  align-items: center;
  font-weight: bold;
  margin-bottom: 1rem;
  flex-shrink: 0;
}
.logo-icon {
  font-size: 1.6rem;
  margin-right: 0.5rem;
}
.logo-text {
  font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
  font-size: 1.5rem;
  font-weight: 900;
  letter-spacing: 0.03em;
  color: #ffe082;
}
.menu {
  list-style: none;
  padding: 0;
  width: 100%;
  margin-bottom: 2.5rem;
}
.menu-link {
  display: flex;
  align-items: center;
  gap: 0.7em;
  color: #fff;
  text-decoration: none;
  font-size: 1.1rem;
  font-weight: 600;
  padding: 0.8em 1.2em;
  border-radius: 12px;
  transition: background 0.18s, color 0.18s;
}
.menu-link:hover,
.menu .active .menu-link {
  background: #218c6a;
  color: #ffe082;
}
.icon {
  font-size: 1.3em;
}
.upgrade-box {
  margin-top: 0.5rem;
  background: #ffe08222;
  border-radius: 12px;
  padding: 0.8em 0.8em;
  text-align: center;
  width: 100%;
  flex-shrink: 0;
}
.upgrade-illustration {
  font-size: 1.6em;
  margin-bottom: 0.2em;
}
.upgrade-text {
  font-size: 0.9em;
  font-weight: 700;
  margin-bottom: 0.4em;
}
.upgrade-btn {
  background: #ffe082;
  color: #1a5f4a;
  border: none;
  border-radius: 8px;
  padding: 0.5em 1em;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.18s;
  width: 100%;
}
.upgrade-btn:hover {
  background: #fff7c2;
}
</style>
