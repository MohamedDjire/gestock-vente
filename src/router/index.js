import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
import Login from '../views/Login.vue'

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
import PaiementForfait from '../pages/PaiementForfait.vue'

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
    component: () => import('../views/SignUp.vue'),
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
  },
  {
    path: '/paiement-forfait',
    name: 'PaiementForfait',
    component: PaiementForfait,
    meta: { requiresAuth: true }
  },
  {
    path: '/comptabilite',
    name: 'Comptabilite',
    component: () => import('../pages/Comptabilite.vue'),
      meta: { requiresAuth: true, requiresCompta: true }
  },
  {
    path: '/historique-ventes',
    name: 'HistoriqueVentes',
    component: () => import('../pages/HistoriqueVentes.vue'),
    meta: { requiresAuth: true, requiresCompta: true }
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

  // Si déjà connecté, empêcher l'accès à login/signup → rediriger vers Dashboard (plus de blocage forfait)
  if ((to.name === 'Login' || to.name === 'SignUp') && authStore.isAuthenticated) {
    next({ name: 'Dashboard' });
    return;
  }
  // Si la route nécessite l'authentification et que l'utilisateur n'est pas connecté
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' });
    return;
  }
  // Ne plus bloquer l'accès aux pages pour admin sans forfait : ils peuvent naviguer partout,
  // un bandeau ou message les incitera à souscrire depuis Gestion du compte.
  // Si la route nécessite les droits admin et que l'utilisateur n'est pas admin
  if (to.meta.requiresAdmin && authStore.isAuthenticated) {
    const userRole = authStore.userRole?.toLowerCase();
    if (userRole !== 'admin' && userRole !== 'superadmin') {
      next({ name: 'Dashboard' });
    } else {
      next();
    }
  }
  // Si la route nécessite l'accès comptabilité et que l'utilisateur n'est pas autorisé
  else if (to.meta.requiresCompta && authStore.isAuthenticated) {
    const userRole = authStore.userRole?.toLowerCase();
    const isAdmin = userRole === 'admin' || userRole === 'superadmin';
    const hasCompta = isAdmin || authStore.user?.acces_comptabilite === true || authStore.user?.acces_comptabilite === 1;
    if (!hasCompta) {
      next({ name: 'Dashboard' });
    } else {
      next();
    }
  }
  else {
    next();
  }
});

export default router
