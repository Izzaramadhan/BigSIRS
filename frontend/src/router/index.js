import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: { name: 'login' }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { guestOnly: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/polyclinics',
      name: 'master-data.polyclinics',
      component: () => import('../views/master-data/PolyclinicView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/procedure-categories',
      name: 'master-data.procedure-categories',
      component: () => import('../views/master-data/ProcedureCategoryView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/tariff-components',
      name: 'master-data.tariff-components',
      component: () => import('../views/master-data/TariffComponentView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/tariff-types',
      name: 'master-data.tariff-types',
      component: () => import('../views/master-data/TariffTypeView.vue'),
      meta: { requiresAuth: true }
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (!authStore.initialized) {
    await authStore.initializeAuth()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
