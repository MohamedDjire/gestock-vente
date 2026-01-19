<template>
  <nav class="sidebar" :class="{ 'sidebar-compact': $route.name === 'Ventes' }">
    <div class="logo">
      <span class="logo-icon">🌥️</span>
      <span class="logo-text">PROSTOCK</span>
    </div>
    <ul class="menu">
      <li v-for="item in menuItems" :key="item.route">
        <router-link 
          :to="item.route" 
          class="menu-link" 
          active-class="active-link" 
          exact-active-class="active-link"
          :title="item.name"
        >
          <span class="icon" v-if="item.icon">{{ item.icon }}</span>
          <span class="menu-text">{{ item.name }}</span>
          <span class="tooltip" v-if="$route.name === 'Ventes'">{{ item.name }}</span>
        </router-link>
      </li>
    </ul>
    <div class="upgrade-box">
      <button 
        class="upgrade-btn" 
        @click="showLogoutModal = true"
        :title="$route.name === 'Ventes' ? 'Déconnexion' : ''"
      >
        <span class="upgrade-btn-text">Déconnexion</span>
        <span class="tooltip" v-if="$route.name === 'Ventes'">Déconnexion</span>
      </button>
    </div>
    <div v-if="showLogoutModal" class="modal-overlay" @click.self="showLogoutModal = false">
      <div class="modal-content confirmation-modal" @click.stop>
        <div class="modal-header modal-header-with-icon">
          <div class="modal-header-start">
            <span class="modal-header-icon">⚠️</span>
            <h3>Déconnexion</h3>
          </div>
          <button @click="showLogoutModal = false" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir vous déconnecter&nbsp;?</p>
        </div>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showLogoutModal = false">Annuler</button>
          <button class="btn-danger" @click="logout">Se déconnecter</button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const $route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const showLogoutModal = ref(false)

const isAdmin = computed(() => {
  const role = authStore.userRole?.toLowerCase()
  return role === 'admin' || role === 'superadmin'
})

const menuItems = computed(() => {
  const items = [
    { name: 'Tableau de bord', route: '/dashboard', icon: '📊' },
    { name: 'Produits & Stocks', route: '/products', icon: '📦' },
    { name: 'Entrepôts', route: '/entrepot', icon: '🏭' },
    { name: 'Points de Vente', route: '/point-vente', icon: '🏪' },
    { name: 'Ventes', route: '/ventes', icon: '🛒' },
    { name: 'Comptabilité', route: '/comptabilite', icon: '💰' },
    { name: 'Clients', route: '/clients', icon: '🧑‍💼' },
    { name: 'Fournisseurs', route: '/fournisseurs', icon: '🏢' },
    { name: 'Journal', route: '/journal', icon: '📝' },
    { name: 'Paramètres', route: '/parametres', icon: '⚙️' }
  ]
  
  // Ajouter le lien "Gestion du Compte" uniquement pour les admins
  if (isAdmin.value) {
    items.push({ name: 'Gestion du Compte', route: '/gestion-compte', icon: '👑' })
  }
  
  return items
})

import { logJournal } from '../composables/useJournal.js'

async function logout() {
  // Récupérer l'utilisateur avant suppression
  const userStr = localStorage.getItem('prostock_user')
  let userEmail = ''
  try {
    if (userStr) userEmail = JSON.parse(userStr)?.email || ''
  } catch {}
  // Journaliser la déconnexion
  await logJournal({
    user: userEmail,
    action: 'Déconnexion',
    details: 'Déconnexion manuelle'
  })
  localStorage.clear();
  showLogoutModal.value = false;
  router.push('/login').then(() => {
    window.location.reload();
  });
}
</script>

<style scoped>
/* Modale : styles de base dans style.css */
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
  transition: width 0.3s ease;
}

.sidebar.sidebar-compact {
  width: 80px;
  padding: 1rem 0.5rem;
}

.sidebar.sidebar-compact .logo-text {
  display: none;
}

.sidebar.sidebar-compact .menu-link {
  justify-content: center;
  padding: 0.8em;
  position: relative;
}

.sidebar.sidebar-compact .menu-link .menu-text {
  display: none;
}

.sidebar.sidebar-compact .menu-link .icon {
  font-size: 1.5rem;
  margin: 0;
}

.sidebar.sidebar-compact .menu-link .tooltip {
  position: absolute;
  left: calc(100% + 10px);
  background: #111827;
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.sidebar.sidebar-compact .menu-link .tooltip::before {
  content: '';
  position: absolute;
  right: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 6px solid transparent;
  border-right-color: #111827;
}

.sidebar.sidebar-compact .menu-link:hover .tooltip {
  opacity: 1;
}

.sidebar.sidebar-compact .upgrade-btn {
  position: relative;
  padding: 0.5em;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidebar.sidebar-compact .upgrade-btn-text {
  display: none;
}

.sidebar.sidebar-compact .upgrade-btn::before {
  content: '🚪';
  font-size: 1.2rem;
}

.sidebar.sidebar-compact .upgrade-btn .tooltip {
  position: absolute;
  left: calc(100% + 10px);
  background: #111827;
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.sidebar.sidebar-compact .upgrade-btn .tooltip::before {
  content: '';
  position: absolute;
  right: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 6px solid transparent;
  border-right-color: #111827;
}

.sidebar.sidebar-compact .upgrade-btn:hover .tooltip {
  opacity: 1;
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
  position: relative;
}
.menu-link:hover {
  background: #218c6a;
  color: #ffe082;
}

.menu-link.active-link,
.menu-link.router-link-active.router-link-exact-active {
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
