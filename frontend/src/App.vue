<template>
  <!-- Animated background -->
  <div class="bg-gradient" aria-hidden="true" />

  <!-- Main card -->
  <main class="card" role="main">
    <!-- Header -->
    <div class="logo-row">
      <div class="logo-icon" aria-hidden="true">⬇</div>
      <div class="logo-text">
        <h1>YT Downloader</h1>
        <p>YouTube videos &amp; audio in any format</p>
      </div>
    </div>

    <hr class="divider" />

    <!-- URL Input -->
    <div class="field-group">
      <label class="field-label" for="url-input">YouTube URL</label>
      <input
        id="url-input"
        v-model="url"
        type="url"
        class="input-url"
        :class="{ invalid: urlError }"
        placeholder="https://www.youtube.com/watch?v=..."
        autocomplete="off"
        spellcheck="false"
        @blur="validateUrl"
        @input="clearStatus"
      />
      <p v-if="urlError" class="field-error">{{ urlError }}</p>
    </div>

    <!-- Format Selector -->
    <div class="field-group">
      <label class="field-label">Output Format</label>
      <div class="format-grid" role="radiogroup" aria-label="Output format">
        <button
          v-for="fmt in formats"
          :key="fmt.value"
          type="button"
          class="format-btn"
          :class="{ active: selectedFormat === fmt.value }"
          :aria-pressed="selectedFormat === fmt.value"
          :title="fmt.description"
          @click="selectedFormat = fmt.value"
        >
          <span class="format-icon" aria-hidden="true">{{ fmt.icon }}</span>
          <span>{{ fmt.label }}</span>
        </button>
      </div>
    </div>

    <!-- Download Button -->
    <button
      id="download-btn"
      class="btn-download"
      :disabled="isLoading || !!urlError"
      @click="handleDownload"
    >
      <span v-if="isLoading" class="spinner" aria-hidden="true" />
      <span v-else aria-hidden="true">⬇</span>
      <span>{{ isLoading ? (progress > 0 ? `Downloading ${progress}%` : 'Queuing...') : 'Download' }}</span>
    </button>

    <!-- Progress Bar (Visible during loading) -->
    <div v-if="isLoading" class="progress-container" aria-hidden="true">
      <div class="progress-inner" :style="{ width: progress + '%' }"></div>
    </div>


    <!-- Status Message -->
    <div
      v-if="status"
      class="status-bar"
      :class="statusClass"
      role="alert"
      aria-live="polite"
    >
      <span class="status-icon" aria-hidden="true">{{ statusIcon }}</span>
      <span>{{ status }}</span>
    </div>

    <!-- Supported Platforms Note -->
    <p class="platforms-note">
      Currently supports <span>YouTube</span> · Vimeo &amp; TikTok coming soon
    </p>
  </main>
</template>

<script setup>
import { ref, computed } from 'vue'

// ── State ─────────────────────────────────────────────────────────────────────
const url            = ref('')
const selectedFormat = ref('mp4')
const isLoading      = ref(false)
const status         = ref('')
const statusType     = ref('') // 'success' | 'error' | 'loading'
const urlError       = ref('')
const jobId          = ref(null)
const progress       = ref(0)
let pollInterval     = null

// ── Format definitions ────────────────────────────────────────────────────────
const formats = [
  { value: 'mp4',   label: 'MP4',   icon: '🎬', description: 'Video with audio (best quality)' },
  { value: 'mp3',   label: 'MP3',   icon: '🎵', description: 'Audio only – MP3' },
  { value: 'webm',  label: 'WebM',  icon: '🌐', description: 'Video in WebM format' },
  { value: 'audio', label: 'Audio', icon: '🎧', description: 'Best quality audio only' },
  { value: 'video', label: 'Video', icon: '📹', description: 'Video only (no audio)' },
]

// ── Allowed hosts ─────────────────────────────────────────────────────────────
const ALLOWED_HOSTS = [
  'youtube.com',
  'www.youtube.com',
  'youtu.be',
  'm.youtube.com',
  'music.youtube.com',
  'googlevideo.com',
]

// ── Computed ──────────────────────────────────────────────────────────────────
const statusClass  = computed(() => statusType.value)
const statusIcon   = computed(() => ({
  success: '✅',
  error:   '❌',
  loading: '⏳',
}[statusType.value] ?? ''))

// ── Validation ────────────────────────────────────────────────────────────────
function validateUrl () {
  urlError.value = ''
  const trimmed = url.value.trim()

  if (!trimmed) {
    urlError.value = 'Please enter a YouTube URL.'
    return false
  }

  let parsed
  try {
    parsed = new URL(trimmed)
  } catch {
    urlError.value = 'Invalid URL format.'
    return false
  }

  const host = parsed.hostname.replace(/^www\./, '')
  if (!ALLOWED_HOSTS.some(h => host.includes(h.replace(/^www\./, '')))) {
    urlError.value = `Unsupported domain "${parsed.hostname}". Only YouTube URLs are allowed.`
    return false
  }

  return true
}

function clearStatus () {
  status.value    = ''
  statusType.value = ''
  urlError.value  = ''
  progress.value  = 0
  if (pollInterval) clearInterval(pollInterval)
}

// ── Download handler ──────────────────────────────────────────────────────────
async function handleDownload () {
  clearStatus()

  if (!validateUrl()) return

  isLoading.value   = true
  status.value      = 'Queuing download job...'
  statusType.value  = 'loading'

  try {
    const apiBase = import.meta.env.VITE_API_URL || ''
    const response = await fetch(`${apiBase}/download`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        url:    url.value.trim(),
        format: selectedFormat.value,
      }),
    })

    if (!response.ok) {
      throw new Error('Failed to queue job.')
    }

    const { jobId: id } = await response.json()
    jobId.value = id
    
    // Start polling status
    pollInterval = setInterval(() => pollStatus(id), 1500)
  } catch (err) {
    status.value     = err.message || 'An unexpected error occurred.'
    statusType.value = 'error'
    isLoading.value  = false
  }
}

async function pollStatus(id) {
  try {
    const apiBase = import.meta.env.VITE_API_URL || ''
    const response = await fetch(`${apiBase}/status/${id}`)
    if (!response.ok) throw new Error('Status check failed.')

    const job = await response.json()
    progress.value = job.progress || 0
    status.value = job.message || 'Processing...'

    if (job.status === 'completed') {
      clearInterval(pollInterval)
      isLoading.value = false
      statusType.value = 'success'
      status.value = 'Download ready!'
      triggerFileDownload(id)
    } else if (job.status === 'error') {
      clearInterval(pollInterval)
      isLoading.value = false
      statusType.value = 'error'
      status.value = job.message || 'Download failed.'
    }
  } catch (err) {
    clearInterval(pollInterval)
    isLoading.value = false
    statusType.value = 'error'
    status.value = 'Lost connection to server.'
  }
}

function triggerFileDownload(id) {
  const apiBase = import.meta.env.VITE_API_URL || ''
  const downloadUrl = `${apiBase}/fetch/${id}`
  
  // Create a temporary hidden anchor to trigger download
  const anchor = document.createElement('a')
  anchor.href = downloadUrl
  anchor.style.display = 'none'
  document.body.appendChild(anchor)
  anchor.click()
  document.body.removeChild(anchor)
}
</script>


<style scoped>
.field-error {
  font-size: 0.78rem;
  color: var(--error);
  margin-top: 0.35rem;
  padding-left: 0.2rem;
}
</style>
