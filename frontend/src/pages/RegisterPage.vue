<template>
  <AuthCard icon="📝" title="Create Account">
    <BaseAlert :message="success ? successMsg : error" :type="success ? 'success' : 'error'" />

    <template v-if="!success">
      <form class="auth-form" @submit.prevent="doRegister">
        <BaseInput v-model="form.email"           label="Email"            type="email"    autocomplete="email"         required />
        <BaseInput v-model="form.username"         label="Username"                         autocomplete="username"      required :minlength="3" :maxlength="64" />
        <BaseInput v-model="form.password"         label="Password"         type="password" autocomplete="new-password"  required :minlength="8" />
        <BaseInput v-model="form.confirmPassword"  label="Confirm Password" type="password" autocomplete="new-password"  required :minlength="8" />
        <BaseButton type="submit" :loading="loading" class="full-width">
          Register
        </BaseButton>
      </form>
      <p class="auth-links">Already have an account? <RouterLink to="/login">Log in</RouterLink></p>
    </template>
  </AuthCard>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { authService } from '../services/authService.js'
import AuthCard   from '../components/layout/AuthCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseInput  from '../components/ui/BaseInput.vue'
import BaseAlert  from '../components/ui/BaseAlert.vue'

const form    = ref({ email: '', username: '', password: '', confirmPassword: '' })
const loading = ref(false)
const error   = ref('')
const success = ref(false)
const successMsg = 'Registration successful! Check your email to verify your account before logging in.'

async function doRegister() {
  error.value = ''
  if (form.value.password !== form.value.confirmPassword) {
    error.value = 'Passwords do not match.'
    return
  }
  loading.value = true
  try {
    await authService.register(form.value.email, form.value.username, form.value.password)
    success.value = true
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
