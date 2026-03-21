import { ref } from 'vue'

const ALLOWED_HOSTS = [
  'youtube.com',
  'www.youtube.com',
  'youtu.be',
  'm.youtube.com',
  'music.youtube.com',
  'googlevideo.com',
]

export function useDownload() {
  const isLoading  = ref(false)
  const status     = ref('')
  const statusType = ref('') // 'success' | 'error' | 'loading'
  const progress   = ref(0)
  const urlError   = ref('')

  let pollInterval = null

  // ── Validation ──────────────────────────────────────────────────────────────

  function validateUrl(url) {
    urlError.value = ''
    const trimmed = url.trim()

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

  function clearStatus() {
    status.value     = ''
    statusType.value = ''
    urlError.value   = ''
    progress.value   = 0
    if (pollInterval) clearInterval(pollInterval)
  }

  // ── Download flow ───────────────────────────────────────────────────────────

  async function startDownload(url, format) {
    clearStatus()

    if (!validateUrl(url)) return

    isLoading.value  = true
    status.value     = 'Queuing download job...'
    statusType.value = 'loading'

    try {
      const apiBase = import.meta.env.VITE_API_URL || ''
      const res = await fetch(`${apiBase}/download`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ url: url.trim(), format }),
      })

      if (!res.ok) throw new Error('Failed to queue job.')

      const { jobId } = await res.json()
      pollInterval = setInterval(() => _pollStatus(jobId), 1500)
    } catch (err) {
      status.value     = err.message || 'An unexpected error occurred.'
      statusType.value = 'error'
      isLoading.value  = false
    }
  }

  async function _pollStatus(id) {
    try {
      const apiBase = import.meta.env.VITE_API_URL || ''
      const res = await fetch(`${apiBase}/status/${id}`)
      if (!res.ok) throw new Error('Status check failed.')

      const job = await res.json()
      progress.value = job.progress || 0
      status.value   = job.message  || 'Processing...'

      if (job.status === 'completed') {
        clearInterval(pollInterval)
        isLoading.value  = false
        statusType.value = 'success'
        status.value     = 'Download ready!'
        _triggerFileDownload(id)
      } else if (job.status === 'error') {
        clearInterval(pollInterval)
        isLoading.value  = false
        statusType.value = 'error'
        status.value     = job.message || 'Download failed.'
      }
    } catch {
      clearInterval(pollInterval)
      isLoading.value  = false
      statusType.value = 'error'
      status.value     = 'Lost connection to server.'
    }
  }

  function _triggerFileDownload(id) {
    const apiBase = import.meta.env.VITE_API_URL || ''
    const anchor  = document.createElement('a')
    anchor.href   = `${apiBase}/fetch/${id}`
    anchor.style.display = 'none'
    document.body.appendChild(anchor)
    anchor.click()
    document.body.removeChild(anchor)
  }

  return {
    isLoading,
    status,
    statusType,
    progress,
    urlError,
    startDownload,
    validateUrl,
    clearStatus,
  }
}
