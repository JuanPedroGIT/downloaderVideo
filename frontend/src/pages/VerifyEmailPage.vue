<template>
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-card__icon">{{ icon }}</div>
      <h1 class="login-card__title">Email Verification</h1>
      <p v-if="loading" class="status-msg">Verifying your email…</p>
      <div v-else-if="success" class="success-msg">
        {{ message }}
        <div style="margin-top:1rem">
          <RouterLink to="/login" class="btn-link">Go to Login</RouterLink>
        </div>
      </div>
      <div v-else class="error-msg">
        {{ message }}
        <div style="margin-top:1rem">
          <RouterLink to="/register" class="auth-link">Register again</RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'

const route   = useRoute()
const loading = ref(true)
const success = ref(false)
const message = ref('')
const icon    = ref('⏳')

onMounted(async () => {
  const token = route.query.token || ''
  if (!token) {
    loading.value = false
    icon.value    = '❌'
    message.value = 'No verification token found in the URL.'
    return
  }
  try {
    const res  = await fetch(`/api/auth/verify-email?token=${encodeURIComponent(token)}`)
    const data = await res.json().catch(() => ({}))
    if (res.ok) {
      success.value = true
      icon.value    = '✅'
      message.value = data.message || 'Email verified! You can now log in.'
    } else {
      icon.value    = '❌'
      message.value = data.error || 'Verification failed.'
    }
  } catch {
    icon.value    = '❌'
    message.value = 'Network error. Please try again.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
.login-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; width: 100%; max-width: 380px; display: flex; flex-direction: column; gap: 1.25rem; text-align: center; }
.login-card__icon { font-size: 2.5rem; }
.login-card__title { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, var(--grad-start), var(--grad-mid), var(--grad-end)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.status-msg { color: var(--text-secondary); font-size: 0.9rem; }
.success-msg { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; border-radius: var(--radius); padding: 0.65rem 1rem; font-size: 0.9rem; }
.error-msg { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; border-radius: var(--radius); padding: 0.65rem 1rem; font-size: 0.85rem; }
.btn-link { display: inline-block; background: linear-gradient(135deg, var(--grad-start), var(--grad-end)); color: #fff; border-radius: var(--radius); padding: 0.55rem 1.2rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; }
.auth-link { color: var(--grad-start); font-size: 0.85rem; }
</style>
