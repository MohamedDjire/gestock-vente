import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
import Login from '../views/Login.vue'
import SignUp from '../views/SignUp.vue'
import Dashboard from '../pages/Dashboard.vue'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Garde de navigation : vérification de l'authentification et expiration du token
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  // Vérifier l'expiration du token avant chaque navigation
  if (authStore.token && authStore.isTokenExpired()) {
    console.warn('⚠️ Token expiré lors de la navigation, déconnexion...');
    authStore.logout();
    // Si on essaie d'accéder à une route protégée, rediriger vers login
    if (to.meta.requiresAuth) {
      next({ name: 'Login' });
    } else {
      next();
    }
    return;
  }

  // Si la route nécessite l'authentification et que l'utilisateur n'est pas connecté
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Rediriger vers login pour se connecter
    next({ name: 'Login' });
  } else {
    // Permettre l'accès à toutes les autres routes
    // Login et SignUp restent accessibles même si connecté (pas de redirection automatique)
    next();
  }
});

export default router
