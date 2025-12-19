import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
import Login from '../views/Login.vue'
import SignUp from '../views/SignUp.vue'
import Dashboard from '../pages/Dashboard.vue'

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
  } else {
    next();
  }
});

export default router
