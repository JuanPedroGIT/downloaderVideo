import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { authService } from '../services/authService.js'

export const useAuthStore = defineStore(
  'auth',
  () => {
    const token    = ref('')
    const username = ref('')
    const canDownload = ref(false)

    const isAuthenticated = computed(() => token.value !== '')

    async function login(credentials) {
      const data = await authService.login(credentials.username, credentials.password)
      token.value    = data.token
      username.value = data.username
      await fetchMe()
      return data
    }

    function logout() {
      token.value    = ''
      username.value = ''
      canDownload.value = false
    }

    // Pregunta al backend si el usuario actual puede usar el descargador
    async function fetchMe() {
      if (!token.value) {
        canDownload.value = false
        return
      }
      try {
        const apiBase = import.meta.env.VITE_API_URL || ''
        const res = await fetch(`${apiBase}/api/auth/me`, {
          headers: { Authorization: `Bearer ${token.value}` },
        })
        if (res.ok) {
          const data = await res.json()
          canDownload.value = !!data.canDownload
        } else {
          canDownload.value = false
        }
      } catch {
        canDownload.value = false
      }
    }

    function authHeaders() {
      return {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json',
      }
    }

    return { token, username, canDownload, isAuthenticated, login, logout, fetchMe, authHeaders }
  },
  {
    persist: {
      key: 'auth',
      storage: localStorage,
      pick: ['token', 'username'],
    },
  }
)
