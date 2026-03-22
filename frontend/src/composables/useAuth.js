import { ref, computed } from 'vue'
import router from '../router/index.js'

// Module-level refs — shared singleton across all components
const token    = ref(localStorage.getItem('admin_token') || '')
const username = ref(localStorage.getItem('admin_username') || '')

export function useAuth() {
  const isAuthenticated = computed(() => token.value !== '')

  async function login(credentials) {
    const res = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(credentials),
    })
    const ct = res.headers.get('content-type') || ''
    if (!ct.includes('application/json')) throw new Error(`Server error (HTTP ${res.status})`)
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Login failed')
    token.value    = data.token
    username.value = data.username
    localStorage.setItem('admin_token', data.token)
    localStorage.setItem('admin_username', data.username)
    return data
  }

  function logout() {
    token.value    = ''
    username.value = ''
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_username')
    router.push('/login')
  }

  function authHeaders() {
    return { Authorization: `Bearer ${token.value}`, 'Content-Type': 'application/json' }
  }

  async function apiFetch(path, options = {}) {
    const res = await fetch(path, { ...options, headers: authHeaders() })
    if (res.status === 401) { logout(); return null }
    if (res.status === 204) return null
    const ct = res.headers.get('content-type') || ''
    if (!ct.includes('application/json')) throw new Error(`Server error (HTTP ${res.status})`)
    const data = await res.json().catch(() => ({}))
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`)
    return data
  }

  return { token, username, isAuthenticated, login, logout, authHeaders, apiFetch }
}
