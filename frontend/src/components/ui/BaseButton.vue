<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="['base-btn', `base-btn--${variant}`, { 'base-btn--loading': loading }]"
    v-bind="$attrs"
  >
    <span v-if="loading" class="base-btn__spinner" aria-hidden="true" />
    <slot />
  </button>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'primary' }, // primary | secondary | danger | ghost
  type:    { type: String, default: 'button' },
  loading: { type: Boolean, default: false },
  disabled:{ type: Boolean, default: false },
})
</script>

<style scoped>
.base-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem 1.2rem;
  border-radius: var(--radius-sm);
  font-family: inherit;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.15s, border-color 0.2s;
  white-space: nowrap;
  border: 1px solid transparent;
}

.base-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

/* Variants */
.base-btn--primary {
  background: linear-gradient(135deg, var(--grad-start), var(--grad-end));
  color: #fff;
  border-color: transparent;
}
.base-btn--primary:hover:not(:disabled) { opacity: 0.88; transform: translateY(-1px); }

.base-btn--secondary {
  background: transparent;
  color: var(--text-primary);
  border-color: var(--border);
}
.base-btn--secondary:hover:not(:disabled) { border-color: var(--grad-start); }

.base-btn--danger {
  background: transparent;
  color: var(--error);
  border-color: var(--error);
}
.base-btn--danger:hover:not(:disabled) { background: var(--error); color: #fff; }

.base-btn--ghost {
  background: transparent;
  color: var(--grad-start);
  border-color: var(--grad-start);
}
.base-btn--ghost:hover:not(:disabled) { background: var(--grad-start); color: #fff; }

/* Spinner */
.base-btn__spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: currentColor;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
