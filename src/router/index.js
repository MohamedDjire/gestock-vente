import { createRouter, createWebHistory } from 'vue-router'
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
    component: Login
  },
  {
    path: '/signup',
    name: 'SignUp',
    component: SignUp
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard
  }
]


const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard : accès au dashboard seulement si connecté
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('prostock_token');
  if (to.name === 'Dashboard' && !token) {
    next({ name: 'Login' });
  } else if (to.name === 'Login' && token) {
    next({ name: 'Dashboard' });
  } else {
    next();
  }
});

export default router
