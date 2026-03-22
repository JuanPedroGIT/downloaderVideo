import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login',           component: () => import('../pages/LoginPage.vue'),          meta: { public: true } },
    { path: '/register',        component: () => import('../pages/RegisterPage.vue'),       meta: { public: true } },
    { path: '/verify-email',    component: () => import('../pages/VerifyEmailPage.vue'),    meta: { public: true } },
    { path: '/forgot-password', component: () => import('../pages/ForgotPasswordPage.vue'), meta: { public: true } },
    { path: '/reset-password',  component: () => import('../pages/ResetPasswordPage.vue'),  meta: { public: true } },
    { path: '/',       component: () => import('../pages/HomePage.vue') },
    { path: '/video',  component: () => import('../pages/VideoPage.vue') },
    { path: '/docs',   component: () => import('../pages/DocumentPage.vue') },
    { path: '/qr',     component: () => import('../pages/QrPage.vue') },
    { path: '/admin',  component: () => import('../pages/AdminPage.vue') },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  const { isAuthenticated } = useAuth()
  if (!to.meta.public && !isAuthenticated.value) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
  if (to.path === '/login' && isAuthenticated.value) {
    return { path: '/' }
  }
})

export default router
