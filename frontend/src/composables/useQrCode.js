import { ref } from 'vue'
import { qrService } from '../services/qrService.js'

export function useQrCode() {
  const qrCodes  = ref([])
  const loading  = ref(false)
  const error    = ref('')

  async function fetchAll() {
    loading.value = true
    error.value   = ''
    try {
      const data = await qrService.list()
      if (data) qrCodes.value = data
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  async function create(id, targetUrl) {
    const data = await qrService.create(id, targetUrl)
    await fetchAll()
    return data
  }

  async function update(id, patch) {
    const data = await qrService.update(id, patch)
    await fetchAll()
    return data
  }

  async function remove(id) {
    await qrService.delete(id)
    await fetchAll()
  }

  async function getSvg(id) {
    return qrService.getSvg(id)
  }

  return { qrCodes, loading, error, fetchAll, create, update, remove, getSvg }
}
