<template>
  <div class="doc-manager">
    <!-- Dropzone -->
    <div
      class="dropzone"
      :class="{ 'dropzone--over': isDragging, 'dropzone--has-files': files.length > 0 }"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
      @click="fileInput.click()"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".pdf,.docx"
        class="dropzone__input"
        @change="onFileSelect"
      />
      <span class="dropzone__icon" aria-hidden="true">📄</span>
      <p class="dropzone__text">
        {{ files.length > 0 ? 'Add more files' : 'Drop files here or click to select' }}
      </p>
      <p class="dropzone__hint">Accepts .pdf and .docx · Max 50 MB per file</p>
    </div>

    <!-- File list -->
    <transition-group v-if="files.length > 0" name="list" tag="ul" class="file-list">
      <li v-for="(file, index) in files" :key="file.name + index" class="file-item">
        <span class="file-item__icon" aria-hidden="true">{{ fileIcon(file) }}</span>
        <div class="file-item__info">
          <span class="file-item__name">{{ file.name }}</span>
          <span class="file-item__size">{{ formatSize(file.size) }}</span>
        </div>
        <div class="file-item__actions">
          <button :disabled="index === 0" class="btn-order" title="Move up" @click="moveUp(index)">↑</button>
          <button :disabled="index === files.length - 1" class="btn-order" title="Move down" @click="moveDown(index)">↓</button>
          <button class="btn-remove" title="Remove" @click="removeFile(index)">✕</button>
        </div>
      </li>
    </transition-group>

    <!-- Actions -->
    <div v-if="files.length > 0" class="doc-actions">
      <p class="doc-actions__summary">
        {{ files.length }} file{{ files.length > 1 ? 's' : '' }} –
        {{ files.length > 1 ? 'will be merged into one PDF' : 'will be converted to PDF' }}
      </p>

      <button
        class="btn-merge"
        :disabled="isLoading"
        @click="handleMerge"
      >
        <span v-if="isLoading" class="spinner" aria-hidden="true" />
        <span v-else aria-hidden="true">⬇</span>
        {{ isLoading ? 'Processing...' : (files.length > 1 ? 'Merge & Download' : 'Convert & Download') }}
      </button>

      <button class="btn-clear" :disabled="isLoading" @click="clearAll">Clear all</button>
    </div>

    <!-- Status -->
    <div v-if="statusMsg" class="doc-status" :class="statusType" role="alert" aria-live="polite">
      <span aria-hidden="true">{{ statusType === 'success' ? '✅' : '❌' }}</span>
      {{ statusMsg }}
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const fileInput = ref(null)
const files     = ref([])
const isDragging = ref(false)
const isLoading  = ref(false)
const statusMsg  = ref('')
const statusType = ref('')

// ── File management ──────────────────────────────────────────────────────────

function onFileSelect(event) {
  addFiles(Array.from(event.target.files))
  event.target.value = ''
}

function onDrop(event) {
  isDragging.value = false
  addFiles(Array.from(event.dataTransfer.files))
}

function addFiles(newFiles) {
  const allowed = newFiles.filter(f => /\.(pdf|docx)$/i.test(f.name))
  const skipped = newFiles.length - allowed.length

  allowed.forEach(f => {
    if (!files.value.some(existing => existing.name === f.name && existing.size === f.size)) {
      files.value.push(f)
    }
  })

  if (skipped > 0) {
    showStatus(`${skipped} file(s) skipped — only .pdf and .docx allowed.`, 'error')
  }
}

function removeFile(index) {
  files.value.splice(index, 1)
  clearStatus()
}

function moveUp(index) {
  if (index === 0) return
  const arr = [...files.value]
  ;[arr[index - 1], arr[index]] = [arr[index], arr[index - 1]]
  files.value = arr
}

function moveDown(index) {
  if (index === files.value.length - 1) return
  const arr = [...files.value]
  ;[arr[index], arr[index + 1]] = [arr[index + 1], arr[index]]
  files.value = arr
}

function clearAll() {
  files.value = []
  clearStatus()
}

// ── Upload & merge ───────────────────────────────────────────────────────────

async function handleMerge() {
  clearStatus()
  isLoading.value = true

  const formData = new FormData()
  files.value.forEach(f => formData.append('files[]', f))

  try {
    const apiBase = import.meta.env.VITE_API_URL || ''
    const res = await fetch(`${apiBase}/api/documents/merge`, {
      method: 'POST',
      body:   formData,
    })

    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: `Server error ${res.status}` }))
      throw new Error(err.error || `Server error ${res.status}`)
    }

    // Stream the blob and trigger browser download
    const blob     = await res.blob()
    const filename = getFilename(res) || `merged_${Date.now()}.pdf`
    const url      = URL.createObjectURL(blob)
    const anchor   = document.createElement('a')
    anchor.href    = url
    anchor.download = filename
    document.body.appendChild(anchor)
    anchor.click()
    document.body.removeChild(anchor)
    URL.revokeObjectURL(url)

    showStatus('PDF downloaded successfully!', 'success')
    files.value = []
  } catch (err) {
    showStatus(err.message || 'An unexpected error occurred.', 'error')
  } finally {
    isLoading.value = false
  }
}

/** Extracts filename from Content-Disposition header. */
function getFilename(response) {
  const cd = response.headers.get('Content-Disposition') || ''
  const match = cd.match(/filename\*?=(?:utf-8'')?["']?([^;"'\n]+)/i)
  return match ? decodeURIComponent(match[1]) : null
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function fileIcon(file) {
  return /\.docx$/i.test(file.name) ? '📝' : '📄'
}

function formatSize(bytes) {
  if (bytes < 1024)        return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function showStatus(msg, type) {
  statusMsg.value  = msg
  statusType.value = type
}

function clearStatus() {
  statusMsg.value  = ''
  statusType.value = ''
}
</script>

<style scoped>
.doc-manager {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Dropzone */
.dropzone {
  border: 2px dashed var(--border);
  border-radius: 12px;
  padding: 2rem 1rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  position: relative;
}
.dropzone:hover,
.dropzone--over {
  border-color: var(--grad-start);
  background: rgba(139, 92, 246, 0.06);
}
.dropzone__input {
  display: none;
}
.dropzone__icon {
  font-size: 2rem;
  display: block;
  margin-bottom: 0.5rem;
}
.dropzone__text {
  color: var(--text-primary);
  font-weight: 500;
  margin: 0;
}
.dropzone__hint {
  color: rgba(255,255,255,0.4);
  font-size: 0.78rem;
  margin: 0.25rem 0 0;
}

/* File list */
.file-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.file-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
}
.file-item__icon { font-size: 1.2rem; flex-shrink: 0; }
.file-item__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}
.file-item__name {
  font-size: 0.85rem;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.file-item__size {
  font-size: 0.72rem;
  color: rgba(255,255,255,0.4);
}
.file-item__actions { display: flex; gap: 0.3rem; flex-shrink: 0; }

.btn-order, .btn-remove {
  background: rgba(255,255,255,0.07);
  border: 1px solid var(--border);
  border-radius: 6px;
  color: var(--text-primary);
  width: 1.8rem;
  height: 1.8rem;
  cursor: pointer;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.btn-order:hover:not(:disabled) { background: rgba(255,255,255,0.14); }
.btn-order:disabled { opacity: 0.3; cursor: default; }
.btn-remove:hover { background: rgba(248,113,113,0.2); border-color: var(--error); color: var(--error); }

/* Actions */
.doc-actions {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.doc-actions__summary {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.5);
  margin: 0;
  text-align: center;
}

.btn-merge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.85rem;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--grad-start), var(--grad-mid));
  color: #fff;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}
.btn-merge:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-clear {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: 8px;
  color: rgba(255,255,255,0.45);
  padding: 0.5rem;
  cursor: pointer;
  font-size: 0.82rem;
  transition: color 0.15s, border-color 0.15s;
}
.btn-clear:hover:not(:disabled) { color: var(--error); border-color: var(--error); }

/* Status */
.doc-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.88rem;
}
.doc-status.success { background: rgba(52,211,153,0.12); color: var(--success); border: 1px solid rgba(52,211,153,0.3); }
.doc-status.error   { background: rgba(248,113,113,0.12); color: var(--error);   border: 1px solid rgba(248,113,113,0.3); }

/* List transition */
.list-enter-active, .list-leave-active { transition: all 0.2s ease; }
.list-enter-from { opacity: 0; transform: translateY(-8px); }
.list-leave-to   { opacity: 0; transform: translateX(8px); }

/* Spinner (reuses global class) */
.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
