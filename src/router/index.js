import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
import Login from '../views/Login.vue'
import SignUp from '../views/SignUp.vue'

import Dashboard from '../pages/Dashboard.vue'
import Products from '../pages/Products.vue'
import ComingSoon from '../pages/ComingSoon.vue'
import Clients from '../pages/Clients.vue'
import Entrepot from '../pages/Entrepot.vue'
import PointVente from '../pages/PointVente.vue'
import Journal from '../pages/Journal.vue'
import Fournisseurs from '../pages/Fournisseurs.vue'
import Settings from '../pages/Settings.vue'
import GestionCompte from '../pages/GestionCompte.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/signup',
    name: 'SignUp',
    component: SignUp,
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/products',
    name: 'Products',
    component: Products,
    meta: { requiresAuth: true }
  },
  {
    path: '/entrepot',
    name: 'Entrepot',
    component: Entrepot,
    meta: { requiresAuth: true }
  },
  {
    path: '/point-vente',
    name: 'PointVente',
    component: PointVente,
    meta: { requiresAuth: true }
  },
  {
    path: '/ventes',
    name: 'Ventes',
    component: () => import('../pages/Ventes.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/factures',
    name: 'Factures',
    component: ComingSoon,
    meta: { requiresAuth: true }
  },
  {
    path: '/compta',
    name: 'Compta',
    component: ComingSoon,
    meta: { requiresAuth: true }
  },
  {
    path: '/contacts',
    name: 'Contacts',
    component: ComingSoon,
    meta: { requiresAuth: true }
  },
  {
    path: '/logs',
    name: 'Logs',
    component: ComingSoon,
    meta: { requiresAuth: true }
  },
  {
    path: '/journal',
    name: 'Journal',
    component: Journal,
    meta: { requiresAuth: true }
  },
  {
    path: '/parametres',
    name: 'Parametres',
    component: () => import('../pages/Parametres.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: Settings,
    meta: { requiresAuth: true }
  },
  {
    path: '/clients',
    name: 'Clients',
    component: Clients,
    meta: { requiresAuth: true }
  },
  {
    path: '/fournisseurs',
    name: 'Fournisseurs',
    component: Fournisseurs,
    meta: { requiresAuth: true }
  },
  {
    path: '/gestion-compte',
    name: 'GestionCompte',
    component: GestionCompte,
    meta: { requiresAuth: true, requiresAdmin: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Garde de navigation fusionnée : expiration du token + redirection selon l'authentification
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  // Vérifier l'expiration du token avant chaque navigation
  if (authStore.token && authStore.isTokenExpired()) {
    console.warn('⚠️ Token expiré lors de la navigation, déconnexion...');
    authStore.logout();
    next({ name: 'Login' });
    return;
  }

  // Si déjà connecté, empêcher l'accès à login/signup et rediriger vers dashboard
  if ((to.name === 'Login' || to.name === 'SignUp') && authStore.isAuthenticated) {
    next({ name: 'Dashboard' });
  }
  // Si la route nécessite l'authentification et que l'utilisateur n'est pas connecté
  else if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' });
  }
  // Si la route nécessite les droits admin et que l'utilisateur n'est pas admin
  else if (to.meta.requiresAdmin && authStore.isAuthenticated) {
    const userRole = authStore.userRole?.toLowerCase();
    if (userRole !== 'admin' && userRole !== 'superadmin') {
      next({ name: 'Dashboard' });
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router
