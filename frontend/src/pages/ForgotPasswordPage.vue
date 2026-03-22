<template>
  <AuthCard icon="🔑" title="Forgot Password">
    <BaseAlert v-if="sent" message="If that email is registered and verified, a reset link has been sent. Check your inbox." type="success" />

    <template v-else>
      <p class="subtitle">Enter your account email and we'll send you a reset link.</p>
      <BaseAlert :message="error" type="error" />
      <form class="auth-form" @submit.prevent="doRequest">
        <BaseInput v-model="email" label="Email" type="email" autocomplete="email" required />
        <BaseButton type="submit" :loading="loading" class="full-width">
          Send Reset Link
        </BaseButton>
      </form>
      <p class="auth-links"><RouterLink to="/login">Back to Login</RouterLink></p>
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

const email   = ref('')
const loading = ref(false)
const error   = ref('')
const sent    = ref(false)

async function doRequest() {
  error.value = ''
  loading.value = true
  try {
    await authService.requestReset(email.value)
    sent.value = true
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.subtitle   { font-size: 0.85rem; color: var(--text-secondary); }
.auth-form  { display: flex; flex-direction: column; gap: 1rem; text-align: left; }
.full-width { width: 100%; }
.auth-links { font-size: 0.85rem; color: var(--text-secondary); }
.auth-links a { color: var(--grad-start); text-decoration: none; }
.auth-links a:hover { text-decoration: underline; }
</style>
