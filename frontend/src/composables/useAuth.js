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
  const { token, username, isAuthenticated, canDownload } = storeToRefs(store)

  async function login(credentials) {
    return store.login(credentials)
  }

  function logout() {
    store.logout()
    router.push('/login')
  }

  async function fetchMe() {
    return store.fetchMe()
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
    canDownload,
    login,
    logout,
    fetchMe,
    authHeaders,
    apiFetch: apiFetchWrapped,
  }
}
