/**
 * Base API fetch wrapper.
 * Reads the auth token from localStorage so it doesn't depend on a specific store,
 * which avoids circular imports between services and stores.
 */

function getToken() {
  try {
    const auth = JSON.parse(localStorage.getItem('auth') || '{}')
    return auth.token || ''
  } catch {
    return ''
  }
}

function authHeaders() {
  return {
    Authorization: `Bearer ${getToken()}`,
    'Content-Type': 'application/json',
  }
}

/**
 * Authenticated JSON fetch. Throws on HTTP errors.
 * Returns null on 204 No Content.
 */
export async function apiFetch(path, options = {}) {
  const res = await fetch(path, {
    ...options,
    headers: { ...authHeaders(), ...options.headers },
  })

  if (res.status === 401) {
    // Token expired / invalid — clear storage and reload to trigger auth guard
    localStorage.removeItem('auth')
    window.location.href = '/login'
    return null
  }

  if (res.status === 204) return null

  const ct = res.headers.get('content-type') || ''
  if (!ct.includes('application/json')) {
    throw new Error(`Server error (HTTP ${res.status})`)
  }

  const data = await res.json().catch(() => ({}))

  if (!res.ok) {
    throw new Error(data.error || `HTTP ${res.status}`)
  }

  return data
}

/**
 * Unauthenticated JSON fetch. Throws on HTTP errors.
 */
export async function publicFetch(path, options = {}) {
  const res = await fetch(path, {
    ...options,
    headers: { 'Content-Type': 'application/json', ...options.headers },
  })

  if (res.status === 204) return null

  const data = await res.json().catch(() => ({}))

  if (!res.ok) {
    throw new Error(data.error || `HTTP ${res.status}`)
  }

  return data
}
