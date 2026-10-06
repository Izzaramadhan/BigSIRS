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
      path: '/master-data/doctors',
      name: 'master-data.doctors',
      component: () => import('../views/master-data/DoctorView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/basic-data/educations',
      name: 'master-data.educations',
      component: () => import('../views/master-data/EducationView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/basic-data/regencies',
      name: 'master-data.regencies',
      component: () => import('../views/master-data/RegencyView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/basic-data/districts',
      name: 'master-data.districts',
      component: () => import('../views/master-data/DistrictView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/basic-data/villages',
      name: 'master-data.villages',
      component: () => import('../views/master-data/VillageView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/basic-data/occupations',
      name: 'master-data.occupations',
      component: () => import('../views/master-data/OccupationView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/basic-data/insurance-types',
      name: 'master-data.insurance-types',
      component: () => import('../views/master-data/InsuranceTypeView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/service-types',
      name: 'master-data.service-types',
      component: () => import('../views/master-data/ServiceTypeView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/nutrition-care',
      name: 'master-data.nutrition-care',
      component: () => import('../views/master-data/DietTypeView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/doctor-schedules',
      name: 'master-data.doctor-schedules',
      component: () => import('../views/master-data/DoctorScheduleView.vue'),
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
    {
      path: '/master-data/procedures',
      name: 'master-data.procedures',
      component: () => import('../views/master-data/ProcedureView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/report-groups',
      name: 'master-data.report-groups',
      component: () => import('../views/master-data/ReportGroupView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/procedure-packages',
      name: 'master-data.procedure-packages',
      component: () => import('../views/master-data/ProcedurePackageView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/procedure-user-mappings',
      name: 'master-data.procedure-user-mappings',
      component: () => import('../views/master-data/ProcedureUserMappingView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/diagnoses/icd10',
      name: 'master-data.icd10',
      component: () => import('../views/master-data/Icd10CodeView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/master-data/diagnoses/icd9',
      name: 'master-data.icd9',
      component: () => import('../views/master-data/Icd9CmView.vue'),
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
