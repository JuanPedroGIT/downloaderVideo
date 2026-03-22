<template>
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-card__icon">📝</div>
      <h1 class="login-card__title">Create Account</h1>

      <div v-if="success" class="success-msg">
        Registration successful! Check your email to verify your account before logging in.
      </div>

      <template v-else>
        <p v-if="error" class="error-msg">{{ error }}</p>
        <form class="login-form" @submit.prevent="doRegister">
          <label class="field">
            <span>Email</span>
            <input v-model="form.email" type="email" autocomplete="email" required />
          </label>
          <label class="field">
            <span>Username</span>
            <input v-model="form.username" type="text" autocomplete="username" minlength="3" maxlength="64" required />
          </label>
          <label class="field">
            <span>Password</span>
            <input v-model="form.password" type="password" autocomplete="new-password" minlength="8" required />
          </label>
          <label class="field">
            <span>Confirm Password</span>
            <input v-model="form.confirmPassword" type="password" autocomplete="new-password" minlength="8" required />
          </label>
          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? 'Creating account…' : 'Register' }}
          </button>
        </form>
        <p class="auth-links">Already have an account? <RouterLink to="/login">Log in</RouterLink></p>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const form    = ref({ email: '', username: '', password: '', confirmPassword: '' })
const loading = ref(false)
const error   = ref('')
const success = ref(false)

async function doRegister() {
  error.value = ''
  if (form.value.password !== form.value.confirmPassword) {
    error.value = 'Passwords do not match.'
    return
  }
  loading.value = true
  try {
    const res  = await fetch('/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: form.value.email, username: form.value.username, password: form.value.password }),
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) {
      error.value = data.error || `Registration failed (HTTP ${res.status})`
    } else {
      success.value = true
    }
  } catch {
    error.value = 'Network error. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
.login-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 1.25rem; text-align: center; }
.login-card__icon { font-size: 2.5rem; }
.login-card__title { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, var(--grad-start), var(--grad-mid), var(--grad-end)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.login-form { display: flex; flex-direction: column; gap: 1rem; text-align: left; }
.field { display: flex; flex-direction: column; gap: 0.35rem; font-size: 0.85rem; }
.field span { color: var(--text-secondary); font-weight: 500; }
.field input { background: var(--bg-input, #1e1e2e); border: 1px solid var(--border); border-radius: var(--radius); padding: 0.55rem 0.75rem; color: var(--text-primary); font-size: 0.9rem; transition: border-color 0.2s; }
.field input:focus { outline: none; border-color: var(--grad-start); }
.btn-primary { background: linear-gradient(135deg, var(--grad-start), var(--grad-end)); color: #fff; border: none; border-radius: var(--radius); padding: 0.65rem 1.2rem; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.error-msg { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; border-radius: var(--radius); padding: 0.65rem 1rem; font-size: 0.85rem; text-align: left; }
.success-msg { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; border-radius: var(--radius); padding: 0.65rem 1rem; font-size: 0.9rem; text-align: left; }
.auth-links { font-size: 0.85rem; color: var(--text-secondary); }
.auth-links a { color: var(--grad-start); text-decoration: none; }
.auth-links a:hover { text-decoration: underline; }
</style>
