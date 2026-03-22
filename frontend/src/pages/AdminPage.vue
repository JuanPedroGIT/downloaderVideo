<template>
  <div class="admin">
    <!-- LOGIN -->
    <div v-if="!token" class="login-wrap">
      <div class="login-card">
        <div class="login-card__icon">🔐</div>
        <h1 class="login-card__title">Admin Panel</h1>
        <p v-if="loginError" class="error-msg">{{ loginError }}</p>
        <form class="login-form" @submit.prevent="doLogin">
          <label class="field">
            <span>Username</span>
            <input v-model="credentials.username" type="text" autocomplete="username" required />
          </label>
          <label class="field">
            <span>Password</span>
            <input v-model="credentials.password" type="password" autocomplete="current-password" required />
          </label>
          <button type="submit" class="btn-primary" :disabled="loggingIn">
            {{ loggingIn ? 'Logging in…' : 'Log in' }}
          </button>
        </form>
      </div>
    </div>

    <!-- DASHBOARD -->
    <div v-else class="dashboard">
      <header class="dash-header">
        <div>
          <h1 class="dash-title">QR Code Manager</h1>
          <p class="dash-sub">Logged in as <strong>{{ username }}</strong></p>
        </div>
        <div class="dash-actions">
          <button class="btn-secondary" @click="showForm = true">+ New QR</button>
          <button class="btn-logout" @click="logout">Logout</button>
        </div>
      </header>

      <!-- Error banner -->
      <p v-if="apiError" class="error-msg">{{ apiError }}</p>

      <!-- QR MODAL -->
      <div v-if="qrModal.show" class="modal-backdrop" @click.self="qrModal.show = false">
        <div class="modal">
          <h3 class="modal-title">QR — <code>{{ qrModal.id }}</code></h3>
          <div class="modal-qr" v-html="qrModal.svg" />
          <p class="modal-url">{{ qrModal.url }}</p>
          <button class="btn-secondary" @click="qrModal.show = false">Close</button>
        </div>
      </div>

      <!-- NEW / EDIT FORM -->
      <div v-if="showForm" class="qr-form-wrap">
        <div class="qr-form">
          <h2>{{ editing ? 'Edit QR Code' : 'New QR Code' }}</h2>
          <form @submit.prevent="saveQr">
            <label class="field" v-if="!editing">
              <span>ID (slug)</span>
              <input v-model="form.id" type="text" required placeholder="e.g. foto, mesa5" maxlength="20" />
            </label>
            <label class="field">
              <span>Target URL</span>
              <input v-model="form.targetUrl" type="url" required placeholder="https://…" />
            </label>
            <label class="field" v-if="editing">
              <span>Active</span>
              <select v-model="form.isActive">
                <option :value="true">Yes</option>
                <option :value="false">No</option>
              </select>
            </label>
            <div class="form-row">
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Saving…' : 'Save' }}
              </button>
              <button type="button" class="btn-secondary" @click="cancelForm">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <!-- QR LIST -->
      <div v-if="loading" class="loading">Loading…</div>
      <div v-else-if="qrCodes.length === 0" class="empty">No QR codes yet. Create one!</div>
      <div v-else class="qr-table-wrap">
        <table class="qr-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Target URL</th>
              <th>Clicks</th>
              <th>Active</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="qr in qrCodes" :key="qr.id">
              <td><code>{{ qr.id }}</code></td>
              <td class="url-cell">
                <a :href="qr.targetUrl" target="_blank" rel="noopener">{{ qr.targetUrl }}</a>
              </td>
              <td>{{ qr.clicks }}</td>
              <td>{{ qr.isActive ? 'Yes' : 'No' }}</td>
              <td>{{ qr.createdAt }}</td>
              <td class="action-cell">
                <button class="btn-qr" @click="showQr(qr)">QR</button>
                <button class="btn-edit" @click="editQr(qr)">Edit</button>
                <button class="btn-delete" @click="deleteQr(qr)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// ── State ──────────────────────────────────────────────────────────────────
const token    = ref(localStorage.getItem('admin_token') || '')
const username = ref(localStorage.getItem('admin_username') || '')

const credentials = ref({ username: '', password: '' })
const loggingIn   = ref(false)
const loginError  = ref('')

const qrCodes  = ref([])
const loading  = ref(false)
const apiError = ref('')
const saving   = ref(false)

const showForm = ref(false)
const editing  = ref(null)   // holds the QR id being edited
const form     = ref({ id: '', targetUrl: '', isActive: true })

const qrModal  = ref({ show: false, id: '', svg: '', url: '' })

// ── Auth ───────────────────────────────────────────────────────────────────
async function doLogin() {
  loggingIn.value = true
  loginError.value = ''
  try {
    const res = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(credentials.value),
    })
    const ct = res.headers.get('content-type') || ''
    if (!ct.includes('application/json')) throw new Error(`Server error (HTTP ${res.status})`)
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Login failed')
    token.value    = data.token
    username.value = data.username
    localStorage.setItem('admin_token', data.token)
    localStorage.setItem('admin_username', data.username)
    fetchQrCodes()
  } catch (e) {
    loginError.value = e.message
  } finally {
    loggingIn.value = false
  }
}

function logout() {
  token.value    = ''
  username.value = ''
  localStorage.removeItem('admin_token')
  localStorage.removeItem('admin_username')
  qrCodes.value = []
}

// ── API helpers ────────────────────────────────────────────────────────────
function authHeaders() {
  return { 'Authorization': `Bearer ${token.value}`, 'Content-Type': 'application/json' }
}

async function apiFetch(path, options = {}) {
  const res = await fetch(path, { ...options, headers: authHeaders() })
  if (res.status === 401) { logout(); return null }
  if (res.status === 204) return null
  const ct = res.headers.get('content-type') || ''
  if (!ct.includes('application/json')) {
    throw new Error(`Server error (HTTP ${res.status})`)
  }
  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`)
  return data
}

// ── QR CRUD ────────────────────────────────────────────────────────────────
async function fetchQrCodes() {
  loading.value  = true
  apiError.value = ''
  try {
    const data = await apiFetch('/api/admin/qrcodes')
    if (data) qrCodes.value = data
  } catch (e) {
    apiError.value = e.message
  } finally {
    loading.value = false
  }
}

function editQr(qr) {
  editing.value        = qr.id
  form.value.targetUrl = qr.targetUrl
  form.value.isActive  = qr.isActive
  showForm.value       = true
}

function cancelForm() {
  showForm.value = false
  editing.value  = null
  form.value = { id: '', targetUrl: '', isActive: true }
}

async function saveQr() {
  saving.value   = true
  apiError.value = ''
  try {
    if (editing.value) {
      await apiFetch(`/api/admin/qrcodes/${editing.value}`, {
        method: 'PATCH',
        body: JSON.stringify({ targetUrl: form.value.targetUrl, isActive: form.value.isActive }),
      })
    } else {
      await apiFetch('/api/admin/qrcodes', {
        method: 'POST',
        body: JSON.stringify({ id: form.value.id, targetUrl: form.value.targetUrl }),
      })
    }
    cancelForm()
    await fetchQrCodes()
  } catch (e) {
    apiError.value = e.message
  } finally {
    saving.value = false
  }
}

async function showQr(qr) {
  const res = await fetch(`/api/qr/generate/${qr.id}`, { headers: authHeaders() })
  const svg = await res.text()
  qrModal.value = { show: true, id: qr.id, svg, url: qr.targetUrl }
}

async function deleteQr(qr) {
  if (!confirm(`Delete QR "${qr.id}"?`)) return
  apiError.value = ''
  try {
    await apiFetch(`/api/admin/qrcodes/${qr.id}`, { method: 'DELETE' })
    await fetchQrCodes()
  } catch (e) {
    apiError.value = e.message
  }
}

// ── Init ───────────────────────────────────────────────────────────────────
onMounted(() => { if (token.value) fetchQrCodes() })
</script>

<style scoped>
.admin {
  min-height: 100vh;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 2rem 1rem;
}

/* LOGIN */
.login-wrap {
  width: 100%;
  max-width: 380px;
  margin-top: 6rem;
}

.login-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  text-align: center;
}

.login-card__icon { font-size: 2.5rem; }

.login-card__title {
  font-size: 1.5rem;
  font-weight: 700;
}

.login-form { display: flex; flex-direction: column; gap: 1rem; text-align: left; }

/* DASHBOARD */
.dashboard {
  width: 100%;
  max-width: 900px;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.dash-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.dash-title { font-size: 1.6rem; font-weight: 800; }
.dash-sub { color: var(--text-secondary); font-size: 0.85rem; }

.dash-actions { display: flex; gap: 0.75rem; }

/* FORM */
.qr-form-wrap {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
}

.qr-form { display: flex; flex-direction: column; gap: 1rem; }
.qr-form h2 { font-size: 1.1rem; font-weight: 700; }
.form-row { display: flex; gap: 0.75rem; }

/* FIELDS */
.field { display: flex; flex-direction: column; gap: 0.35rem; font-size: 0.85rem; }
.field span { color: var(--text-secondary); font-weight: 500; }
.field input {
  background: var(--bg-input, #1e1e2e);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0.55rem 0.75rem;
  color: var(--text-primary);
  font-size: 0.9rem;
  transition: border-color 0.2s;
}
.field input:focus { outline: none; border-color: var(--grad-start); }
.field select {
  background: var(--bg-input, #1e1e2e);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0.55rem 0.75rem;
  color: var(--text-primary);
  font-size: 0.9rem;
}
.field select:focus { outline: none; border-color: var(--grad-start); }

/* TABLE */
.qr-table-wrap { overflow-x: auto; }

.qr-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.qr-table th, .qr-table td {
  padding: 0.65rem 0.85rem;
  text-align: left;
  border-bottom: 1px solid var(--border);
}

.qr-table th {
  color: var(--text-secondary);
  font-weight: 600;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.url-cell a {
  color: var(--grad-start);
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
  max-width: 280px;
}

.action-cell { display: flex; gap: 0.5rem; }

/* BUTTONS */
.btn-primary {
  background: linear-gradient(135deg, var(--grad-start), var(--grad-end));
  color: #fff;
  border: none;
  border-radius: var(--radius);
  padding: 0.6rem 1.2rem;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-primary);
  border-radius: var(--radius);
  padding: 0.6rem 1.1rem;
  font-size: 0.85rem;
  cursor: pointer;
  transition: border-color 0.2s;
}
.btn-secondary:hover { border-color: var(--grad-start); }

.btn-qr {
  background: transparent;
  border: 1px solid var(--grad-start);
  color: var(--grad-start);
  border-radius: var(--radius);
  padding: 0.3rem 0.7rem;
  font-size: 0.78rem;
  cursor: pointer;
  font-weight: 600;
}
.btn-qr:hover { background: var(--grad-start); color: #fff; }

.btn-edit {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-primary);
  border-radius: var(--radius);
  padding: 0.3rem 0.7rem;
  font-size: 0.78rem;
  cursor: pointer;
}
.btn-edit:hover { border-color: var(--grad-start); }

.btn-delete {
  background: transparent;
  border: 1px solid #f87171;
  color: #f87171;
  border-radius: var(--radius);
  padding: 0.3rem 0.7rem;
  font-size: 0.78rem;
  cursor: pointer;
}
.btn-delete:hover { background: #f87171; color: #fff; }

.btn-logout {
  background: transparent;
  border: 1px solid #f87171;
  color: #f87171;
  border-radius: var(--radius);
  padding: 0.55rem 1rem;
  font-size: 0.85rem;
  cursor: pointer;
}
.btn-logout:hover { background: #f87171; color: #fff; }

/* MODAL */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.modal {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  max-width: 360px;
  width: 100%;
}

.modal-title { font-size: 1rem; font-weight: 700; }

.modal-qr svg { width: 260px; height: 260px; }

.modal-url {
  font-size: 0.75rem;
  color: var(--text-secondary);
  word-break: break-all;
  text-align: center;
}

/* MISC */
.error-msg {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
  border-radius: var(--radius);
  padding: 0.65rem 1rem;
  font-size: 0.85rem;
}

.loading, .empty {
  text-align: center;
  color: var(--text-secondary);
  padding: 3rem 0;
}

code {
  background: var(--bg-input, #1e1e2e);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  font-size: 0.82rem;
}
</style>
