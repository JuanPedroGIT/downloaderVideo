<template>
  <AuthCard icon="🔒" title="Reset Password">
    <BaseAlert v-if="success" message="Password updated successfully! Redirecting to login…" type="success" />

    <template v-else>
      <BaseAlert :message="error" type="error" />
      <form class="auth-form" @submit.prevent="doReset">
        <BaseInput v-model="form.password"        label="New Password"     type="password" autocomplete="new-password" required :minlength="8" />
        <BaseInput v-model="form.confirmPassword" label="Confirm Password" type="password" autocomplete="new-password" required :minlength="8" />
        <BaseButton type="submit" :loading="loading" class="full-width">
          Update Password
        </BaseButton>
      </form>
    </template>
  </AuthCard>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authService } from '../services/authService.js'
import AuthCard   from '../components/layout/AuthCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseInput  from '../components/ui/BaseInput.vue'
import BaseAlert  from '../components/ui/BaseAlert.vue'

const route  = useRoute()
const router = useRouter()

const form    = ref({ password: '', confirmPassword: '' })
const loading = ref(false)
const error   = ref('')
const success = ref(false)

async function doReset() {
  error.value = ''
  const token = route.query.token || ''
  if (!token) { error.value = 'No reset token found in the URL.'; return }
  if (form.value.password !== form.value.confirmPassword) { error.value = 'Passwords do not match.'; return }
  loading.value = true
  try {
    await authService.resetPassword(token, form.value.password)
    success.value = true
    setTimeout(() => router.push('/login'), 2000)
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-form  { display: flex; flex-direction: column; gap: 1rem; text-align: left; }
.full-width { width: 100%; }
</style>
