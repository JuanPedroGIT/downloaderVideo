import { apiFetch } from './api.js'

export const qrService = {
  list() {
    return apiFetch('/api/admin/qrcodes')
  },

  create(id, targetUrl) {
    return apiFetch('/api/admin/qrcodes', {
      method: 'POST',
      body: JSON.stringify({ id, targetUrl }),
    })
  },

  update(id, patch) {
    return apiFetch(`/api/admin/qrcodes/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(patch),
    })
  },

  delete(id) {
    return apiFetch(`/api/admin/qrcodes/${id}`, { method: 'DELETE' })
  },

  async getSvg(id) {
    const token = localStorage.getItem('admin_token') || ''
    const res = await fetch(`/api/qr/generate/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    if (!res.ok) throw new Error(`Failed to load QR (HTTP ${res.status})`)
    return res.text()
  },
}
