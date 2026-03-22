<template>
  <div class="qr-page">
    <header class="page-header">
      <h1 class="page-title">My QR Codes</h1>
      <BaseButton @click="openCreate">+ New QR</BaseButton>
    </header>

    <BaseAlert :message="error" type="error" />

    <QrTable
      :qr-codes="qrCodes"
      :loading="loading"
      @show-qr="onShowQr"
      @edit="onEdit"
      @delete="onDelete"
    />

    <QrForm
      v-model="showForm"
      :editing="editingQr"
      :saving="saving"
      :error="formError"
      @save="onSave"
    />

    <QrDisplayModal
      v-model="qrModal.show"
      :svg="qrModal.svg"
      :qr="qrModal.qr"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQrCode }       from '../composables/useQrCode.js'
import BaseButton          from '../components/ui/BaseButton.vue'
import BaseAlert           from '../components/ui/BaseAlert.vue'
import QrTable             from '../components/features/qr/QrTable.vue'
import QrForm              from '../components/features/qr/QrForm.vue'
import QrDisplayModal      from '../components/features/qr/QrDisplayModal.vue'

const { qrCodes, loading, error, fetchAll, create, update, remove, getSvg } = useQrCode()

const showForm  = ref(false)
const editingQr = ref(null)
const saving    = ref(false)
const formError = ref('')
const qrModal   = ref({ show: false, svg: '', qr: null })

function openCreate() {
  editingQr.value = null
  showForm.value  = true
}

function onEdit(qr) {
  editingQr.value = qr
  showForm.value  = true
}

async function onDelete(qr) {
  if (!confirm(`Delete QR "${qr.id}"?`)) return
  try {
    await remove(qr.id)
  } catch (e) {
    error.value = e.message
  }
}

async function onSave(data) {
  saving.value    = true
  formError.value = ''
  try {
    if (editingQr.value) {
      await update(editingQr.value.id, { targetUrl: data.targetUrl, isActive: data.isActive })
    } else {
      await create(data.id, data.targetUrl)
    }
    showForm.value = false
  } catch (e) {
    formError.value = e.message
  } finally {
    saving.value = false
  }
}

async function onShowQr(qr) {
  const svg = await getSvg(qr.id)
  qrModal.value = { show: true, svg, qr }
}

onMounted(fetchAll)
</script>

<style scoped>
.qr-page {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title { font-size: 1.6rem; font-weight: 800; }
</style>
