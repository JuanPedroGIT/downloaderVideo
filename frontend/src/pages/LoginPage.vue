<template>
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-card__icon">🔐</div>
      <h1 class="login-card__title">Media Tools</h1>
      <p v-if="error" class="error-msg">{{ error }}</p>
      <form class="login-form" @submit.prevent="doLogin">
        <label class="field">
          <span>Username</span>
          <input v-model="credentials.username" type="text" autocomplete="username" required />
        </label>
        <label class="field">
          <span>Password</span>
          <input v-model="credentials.password" type="password" autocomplete="current-password" required />
        </label>
        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Logging in…' : 'Log in' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

const { login } = useAuth()
const route  = useRoute()
const router = useRouter()

const credentials = ref({ username: '', password: '' })
const loading     = ref(false)
const error       = ref('')

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
.login-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.login-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 2rem;
  width: 100%;
  max-width: 380px;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  text-align: center;
}

.login-card__icon { font-size: 2.5rem; }

.login-card__title {
  font-size: 1.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, var(--grad-start), var(--grad-mid), var(--grad-end));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.login-form { display: flex; flex-direction: column; gap: 1rem; text-align: left; }

.field { display: flex; flex-direction: column; gap: 0.35rem; font-size: 0.85rem; }
.field span { color: var(--text-secondary); font-weight: 500; }
.field input {
  background: var(--bg-input, #1e1e2e);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0.55rem 0.75rem;
  color: var(--text-primary);
  font-size: 0.9rem;
  transition: border-color 0.2s;
}
.field input:focus { outline: none; border-color: var(--grad-start); }

.btn-primary {
  background: linear-gradient(135deg, var(--grad-start), var(--grad-end));
  color: #fff;
  border: none;
  border-radius: var(--radius);
  padding: 0.65rem 1.2rem;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.error-msg {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
  border-radius: var(--radius);
  padding: 0.65rem 1rem;
  font-size: 0.85rem;
  text-align: left;
}
</style>
