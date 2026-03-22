import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import HomePage          from '../pages/HomePage.vue'
import LoginPage         from '../pages/LoginPage.vue'
import VideoPage         from '../pages/VideoPage.vue'
import DocumentPage      from '../pages/DocumentPage.vue'
import QrPage            from '../pages/QrPage.vue'
import AdminPage         from '../pages/AdminPage.vue'
import RegisterPage      from '../pages/RegisterPage.vue'
import VerifyEmailPage   from '../pages/VerifyEmailPage.vue'
import ForgotPasswordPage from '../pages/ForgotPasswordPage.vue'
import ResetPasswordPage from '../pages/ResetPasswordPage.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login',           component: LoginPage,          meta: { public: true } },
    { path: '/register',        component: RegisterPage,       meta: { public: true } },
    { path: '/verify-email',    component: VerifyEmailPage,    meta: { public: true } },
    { path: '/forgot-password', component: ForgotPasswordPage, meta: { public: true } },
    { path: '/reset-password',  component: ResetPasswordPage,  meta: { public: true } },
    { path: '/',       component: HomePage },
    { path: '/video',  component: VideoPage },
    { path: '/docs',   component: DocumentPage },
    { path: '/qr',     component: QrPage },
    { path: '/admin',  component: AdminPage },
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
