<template>
  <AuthCard icon="🔐" title="Media Tools">
    <BaseAlert :message="error" type="error" />

    <form class="auth-form" @submit.prevent="doLogin">
      <BaseInput
        v-model="credentials.username"
        label="Username"
        autocomplete="username"
        required
      />
      <BaseInput
        v-model="credentials.password"
        label="Password"
        type="password"
        autocomplete="current-password"
        required
      />
      <BaseButton type="submit" :loading="loading" class="full-width">
        Log in
      </BaseButton>
    </form>

    <p class="auth-links">
      <RouterLink to="/forgot-password">Forgot password?</RouterLink>
    </p>
    <p class="auth-links">
      Don't have an account? <RouterLink to="/register">Register</RouterLink>
    </p>
  </AuthCard>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import AuthCard   from '../components/layout/AuthCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseInput  from '../components/ui/BaseInput.vue'
import BaseAlert  from '../components/ui/BaseAlert.vue'

const { login } = useAuth()
const route  = useRoute()
const router = useRouter()

const credentials = ref({ username: '', password: '' })
const loading = ref(false)
const error   = ref('')

async function doLogin() {
  loading.value = true
  error.value   = ''
  try {
    await login(credentials.value)
    router.push(route.query.redirect || '/')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-form { display: flex; flex-direction: column; gap: 1rem; text-align: left; }
.full-width { width: 100%; }
.auth-links { font-size: 0.85rem; color: var(--text-secondary); }
.auth-links a { color: var(--grad-start); text-decoration: none; }
.auth-links a:hover { text-decoration: underline; }
</style>
