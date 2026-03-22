<template>
  <span
    class="base-spinner"
    :style="{ width: size, height: size }"
    role="status"
    :aria-label="label"
  />
</template>

<script setup>
defineProps({
  size:  { type: String, default: '18px' },
  label: { type: String, default: 'Loading…' },
})
</script>

<style scoped>
.base-spinner {
  display: inline-block;
  border: 2.5px solid rgba(255, 255, 255, 0.25);
  border-top-color: currentColor;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
