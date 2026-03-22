import { publicFetch } from './api.js'

export const authService = {
  login(username, password) {
    return publicFetch('/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    })
  },

  register(email, username, password) {
    return publicFetch('/api/auth/register', {
      method: 'POST',
      body: JSON.stringify({ email, username, password }),
    })
  },

  verifyEmail(token) {
    return publicFetch(`/api/auth/verify-email?token=${encodeURIComponent(token)}`)
  },

  requestReset(email) {
    return publicFetch('/api/auth/request-reset', {
      method: 'POST',
      body: JSON.stringify({ email }),
    })
  },

  resetPassword(token, password) {
    return publicFetch('/api/auth/reset-password', {
      method: 'POST',
      body: JSON.stringify({ token, password }),
    })
  },
}
