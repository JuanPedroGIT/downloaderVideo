<template>
  <div
    v-if="message"
    :class="['base-alert', `base-alert--${type}`]"
    role="alert"
    aria-live="polite"
  >
    <span class="base-alert__icon" aria-hidden="true">{{ icons[type] }}</span>
    <span>{{ message }}</span>
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: '' },
  type:    { type: String, default: 'error' }, // error | success | info | warning
})

const icons = { error: '❌', success: '✅', info: 'ℹ️', warning: '⚠️' }
</script>

<style scoped>
.base-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.65rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  line-height: 1.5;
  animation: fade-in 0.25s ease;
}

.base-alert--error   { background: rgba(248,113,113,.12); border: 1px solid rgba(248,113,113,.3); color: var(--error); }
.base-alert--success { background: rgba(52,211,153,.12);  border: 1px solid rgba(52,211,153,.3);  color: var(--success); }
.base-alert--info    { background: rgba(139,92,246,.10);  border: 1px solid rgba(139,92,246,.25); color: #a78bfa; }
.base-alert--warning { background: rgba(251,191,36,.10);  border: 1px solid rgba(251,191,36,.25); color: var(--warning); }

.base-alert__icon { flex-shrink: 0; font-size: 0.9rem; }

@keyframes fade-in {
  from { opacity: 0; transform: translateY(3px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
