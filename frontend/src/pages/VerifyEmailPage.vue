<template>
  <AuthCard :icon="icon" title="Email Verification">
    <p v-if="loading" class="status-msg">Verifying your email…</p>
    <BaseAlert v-else :message="message" :type="success ? 'success' : 'error'" />
    <div v-if="!loading" class="verify-links">
      <RouterLink v-if="success" to="/login" class="btn-link">Go to Login</RouterLink>
      <RouterLink v-else to="/register" class="link-muted">Register again</RouterLink>
    </div>
  </AuthCard>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { authService } from '../services/authService.js'
import AuthCard  from '../components/layout/AuthCard.vue'
import BaseAlert from '../components/ui/BaseAlert.vue'

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
    const data = await authService.verifyEmail(token)
    success.value = true
    icon.value    = '✅'
    message.value = data?.message || 'Email verified! You can now log in.'
  } catch (e) {
    icon.value    = '❌'
    message.value = e.message || 'Verification failed.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.status-msg  { color: var(--text-secondary); font-size: 0.9rem; }
.verify-links { display: flex; justify-content: center; }
.btn-link {
  display: inline-block;
  background: linear-gradient(135deg, var(--grad-start), var(--grad-end));
  color: #fff;
  border-radius: var(--radius-sm);
  padding: 0.55rem 1.2rem;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
}
.link-muted { color: var(--grad-start); font-size: 0.85rem; text-decoration: none; }
.link-muted:hover { text-decoration: underline; }
</style>
