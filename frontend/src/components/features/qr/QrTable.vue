<template>
  <div v-if="loading" class="state-msg">Loading…</div>
  <div v-else-if="qrCodes.length === 0" class="state-msg">No QR codes yet. Create one!</div>
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
          <td data-label="ID"><code>{{ qr.id }}</code></td>
          <td data-label="Target URL" class="url-cell">
            <a :href="qr.targetUrl" target="_blank" rel="noopener">{{ qr.targetUrl }}</a>
          </td>
          <td data-label="Clicks">{{ qr.clicks }}</td>
          <td data-label="Active">{{ qr.isActive ? 'Yes' : 'No' }}</td>
          <td data-label="Created">{{ qr.createdAt }}</td>
          <td class="action-cell">
            <BaseButton variant="ghost"     @click="$emit('show-qr', qr)"  >QR</BaseButton>
            <BaseButton variant="secondary" @click="$emit('edit', qr)"     >Edit</BaseButton>
            <BaseButton variant="danger"    @click="$emit('delete', qr)"   >Delete</BaseButton>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import BaseButton from '../../ui/BaseButton.vue'

defineProps({
  qrCodes: { type: Array, required: true },
  loading: { type: Boolean, default: false },
})
defineEmits(['show-qr', 'edit', 'delete'])
</script>

<style scoped>
.state-msg { text-align: center; color: var(--text-secondary); padding: 3rem 0; }

.qr-table-wrap { overflow-x: auto; }
.qr-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.qr-table th,
.qr-table td { padding: 0.65rem 0.85rem; text-align: left; border-bottom: 1px solid var(--border); }
.qr-table th { color: var(--text-secondary); font-weight: 600; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; }

.url-cell a { color: var(--grad-start); text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; max-width: 280px; }
.action-cell { display: flex; gap: 0.4rem; }
.action-cell :deep(.base-btn) { padding: 0.3rem 0.65rem; font-size: 0.78rem; }

@media (max-width: 600px) {
  .qr-table thead { display: none; }
  .qr-table, .qr-table tbody, .qr-table tr, .qr-table td { display: block; width: 100%; }
  .qr-table tr {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    margin-bottom: 0.75rem;
    padding: 0.75rem 1rem;
  }
  .qr-table td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.4rem 0;
    border-bottom: none;
    font-size: 0.83rem;
    gap: 0.5rem;
  }
  .qr-table td::before {
    content: attr(data-label);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .url-cell a { max-width: 160px; }
  .action-cell { justify-content: flex-end; }
  .action-cell::before { display: none; }
}

code { background: rgba(255,255,255,0.06); padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.82rem; }
</style>
