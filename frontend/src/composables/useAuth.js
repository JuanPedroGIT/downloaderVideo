/**
 * Thin wrapper around the Pinia auth store.
 * Keeps backward compatibility with existing components that call useAuth().
 */
import { useAuthStore } from '../stores/auth.js'
import { apiFetch } from '../services/api.js'
import router from '../router/index.js'

export function useAuth() {
  const store = useAuthStore()

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
    token:           store.token,
    username:        store.username,
    isAuthenticated: store.isAuthenticated,
    login,
    logout,
    authHeaders,
    apiFetch: apiFetchWrapped,
  }
}
