import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { authService } from '../services/authService.js'

export const useAuthStore = defineStore(
  'auth',
  () => {
    const token    = ref('')
    const username = ref('')

    const isAuthenticated = computed(() => token.value !== '')

    async function login(credentials) {
      const data = await authService.login(credentials.username, credentials.password)
      token.value    = data.token
      username.value = data.username
      return data
    }

    function logout() {
      token.value    = ''
      username.value = ''
    }

    function authHeaders() {
      return {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json',
      }
    }

    return { token, username, isAuthenticated, login, logout, authHeaders }
  },
  {
    persist: {
      key: 'auth',
      storage: localStorage,
      pick: ['token', 'username'],
    },
  }
)
