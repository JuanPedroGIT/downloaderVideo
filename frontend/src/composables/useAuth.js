/**
 * Thin wrapper around the Pinia auth store.
 * Keeps backward compatibility with existing components that call useAuth().
 */
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth.js'
import { apiFetch } from '../services/api.js'
import router from '../router/index.js'

export function useAuth() {
  const store = useAuthStore()
  const { token, username, isAuthenticated } = storeToRefs(store)

  async function login(credentials) {
    return store.login(credentials)
  }

  function logout() {
    store.logout()
    router.push('/login')
  }

  function authHeaders() {
    return store.authHeaders()
  }

  async function apiFetchWrapped(path, options = {}) {
    return apiFetch(path, options)
  }

  return {
    token,
    username,
    isAuthenticated,
    login,
    logout,
    authHeaders,
    apiFetch: apiFetchWrapped,
  }
}
